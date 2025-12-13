<template>
  <div>
    <div class="grid">
      <div v-for="v in vehicles" :key="v.id" class="card" @click="openModal(v)">
        <div class="image-placeholder"></div>
        <div class="card-body">
          <div class="title">{{ v.name }}</div>
          <div class="type">{{ v.type }}</div>
          <div class="price-row">
            <div class="price">
              {{ formatCurrency(v.calculation.total) }}
            </div>
          </div>
          <button class="open-btn" @click.stop="$emit('select-and-open', v)">Open in calculator</button>
        </div>
      </div>
    </div>

    <div v-if="modalOpen" class="modal-backdrop" @click.self="closeModal">
      <div class="modal" role="dialog" aria-modal="true" :aria-labelledby="'modal-title-' + (modalVehicle?.id || '')" ref="modalRef" tabindex="-1">
        <div class="modal-header">
          <h3 :id="'modal-title-' + (modalVehicle?.id || '')">{{ modalVehicle?.name }}</h3>
          <button class="close" @click="closeModal" ref="closeBtn">×</button>
        </div>
        <div class="modal-body">
          <div class="image-placeholder"></div>
          <div>Price: {{ formatCurrency(modalVehicle?.price) }} ({{ capitalize(modalVehicle?.type || '') }})</div>
          <div>Basic buyer fee: {{ formatCurrency(modalVehicle?.calculation?.fees?.basic_buyer_fee) }}</div>
          <div>Special fee: {{ formatCurrency(modalVehicle?.calculation?.fees?.seller_special_fee) }}</div>
          <div>Association fee: {{ formatCurrency(modalVehicle?.calculation?.fees?.association_fee) }}</div>
          <div>Storage fee: {{ formatCurrency(modalVehicle?.calculation?.fees?.storage_fee) }}</div>
          <div class="sep"></div>
          <div><strong>Total: {{ formatCurrency(modalVehicle?.calculation?.total) }}</strong></div>
          <button class="open-btn" style="width: 100%; margin-top: 12px" @click="$emit('select-and-open', modalVehicle); closeModal()">Open in Calculator</button>
        </div>
        <div class="modal-footer">
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { ref, onMounted, watch, nextTick, onBeforeUnmount } from 'vue'
  import axios from 'axios'

  interface Vehicle {
    id: number
    name: string
    price: number
    type: string
    calculation: any
  }

  const vehicles = ref<Vehicle[]>([])
  const modalOpen = ref(false)
  const modalVehicle = ref<any | null>(null)
  const modalRef = ref<HTMLElement | null>(null)
  const closeBtn = ref<HTMLElement | null>(null)

  function getFocusableElements(root: HTMLElement) {
    const selectors = 'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, [tabindex]:not([tabindex="-1"]), [contenteditable]';
    return Array.from(root.querySelectorAll<HTMLElement>(selectors)).filter(el => el.offsetParent !== null);
  }

  function handleKeyDown(e: KeyboardEvent) {
    if (!modalOpen.value) return;
    if (e.key === 'Escape') {
      e.preventDefault();
      closeModal();
      return;
    }

    if (e.key === 'Tab') {
      const root = modalRef.value;
      if (!root) return;
      const focusable = getFocusableElements(root);
      if (focusable.length === 0) {
        e.preventDefault();
        return;
      }
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (e.shiftKey) {
        if (document.activeElement === first) {
          e.preventDefault();
          last.focus();
        }
      } else {
        if (document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    }
  }

  function formatCurrency(val: number | undefined) {
    return `$${Number(val || 0).toFixed(2)}`
  }

  function capitalize(s: string) {
    return s.charAt(0).toUpperCase() + s.slice(1)
  }

  function openModal(v: Vehicle) { modalVehicle.value = v; modalOpen.value = true }
  function closeModal() { modalOpen.value = false; modalVehicle.value = null }

  watch(modalOpen, async (val) => {
    if (val) {
      await nextTick();
      // focus the close button if available
      if (closeBtn.value) {
        (closeBtn.value as HTMLElement).focus();
      } else if (modalRef.value) {
        modalRef.value.focus();
      }
      document.addEventListener('keydown', handleKeyDown);
      // prevent background scrolling
      document.body.style.overflow = 'hidden';
    } else {
      document.removeEventListener('keydown', handleKeyDown);
      document.body.style.overflow = '';
    }
  })

  onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleKeyDown);
    document.body.style.overflow = '';
  })

  onMounted(async () => {
    try {
      const base = (import.meta as any).env.VITE_API_BASE || 'http://localhost:8000'
      const res = await axios.get(`${base}/api/vehicles`)
      vehicles.value = res.data
    } catch (err) {
      console.error('Failed to load vehicles', err)
    }
  })
</script>

<style scoped>
  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 12px;
  }

  .card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform .2s ease, box-shadow .2s ease;
    cursor: pointer;
  }

  .card:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 16px rgba(0, 0, 0, .12);
  }

  .image-placeholder {
    height: 120px;
    background: linear-gradient(135deg, #f0f4f8, #dfeef6);
    border-radius: 8px 8px 0 0;
  }

  .card-body {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
  }

  .title {
    font-weight: 600;
    font-size: 14px;
  }

  .type {
    font-size: 12px;
    color: #666;
    text-transform: capitalize;
  }

  .price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: 4px;
  }

  .price {
    font-size: 16px;
    font-weight: 700;
    cursor: default;
    position: relative;
    color: #00629c;
  }

  .open-btn {
    background: #00629c;
    color: #fff;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    transition: background .18s, transform .15s ease;
    font-weight: 500;
    margin-top: auto;
  }

  .open-btn:hover {
    background: #00AFD9;
    transform: translateY(-1px);
  }

  .open-btn:active {
    transform: translateY(0);
  }

  .tooltip {
    position: absolute;
    top: 28px;
    left: 0;
    z-index: 20;
    background: #fff;
    border: 1px solid #ddd;
    padding: 8px;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
    width: 220px;
  }

  .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
  }

  .modal {
    background: #fff;
    width: 320px;
    border-radius: 8px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
    overflow: hidden;
  }

  .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px;
    border-bottom: 1px solid #eee;
  }

  .modal-body {
    padding: 12px;
  }

  .modal-footer {
    padding: 0;
    border-top: none;
    display: none;
  }

  .close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
  }

  .tooltip .sep {
    height: 1px;
    background: #eee;
    margin: 6px 0;
  }

  @media (max-width: 480px) {
    .grid {
      grid-template-columns: 1fr;
    }
  }
</style>
