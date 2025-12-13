<template>
  <div>
    <div class="form-row">
      <label>Vehicle Base Price</label>
      <input type="number" v-model.number="price" @input="calculate" />
    </div>

    <div class="form-row">
      <label>Vehicle Type</label>
      <select v-model="type" @change="calculate">
        <option value="common">Common</option>
        <option value="luxury">Luxury</option>
      </select>
    </div>

    <div class="fees">
      <h3>Fees</h3>
      <ul>
        <li>Basic buyer fee: {{ formatCurrency(fees.basic_buyer_fee) }}</li>
        <li>Seller special fee: {{ formatCurrency(fees.seller_special_fee) }}</li>
        <li>Association fee: {{ formatCurrency(fees.association_fee) }}</li>
        <li>Storage fee: {{ formatCurrency(fees.storage_fee) }}</li>
      </ul>
      <h3>Total: {{ formatCurrency(total) }}</h3>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { ref, onMounted } from 'vue'
  import axios from 'axios'

  // Type definitions
  interface Fees {
    basic_buyer_fee: number
    seller_special_fee: number
    association_fee: number
    storage_fee: number
  }

  interface CalculationResult {
    price: number
    type: string
    fees: Fees
    total: number
  }

  // Component state
  import { watch } from 'vue'

  // Accept initial props for reuse as a separate calculation tool
  const props = defineProps<{ initialPrice?: number; initialType?: string }>()

  const price = ref<number>(props.initialPrice ?? 1000)
  const type = ref<string>(props.initialType ?? 'common')
  const fees = ref<Fees>({
    basic_buyer_fee: 0,
    seller_special_fee: 0,
    association_fee: 0,
    storage_fee: 0,
  })
  const total = ref<number>(0)

  /**
   * Format a number as currency
   * @param val - The value to format
   * @returns Formatted currency string
   */
  function formatCurrency(val: number | undefined): string {
    return `$${Number(val || 0).toFixed(2)}`
  }

  /**
   * Calculate fees by calling the backend API
   */
  async function calculate(): Promise<void> {
    try {
      const base = import.meta.env.VITE_API_BASE || 'http://localhost:8000'
      const response = await axios.post<CalculationResult>(
        `${base}/api/calculate`,
        { price: price.value, type: type.value }
      )
      const data = response.data
      fees.value = data.fees
      total.value = data.total
    } catch (err) {
      if (axios.isAxiosError(err)) {
        console.error('Backend error:', err.response?.data || err.message)
      } else {
        console.error('Unexpected error:', err)
      }
    }
  }

  // Initialize calculation on component mount
  onMounted(() => {
    calculate()
  })

  // Recalculate if parent changes props
  watch(() => props.initialPrice, (v) => {
    if (typeof v === 'number') {
      price.value = v
      calculate()
    }
  })

  watch(() => props.initialType, (v) => {
    if (typeof v === 'string') {
      type.value = v
      calculate()
    }
  })
</script>

<style scoped>
  input,
  select {
    padding: 6px;
    margin-right: 8px;
  }

  .form-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 8px 0;
  }

  .fees {
    margin-top: 12px;
  }

  @media (max-width: 480px) {
    .form-row {
      flex-direction: column;
      align-items: flex-start;
    }
  }
</style>
