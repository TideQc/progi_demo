<template>
  <div>
    <header class="topbar">
      <div class="topbar-inner">
        <h1 class="brand">Bid Calculation Tool</h1>
        <nav class="menu">
          <button class="menu-btn" @click="toggleCalculator()">{{ isCalculatorVisible ? 'Close' : 'Calculator' }}</button>
        </nav>
      </div>
    </header>

    <main class="main-content">
      <div class="grid-container">
        <VehicleGrid @select-and-open="onSelectAndOpen" />
      </div>

      <transition name="slide">
        <section v-if="isCalculatorVisible" class="calculator" role="region" aria-label="Bid Calculator">
          <div class="calc-header">
            <h2>Bid Calculator</h2>
            <button class="close-btn" @click="toggleCalculator()" aria-label="Close calculator">×</button>
          </div>
          <BidForm
            :key="selected?.id || 'default'"
            :initialPrice="selected?.price ? Number(selected.price) : 1000"
            :initialType="selected?.type || 'common'"
          />
        </section>
      </transition>
    </main>
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import VehicleGrid from './components/VehicleGrid.vue'
  import BidForm from './components/BidForm.vue'

  const selected = ref<{ id?: number; name?: string; price?: number; type?: string } | null>(null)
  const isCalculatorVisible = ref(false)

  function toggleCalculator() {
    isCalculatorVisible.value = !isCalculatorVisible.value
  }

  function onSelectAndOpen(vehicle: any) {
    selected.value = vehicle
    isCalculatorVisible.value = true
  }
</script>

<style>
  body {
    font-family: Arial, Helvetica, sans-serif;
    margin: 0;
    padding: 0;
  }

  .topbar {
    background: #fff;
    border-bottom: 1px solid rgba(0, 0, 0, .08);
  }

  .topbar-inner {
    max-width: 100%;
    margin: 0;
    padding: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .brand {
    margin: 0;
    font-size: 18px;
    padding-left: 16px;
  }

  .menu {
    display: flex;
    gap: 8px;
    padding-right: 16px;
  }

  .menu-btn {
    background: #00629c;
    color: #fff;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    transition: background .18s ease;
  }

  .menu-btn:hover {
    background: #00AFD9;
  }

  .main-content {
    position: relative;
    height: calc(100vh - 80px);
    display: flex;
    overflow: hidden;
  }

  .grid-container {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 16px;
    box-sizing: border-box;
  }

  .calculator {
    position: absolute;
    top: 0;
    right: 0;
    width: 360px;
    height: 100%;
    background: #fff;
    border-left: 1px solid #eee;
    box-shadow: -2px 0 8px rgba(0, 0, 0, 0.22);
    overflow-y: auto;
    padding: 0;
    box-sizing: border-box;
    z-index: 10;
    display: flex;
    flex-direction: column;
  }

  .calc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-bottom: 1px solid #eee;
    flex-shrink: 0;
  }

  .calc-header h2 {
    margin: 0;
    font-size: 16px;
  }

  .close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .calculator > * {
    padding: 16px;
  }

  /* slide transition from right to left */
  .slide-enter-from,
  .slide-leave-to {
    transform: translateX(100%);
  }

  .slide-enter-active,
  .slide-leave-active {
    transition: transform 260ms cubic-bezier(.2, .8, .2, 1);
  }

  .slide-enter-to,
  .slide-leave-from {
    transform: translateX(0%);
  }

  @media (max-width: 960px) {
    .calculator {
      width: 100%;
    }
  }
</style>
