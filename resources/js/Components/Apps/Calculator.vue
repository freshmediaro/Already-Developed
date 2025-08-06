<template>
  <div class="calculator-app">
    <div class="calculator-display">
      <div class="display-screen">
        {{ displayValue || '0' }}
      </div>
    </div>
    
    <div class="calculator-buttons">
      <!-- First Row -->
      <button @click="clearAll" class="btn-clear">AC</button>
      <button @click="clearEntry" class="btn-clear">CE</button>
      <button @click="deleteLastDigit" class="btn-operator">⌫</button>
      <button @click="inputOperator('/')" class="btn-operator">÷</button>
      
      <!-- Second Row -->
      <button @click="inputNumber('7')" class="btn-number">7</button>
      <button @click="inputNumber('8')" class="btn-number">8</button>
      <button @click="inputNumber('9')" class="btn-number">9</button>
      <button @click="inputOperator('*')" class="btn-operator">×</button>
      
      <!-- Third Row -->
      <button @click="inputNumber('4')" class="btn-number">4</button>
      <button @click="inputNumber('5')" class="btn-number">5</button>
      <button @click="inputNumber('6')" class="btn-number">6</button>
      <button @click="inputOperator('-')" class="btn-operator">−</button>
      
      <!-- Fourth Row -->
      <button @click="inputNumber('1')" class="btn-number">1</button>
      <button @click="inputNumber('2')" class="btn-number">2</button>
      <button @click="inputNumber('3')" class="btn-number">3</button>
      <button @click="inputOperator('+')" class="btn-operator btn-plus">+</button>
      
      <!-- Fifth Row -->
      <button @click="inputNumber('0')" class="btn-number btn-zero">0</button>
      <button @click="inputDecimal" class="btn-number">.</button>
      <button @click="calculate" class="btn-equals">=</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAppMetadata } from '@/composables/useAppMetadata'

interface Props {
  windowId: string
  windowData?: Record<string, any>
}

const props = defineProps<Props>()

// Set app metadata
const { updateMetadata } = useAppMetadata()
updateMetadata({
  title: 'Calculator',
  icon: 'fas fa-calculator',
  iconClass: 'gray-icon'
})

// Example: Update title dynamically based on calculation mode
const updateCalculatorTitle = (mode: string = 'Standard') => {
  updateMetadata({
    title: `Calculator - ${mode}`,
    subtitle: mode === 'Scientific' ? 'Advanced calculations' : 'Basic calculations'
  })
}

const emit = defineEmits<{
  updateTitle: [title: string]
  updateData: [data: Record<string, unknown>]
}>()

// Calculator state
const displayValue = ref('0')
const previousValue = ref<number | null>(null)
const currentOperator = ref<string | null>(null)
const waitingForOperand = ref(false)
const shouldResetDisplay = ref(false)

// Computed
const currentValue = computed(() => parseFloat(displayValue.value || '0') || 0)

// Methods
const inputNumber = (digit: string) => {
  if (waitingForOperand.value || shouldResetDisplay.value) {
    displayValue.value = digit
    waitingForOperand.value = false
    shouldResetDisplay.value = false
  } else {
    displayValue.value = displayValue.value === '0' ? digit : displayValue.value + digit
  }
  
  updateWindowData()
}

const inputDecimal = () => {
  if (waitingForOperand.value || shouldResetDisplay.value) {
    displayValue.value = '0.'
    waitingForOperand.value = false
    shouldResetDisplay.value = false
  } else if ((displayValue.value || '').indexOf('.') === -1) {
    displayValue.value = (displayValue.value || '') + '.'
  }
  
  updateWindowData()
}

const inputOperator = (nextOperator: string) => {
  const inputValue = currentValue.value

  if (previousValue.value === null) {
    previousValue.value = inputValue
  } else if (currentOperator.value) {
    const currentValueNum = previousValue.value || 0
    const newValue = performCalculation(currentValueNum, inputValue, currentOperator.value)
    
    displayValue.value = String(newValue)
    previousValue.value = newValue
  }

  waitingForOperand.value = true
  currentOperator.value = nextOperator
  
  updateWindowData()
}

const calculate = () => {
  if (currentOperator.value && previousValue.value !== null) {
    const inputValue = currentValue.value || 0
    const newValue = performCalculation(previousValue.value || 0, inputValue, currentOperator.value)
    
    displayValue.value = String(newValue)
    previousValue.value = null
    currentOperator.value = null
    waitingForOperand.value = false
    shouldResetDisplay.value = true
    
    updateWindowData()
  }
}

const performCalculation = (firstValue: number, secondValue: number, operator: string): number => {
  switch (operator) {
    case '+':
      return firstValue + secondValue
    case '-':
      return firstValue - secondValue
    case '*':
      return firstValue * secondValue
    case '/':
      return secondValue !== 0 ? firstValue / secondValue : 0
    default:
      return secondValue
  }
}

const clearAll = () => {
  displayValue.value = '0'
  previousValue.value = null
  currentOperator.value = null
  waitingForOperand.value = false
  shouldResetDisplay.value = false
  
  updateWindowData()
}

const clearEntry = () => {
  displayValue.value = '0'
  shouldResetDisplay.value = false
  
  updateWindowData()
}

const deleteLastDigit = () => {
  if ((displayValue.value || '').length > 1) {
    displayValue.value = (displayValue.value || '').slice(0, -1)
  } else {
    displayValue.value = '0'
  }
  
  updateWindowData()
}

const updateWindowData = () => {
  emit('updateData', {
    displayValue: displayValue.value,
    previousValue: previousValue.value,
    currentOperator: currentOperator.value,
    waitingForOperand: waitingForOperand.value
  })
}

// Keyboard support
const handleKeydown = (event: KeyboardEvent) => {
  const { key } = event
  
  if (/[0-9]/.test(key)) {
    event.preventDefault()
    inputNumber(key)
  } else if (key === '.') {
    event.preventDefault()
    inputDecimal()
  } else if (['+', '-', '*', '/'].includes(key)) {
    event.preventDefault()
    inputOperator(key)
  } else if (key === 'Enter' || key === '=') {
    event.preventDefault()
    calculate()
  } else if (key === 'Escape') {
    event.preventDefault()
    clearAll()
  } else if (key === 'Backspace') {
    event.preventDefault()
    deleteLastDigit()
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
  emit('updateTitle', 'Calculator')
  
  // Restore state if available
  if (props.windowData) {
    displayValue.value = props.windowData.displayValue || '0'
    previousValue.value = props.windowData.previousValue || null
    currentOperator.value = props.windowData.currentOperator || null
    waitingForOperand.value = props.windowData.waitingForOperand || false
  }
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.calculator-app {
  width: 100%;
  height: 100%;
  background-color: #111827;
  color: #ffffff;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-width: 300px;
  margin: 0 auto;
}

.calculator-display {
  background-color: #000000;
  padding: 16px;
  flex-shrink: 0;
  height: 80px;
}

.display-screen {
  text-align: right;
  font-size: 30px;
  font-family: 'Courier New', monospace;
  line-height: 48px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.calculator-buttons {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 4px;
  padding: 8px;
  grid-template-rows: repeat(5, 1fr);
}

.calculator-buttons button {
  border-radius: 4px;
  font-weight: 600;
  font-size: 18px;
  transition: all 0.15s ease;
  border: none;
  outline: none;
  cursor: pointer;
}

.calculator-buttons button:hover {
  opacity: 0.8;
}

.calculator-buttons button:active {
  transform: scale(0.95);
}

.btn-number {
  background-color: #374151;
  color: #ffffff;
}

.btn-operator {
  background-color: #f97316;
  color: #ffffff;
}

.btn-clear {
  background-color: #6b7280;
  color: #ffffff;
}

.btn-equals {
  background-color: #f97316;
  color: #ffffff;
}

.btn-zero {
  grid-column: span 2;
}

.btn-plus {
  grid-row: span 2;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .calculator-app {
    max-width: 100%;
  }
  
  .calculator-buttons button {
    font-size: 16px;
    min-height: 60px;
  }
  
  .display-screen {
    font-size: 24px;
  }
}
</style> 