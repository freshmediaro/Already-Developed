<template>
  <div 
    v-show="show"
    class="wallet-sidebar"
    :class="{ 'wallet-sidebar-visible': show }"
  >
    <!-- Header -->
    <div class="wallet-sidebar-header">
      <button class="wallet-menu-toggle" aria-label="Menu">
        <i class="fas fa-bars"></i>
      </button>
      <div class="wallet-header-center">
        <i class="fas fa-wallet"></i>
        <span>Wallet</span>
      </div>
      <button class="panel-close-btn" @click="$emit('close')" aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Content -->
    <div class="wallet-sidebar-content">
      <!-- Quick Balance Cards -->
      <div class="balance-section">
        <div class="balance-cards">
          <div class="balance-card main">
            <div class="card-header">
              <span class="card-label">Main</span>
              <CreditCardIcon class="card-icon card-icon-blue" />
            </div>
            <div class="card-balance">
              ${{ formatCurrency(walletBalances.main) }}
            </div>
          </div>
          
          <div class="balance-card tokens">
            <div class="card-header">
              <span class="card-label">AI Tokens</span>
              <CpuChipIcon class="card-icon card-icon-purple" />
            </div>
            <div class="card-balance">
              {{ formatNumber(walletBalances.aiTokens) }}
            </div>
          </div>
          
          <div class="balance-card revenue">
            <div class="card-header">
              <span class="card-label">Revenue</span>
              <BanknotesIcon class="card-icon card-icon-green" />
            </div>
            <div class="card-balance">
              ${{ formatCurrency(walletBalances.revenue) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="actions-section">
        <div class="section-title">Quick Actions</div>
        <div class="action-buttons">
          <button @click="showAddFunds = true" class="action-btn primary">
            <PlusIcon class="action-icon" />
            <span>Add Funds</span>
          </button>
          
          <button @click="showBuyTokens = true" class="action-btn secondary">
            <CpuChipIcon class="action-icon" />
            <span>Buy Tokens</span>
          </button>
          
          <button @click="showWithdraw = true" class="action-btn tertiary">
            <ArrowUpOnSquareIcon class="action-icon" />
            <span>Withdraw</span>
          </button>
          
          <button @click="openWalletApp" class="action-btn ghost">
            <Cog6ToothIcon class="action-icon" />
            <span>Manage</span>
          </button>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="activity-section">
        <div class="section-title">Recent Activity</div>
        <div class="activity-list">
          <div v-for="transaction in recentTransactions.slice(0, 3)" :key="transaction.id" class="activity-item">
            <div class="activity-icon" :class="getActivityIconClass(transaction.type)">
              <component :is="getActivityIcon(transaction.type)" class="activity-icon-size" />
            </div>
            <div class="activity-details">
              <div class="activity-title">{{ transaction.description }}</div>
              <div class="activity-time">{{ formatTimeAgo(transaction.created_at) }}</div>
            </div>
            <div class="activity-amount" :class="transaction.amount >= 0 ? 'positive' : 'negative'">
              {{ transaction.amount >= 0 ? '+' : '' }}${{ formatCurrency(Math.abs(transaction.amount)) }}
            </div>
          </div>
          
          <div v-if="recentTransactions.length === 0" class="activity-empty">
            <div class="empty-icon">
              <CreditCardIcon class="w-8 h-8 text-gray-400" />
            </div>
            <p class="empty-text">No recent activity</p>
          </div>
        </div>
        
        <button v-if="recentTransactions.length > 3" @click="showAllTransactions" class="view-all-btn">
          View All Transactions
        </button>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-section">
      <div class="stat-item">
        <div class="stat-label">This Month</div>
        <div class="stat-value">${{ formatCurrency(monthlySpending) }}</div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Providers</div>
        <div class="stat-value">{{ enabledProvidersCount }}</div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Pending</div>
        <div class="stat-value">{{ pendingWithdrawalsCount }}</div>
      </div>
    </div>

    <!-- Modals -->
    <!-- Add Funds Quick Modal -->
    <div v-if="showAddFunds" class="tray-modal-overlay" @click="showAddFunds = false">
      <div class="tray-modal" @click.stop>
        <div class="modal-header">
          <h3>Add Funds</h3>
          <button @click="showAddFunds = false" class="modal-close">
            <XMarkIcon class="close-icon" />
          </button>
        </div>
        <div class="modal-body">
          <div class="quick-amounts">
            <button 
              v-for="amount in quickAmounts" 
              :key="amount"
              @click="selectedAmount = amount"
              :class="['quick-amount-btn', selectedAmount === amount && 'selected']"
            >
              ${{ amount }}
            </button>
          </div>
          <div class="custom-amount">
            <label class="amount-label">Custom Amount</label>
            <div class="amount-input-wrapper">
              <span class="currency-symbol">$</span>
              <input 
                v-model="selectedAmount" 
                type="number" 
                class="amount-input"
                min="0" 
                step="0.01"
                placeholder="0.00"
              />
            </div>
          </div>
          <div class="payment-method">
            <label class="method-label">Payment Method</label>
            <select v-model="selectedPaymentMethod" class="method-select">
              <option value="">Select method...</option>
              <option v-for="provider in enabledProviders" :key="provider.provider_name" :value="provider.provider_name">
                {{ provider.display_name }}
              </option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showAddFunds = false" class="btn-cancel">Cancel</button>
          <button @click="processAddFunds" :disabled="!canProcessAddFunds" class="btn-confirm">
            Add ${{ formatCurrency(selectedAmount) }}
          </button>
        </div>
      </div>
    </div>

    <!-- Buy Tokens Quick Modal -->
    <div v-if="showBuyTokens" class="tray-modal-overlay" @click="showBuyTokens = false">
      <div class="tray-modal" @click.stop>
        <div class="modal-header">
          <h3>Buy AI Tokens</h3>
          <button @click="showBuyTokens = false" class="modal-close">
            <XMarkIcon class="close-icon" />
          </button>
        </div>
        <div class="modal-body">
          <div class="token-packages">
            <div 
              v-for="package in tokenPackages.slice(0, 3)" 
              :key="package.id"
              @click="selectedTokenPackage = package.id"
              :class="['token-package-option', selectedTokenPackage === package.id && 'selected']"
            >
              <div class="package-header">
                <span class="package-name">{{ package.name }}</span>
                <span v-if="package.is_featured" class="featured-badge">Popular</span>
              </div>
              <div class="package-tokens">{{ formatNumber(package.token_amount) }} tokens</div>
              <div class="package-price">${{ formatCurrency(package.price) }}</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showBuyTokens = false" class="btn-cancel">Cancel</button>
          <button @click="processBuyTokens" :disabled="!selectedTokenPackage" class="btn-confirm">
            Purchase Tokens
          </button>
        </div>
      </div>
    </div>

    <!-- Withdraw Quick Modal -->
    <div v-if="showWithdraw" class="tray-modal-overlay" @click="showWithdraw = false">
      <div class="tray-modal" @click.stop>
        <div class="modal-header">
          <h3>Quick Withdraw</h3>
          <button @click="showWithdraw = false" class="modal-close">
            <XMarkIcon class="close-icon" />
          </button>
        </div>
        <div class="modal-body">
          <div class="available-balance">
            Available: ${{ formatCurrency(walletBalances.revenue) }}
          </div>
          <div class="withdrawal-amount">
            <label class="amount-label">Amount</label>
            <div class="amount-input-wrapper">
              <span class="currency-symbol">$</span>
              <input 
                v-model="withdrawAmount" 
                type="number" 
                class="amount-input"
                min="10" 
                :max="walletBalances.revenue"
                step="0.01"
                placeholder="0.00"
              />
            </div>
            <div class="fee-info">
              Fee: ${{ formatCurrency(withdrawAmount * 0.02) }} • 
              You'll receive: ${{ formatCurrency(withdrawAmount - (withdrawAmount * 0.02)) }}
            </div>
          </div>
          <div class="withdrawal-method">
            <label class="method-label">Method</label>
            <select v-model="withdrawalMethod" class="method-select">
              <option value="bank_transfer">Bank Transfer</option>
              <option value="paypal">PayPal</option>
              <option value="check">Check</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showWithdraw = false" class="btn-cancel">Cancel</button>
          <button @click="processWithdraw" :disabled="!canProcessWithdraw" class="btn-confirm">
            Request Withdrawal
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import {
  CreditCardIcon,
  BanknotesIcon,
  CpuChipIcon,
  PlusIcon,
  ArrowUpOnSquareIcon,
  Cog6ToothIcon,
  XMarkIcon,
  ListBulletIcon,
  MinusIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'WalletTrayPanel',
  components: {
    CreditCardIcon,
    BanknotesIcon,
    CpuChipIcon,
    PlusIcon,
    ArrowUpOnSquareIcon,
    Cog6ToothIcon,
    XMarkIcon,
    ListBulletIcon,
    MinusIcon
  },
  props: {
    show: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'open-wallet-app'],
  setup(props, { emit }) {
    // State
    const walletBalances = ref({
      main: 1250.75,
      aiTokens: 45000,
      revenue: 890.50
    })
    
    const recentTransactions = ref([
      {
        id: 1,
        description: 'AI Token Purchase',
        amount: -25.00,
        type: 'ai_tokens',
        created_at: new Date(Date.now() - 2 * 60 * 60 * 1000) // 2 hours ago
      },
      {
        id: 2,
        description: 'Sales Revenue',
        amount: 150.00,
        type: 'revenue',
        created_at: new Date(Date.now() - 5 * 60 * 60 * 1000) // 5 hours ago
      },
      {
        id: 3,
        description: 'Funds Added',
        amount: 100.00,
        type: 'deposit',
        created_at: new Date(Date.now() - 24 * 60 * 60 * 1000) // 1 day ago
      }
    ])
    
    const enabledProviders = ref([
      { provider_name: 'stripe', display_name: 'Stripe' },
      { provider_name: 'paypal', display_name: 'PayPal' }
    ])
    
    const tokenPackages = ref([
      { id: 1, name: 'Starter', token_amount: 10000, price: 15.00, is_featured: false },
      { id: 2, name: 'Pro', token_amount: 50000, price: 65.00, is_featured: true },
      { id: 3, name: 'Enterprise', token_amount: 100000, price: 120.00, is_featured: false }
    ])
    
    // Modal states
    const showAddFunds = ref(false)
    const showBuyTokens = ref(false)
    const showWithdraw = ref(false)
    
    // Form data
    const quickAmounts = [25, 50, 100, 250, 500]
    const selectedAmount = ref(100)
    const selectedPaymentMethod = ref('')
    const selectedTokenPackage = ref(null)
    const withdrawAmount = ref(100)
    const withdrawalMethod = ref('bank_transfer')
    
    // Computed
    const totalBalance = computed(() => {
      return walletBalances.value.main + walletBalances.value.revenue
    })
    
    const monthlySpending = computed(() => {
      return 425.50
    })
    
    const enabledProvidersCount = computed(() => {
      return enabledProviders.value.length
    })
    
    const pendingWithdrawalsCount = computed(() => {
      return 1
    })
    
    const canProcessAddFunds = computed(() => {
      return selectedAmount.value > 0 && selectedPaymentMethod.value
    })
    
    const canProcessWithdraw = computed(() => {
      return withdrawAmount.value >= 10 && withdrawAmount.value <= walletBalances.value.revenue
    })
    
    // Methods
    const formatCurrency = (amount) => {
      return Number(amount).toFixed(2)
    }
    
    const formatNumber = (number) => {
      return Number(number).toLocaleString()
    }
    
    const formatTimeAgo = (date) => {
      const now = new Date()
      const diff = now - new Date(date)
      const hours = Math.floor(diff / (1000 * 60 * 60))
      const days = Math.floor(hours / 24)
      
      if (days > 0) {
        return `${days}d ago`
      } else if (hours > 0) {
        return `${hours}h ago`
      } else {
        return 'Just now'
      }
    }
    
    const getActivityIcon = (type) => {
      const icons = {
        deposit: PlusIcon,
        withdrawal: MinusIcon,
        ai_tokens: CpuChipIcon,
        revenue: BanknotesIcon
      }
      return icons[type] || CreditCardIcon
    }
    
    const getActivityIconClass = (type) => {
      const classes = {
        deposit: 'positive',
        withdrawal: 'negative',
        ai_tokens: 'neutral',
        revenue: 'positive'
      }
      return classes[type] || 'neutral'
    }
    
    const openWalletApp = () => {
      // Emit event to open main wallet app
      emit('open-wallet-app')
    }

    const showAllTransactions = () => {
      // Emit event to open full wallet transactions
      openWalletApp()
    }
    
    const processAddFunds = async () => {
      // Process add funds
      console.log('Adding funds:', selectedAmount.value, selectedPaymentMethod.value)
      showAddFunds.value = false
      // Reset form
      selectedAmount.value = 100
      selectedPaymentMethod.value = ''
    }
    
    const processBuyTokens = async () => {
      // Process token purchase
      console.log('Buying tokens:', selectedTokenPackage.value)
      showBuyTokens.value = false
      // Reset form
      selectedTokenPackage.value = null
    }
    
    const processWithdraw = async () => {
      // Process withdrawal
      console.log('Withdrawing:', withdrawAmount.value, withdrawalMethod.value)
      showWithdraw.value = false
      // Reset form
      withdrawAmount.value = 100
      withdrawalMethod.value = 'bank_transfer'
    }
    
    const loadData = async () => {
      // Load wallet data from API
      // This would be replaced with actual API calls
    }
    
    // Initialize
    onMounted(() => {
      loadData()
    })
    
    return {
      // State
      walletBalances,
      recentTransactions,
      enabledProviders,
      tokenPackages,
      
      // Modal states
      showAddFunds,
      showBuyTokens,
      showWithdraw,
      
      // Form data
      quickAmounts,
      selectedAmount,
      selectedPaymentMethod,
      selectedTokenPackage,
      withdrawAmount,
      withdrawalMethod,
      
      // Computed
      totalBalance,
      monthlySpending,
      enabledProvidersCount,
      pendingWithdrawalsCount,
      canProcessAddFunds,
      canProcessWithdraw,
      
      // Methods
      formatCurrency,
      formatNumber,
      formatTimeAgo,
      getActivityIcon,
      getActivityIconClass,
      openWalletApp,
      showAllTransactions,
      processAddFunds,
      processBuyTokens,
      processWithdraw
    }
  }
}
</script>

<style scoped>
.wallet-tray-panel {
  width: 320px; /* w-80 */
  background-color: #ffffff; /* bg-white */
  border-radius: 12px; /* rounded-lg */
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15); /* shadow-2xl */
  border: 1px solid #e5e7eb; /* border border-gray-200 */
  overflow: hidden; /* overflow-hidden */
  font-family: 'Arial', sans-serif; /* font-sans */
}

/* Header */
.panel-header {
  background: linear-gradient(to right, #4f46e5, #3b82f6); /* bg-gradient-to-r from-blue-500 to-blue-600 */
  color: #ffffff; /* text-white */
  padding: 16px; /* p-4 */
}

.header-content {
  display: flex; /* flex */
  justify-content: space-between; /* justify-between */
  align-items: center; /* items-center */
}

.wallet-icon {
  width: 40px; /* w-10 */
  height: 40px; /* h-10 */
  background-color: rgba(255, 255, 255, 0.2); /* bg-white bg-opacity-20 */
  border-radius: 50%; /* rounded-full */
  display: flex; /* flex */
  align-items: center; /* items-center */
  justify-content: center; /* justify-center */
}

.wallet-icon-size {
  width: 24px; /* w-6 */
  height: 24px; /* h-6 */
}

.wallet-icon-color {
  color: #3b82f6; /* text-blue-600 */
}

.header-info {
  flex: 1; /* flex-1 */
  margin-left: 12px; /* ml-3 */
}

.wallet-title {
  font-size: 12px; /* text-sm */
  font-weight: 500; /* font-medium */
  opacity: 0.9; /* opacity-90 */
}

.total-balance {
  font-size: 24px; /* text-xl */
  font-weight: 700; /* font-bold */
}

.close-btn {
  padding: 4px; /* p-1 */
  border-radius: 8px; /* rounded-full */
  transition: background-color 0.2s ease; /* transition-colors */
}

.close-btn:hover {
  background-color: rgba(255, 255, 255, 0.2); /* hover:bg-white hover:bg-opacity-20 */
}

.close-icon {
  width: 20px; /* w-5 */
  height: 20px; /* h-5 */
}

/* Balance Section */
.balance-section {
  padding: 16px; /* p-4 */
  background-color: #f9fafb; /* bg-gray-50 */
}

.balance-cards {
  display: grid; /* grid */
  grid-template-columns: repeat(3, 1fr); /* grid-cols-3 */
  gap: 12px; /* gap-3 */
}

.balance-card {
  background-color: #ffffff; /* bg-white */
  border-radius: 8px; /* rounded-lg */
  padding: 12px; /* p-3 */
  text-align: center; /* text-center */
}

.card-header {
  display: flex; /* flex */
  justify-content: center; /* justify-center */
  align-items: center; /* items-center */
  gap: 4px; /* space-x-1 */
  margin-bottom: 8px; /* mb-2 */
}

.card-label {
  font-size: 12px; /* text-xs */
  font-weight: 500; /* font-medium */
  color: #4b5563; /* text-gray-600 */
}

.card-balance {
  font-size: 16px; /* text-sm */
  font-weight: 700; /* font-bold */
  color: #1f2937; /* text-gray-900 */
}

.card-icon {
  width: 16px; /* w-4 */
  height: 16px; /* h-4 */
}

.card-icon-blue {
  color: #3b82f6; /* text-blue-500 */
}

.card-icon-purple {
  color: #8b5cf6; /* text-purple-500 */
}

.card-icon-green {
  color: #10b981; /* text-green-500 */
}

/* Actions Section */
.actions-section {
  padding: 16px; /* p-4 */
}

.section-title {
  font-size: 14px; /* text-sm */
  font-weight: 600; /* font-semibold */
  color: #1f2937; /* text-gray-900 */
  margin-bottom: 12px; /* mb-3 */
}

.action-buttons {
  display: grid; /* grid */
  grid-template-columns: repeat(2, 1fr); /* grid-cols-2 */
  gap: 8px; /* gap-2 */
}

.action-btn {
  display: flex; /* flex */
  flex-direction: column; /* flex-col */
  align-items: center; /* items-center */
  justify-content: center; /* justify-center */
  padding: 12px; /* p-3 */
  border-radius: 8px; /* rounded-lg */
  font-size: 14px; /* text-sm */
  font-weight: 500; /* font-medium */
  transition: background-color 0.2s ease; /* transition-colors */
}

.action-btn span {
  margin-top: 4px; /* mt-1 */
}

.action-btn.primary {
  background-color: #4f46e5; /* bg-blue-500 */
  color: #ffffff; /* text-white */
}

.action-btn.primary:hover {
  background-color: #6366f1; /* hover:bg-blue-600 */
}

.action-btn.secondary {
  background-color: #8b5cf6; /* bg-purple-500 */
  color: #ffffff; /* text-white */
}

.action-btn.secondary:hover {
  background-color: #a78bfa; /* hover:bg-purple-600 */
}

.action-btn.tertiary {
  background-color: #10b981; /* bg-green-500 */
  color: #ffffff; /* text-white */
}

.action-btn.tertiary:hover {
  background-color: #059669; /* hover:bg-green-600 */
}

.action-btn.ghost {
  background-color: #f3f4f6; /* bg-gray-100 */
  color: #4b5563; /* text-gray-700 */
}

.action-btn.ghost:hover {
  background-color: #e5e7eb; /* hover:bg-gray-200 */
}

.action-icon {
  width: 20px; /* w-5 */
  height: 20px; /* h-5 */
}

/* Activity Section */
.activity-section {
  padding: 16px; /* p-4 */
  border-top: 1px solid #e5e7eb; /* border-t border-gray-100 */
}

.activity-list {
  display: flex; /* flex */
  flex-direction: column; /* flex-col */
  gap: 12px; /* space-y-3 */
  margin-bottom: 12px; /* mb-3 */
}

.activity-item {
  display: flex; /* flex */
  align-items: center; /* items-center */
  gap: 12px; /* space-x-3 */
}

.activity-icon {
  width: 32px; /* w-8 */
  height: 32px; /* h-8 */
  border-radius: 50%; /* rounded-full */
  display: flex; /* flex */
  align-items: center; /* items-center */
  justify-content: center; /* justify-center */
}

.activity-icon.positive {
  background-color: #ecfdf5; /* bg-green-100 */
  color: #065f46; /* text-green-600 */
}

.activity-icon.negative {
  background-color: #fef3f2; /* bg-red-100 */
  color: #991b1b; /* text-red-600 */
}

.activity-icon.neutral {
  background-color: #e0f2fe; /* bg-blue-100 */
  color: #1d4ed8; /* text-blue-600 */
}

.activity-icon-size {
  width: 16px; /* w-4 */
  height: 16px; /* h-4 */
}

.activity-details {
  flex: 1; /* flex-1 */
  min-width: 0; /* min-w-0 */
}

.activity-title {
  font-size: 14px; /* text-sm */
  font-weight: 500; /* font-medium */
  color: #1f2937; /* text-gray-900 */
  white-space: nowrap; /* truncate */
  overflow: hidden;
  text-overflow: ellipsis;
}

.activity-time {
  font-size: 12px; /* text-xs */
  color: #6b7280; /* text-gray-500 */
}

.activity-amount {
  font-size: 16px; /* text-sm */
  font-weight: 600; /* font-semibold */
}

.activity-amount.positive {
  color: #065f46; /* text-green-600 */
}

.activity-amount.negative {
  color: #991b1b; /* text-red-600 */
}

.activity-empty {
  text-align: center; /* text-center */
  padding: 24px; /* py-6 */
}

.empty-icon {
  display: flex; /* flex */
  justify-content: center; /* justify-center */
  margin-bottom: 8px; /* mb-2 */
}

.empty-icon-size {
  width: 32px; /* w-8 */
  height: 32px; /* h-8 */
}

.empty-icon-color {
  color: #9ca3af; /* text-gray-300 */
}

.empty-text {
  font-size: 14px; /* text-sm */
  color: #6b7280; /* text-gray-500 */
}

.view-all-btn {
  width: 100%; /* w-full */
  text-align: center; /* text-center */
  font-size: 14px; /* text-sm */
  color: #3b82f6; /* text-blue-600 */
  font-weight: 500; /* font-medium */
}

.view-all-btn:hover {
  color: #1d4ed8; /* hover:text-blue-700 */
}

/* Stats Section */
.stats-section {
  display: grid; /* grid */
  grid-template-columns: repeat(3, 1fr); /* grid-cols-3 */
  gap: 16px; /* gap-4 */
  padding: 16px; /* p-4 */
  background-color: #f9fafb; /* bg-gray-50 */
  border-top: 1px solid #e5e7eb; /* border-t border-gray-100 */
}

.stat-item {
  text-align: center; /* text-center */
}

.stat-label {
  font-size: 12px; /* text-xs */
  color: #6b7280; /* text-gray-600 */
}

.stat-value {
  font-size: 16px; /* text-sm */
  font-weight: 700; /* font-bold */
  color: #1f2937; /* text-gray-900 */
}

/* Modals */
.tray-modal-overlay {
  position: fixed; /* fixed */
  top: 0; /* inset-0 */
  left: 0; /* inset-0 */
  width: 100%; /* w-full */
  height: 100%; /* h-full */
  background-color: rgba(0, 0, 0, 0.5); /* bg-black bg-opacity-50 */
  display: flex; /* flex */
  align-items: center; /* items-center */
  justify-content: center; /* justify-center */
  z-index: 50; /* z-50 */
}

.tray-modal {
  width: 320px; /* w-80 */
  margin: 0 16px; /* mx-4 */
  background-color: #ffffff; /* bg-white */
  border-radius: 12px; /* rounded-lg */
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); /* shadow-xl */
}

.modal-header {
  display: flex; /* flex */
  justify-content: space-between; /* justify-between */
  align-items: center; /* items-center */
  padding: 16px; /* p-4 */
  border-bottom: 1px solid #e5e7eb; /* border-b border-gray-200 */
}

.modal-header h3 {
  font-size: 18px; /* text-lg */
  font-weight: 600; /* font-semibold */
  color: #1f2937; /* text-gray-900 */
}

.modal-close {
  padding: 4px; /* p-1 */
  color: #9ca3af; /* text-gray-400 */
  transition: color 0.2s ease; /* transition-colors */
}

.modal-close:hover {
  color: #4b5563; /* hover:text-gray-600 */
}

.modal-body {
  padding: 16px; /* p-4 */
  display: flex; /* flex */
  flex-direction: column; /* flex-col */
  gap: 16px; /* space-y-4 */
}

.modal-footer {
  display: flex; /* flex */
  justify-content: flex-end; /* justify-end */
  gap: 8px; /* space-x-3 */
  padding: 16px; /* p-4 */
  border-top: 1px solid #e5e7eb; /* border-t border-gray-200 */
}

.btn-cancel {
  padding: 8px 16px; /* px-4 py-2 */
  font-size: 14px; /* text-sm */
  font-weight: 500; /* font-medium */
  color: #4b5563; /* text-gray-700 */
  background-color: #e5e7eb; /* bg-gray-100 */
  border-radius: 8px; /* rounded-md */
  transition: background-color 0.2s ease; /* transition-colors */
}

.btn-cancel:hover {
  background-color: #d1d5db; /* hover:bg-gray-200 */
}

.btn-confirm {
  padding: 8px 16px; /* px-4 py-2 */
  font-size: 14px; /* text-sm */
  font-weight: 500; /* font-medium */
  color: #ffffff; /* text-white */
  background-color: #4f46e5; /* bg-blue-500 */
  border-radius: 8px; /* rounded-md */
  transition: background-color 0.2s ease; /* transition-colors */
}

.btn-confirm:hover {
  background-color: #6366f1; /* hover:bg-blue-600 */
}

.btn-confirm:disabled {
  background-color: #d1d5db; /* disabled:bg-gray-300 */
  cursor: not-allowed; /* disabled:cursor-not-allowed */
}

/* Add Funds Modal */
.quick-amounts {
  display: grid; /* grid */
  grid-template-columns: repeat(3, 1fr); /* grid-cols-3 */
  gap: 8px; /* gap-2 */
}

.quick-amount-btn {
  padding: 8px 12px; /* p-2 */
  font-size: 14px; /* text-sm */
  font-weight: 500; /* font-medium */
  color: #4b5563; /* text-gray-700 */
  background-color: #e5e7eb; /* bg-gray-100 */
  border-radius: 8px; /* rounded-md */
  transition: background-color 0.2s ease; /* transition-colors */
}

.quick-amount-btn:hover {
  background-color: #d1d5db; /* hover:bg-gray-200 */
}

.quick-amount-btn.selected {
  background-color: #4f46e5; /* bg-blue-500 */
  color: #ffffff; /* text-white */
}

.custom-amount, .payment-method, .withdrawal-amount, .withdrawal-method {
  display: flex; /* flex */
  flex-direction: column; /* flex-col */
  gap: 8px; /* space-y-2 */
}

.amount-label, .method-label {
  font-size: 14px; /* text-sm */
  font-weight: 500; /* font-medium */
  color: #4b5563; /* text-gray-700 */
}

.amount-input-wrapper {
  position: relative; /* relative */
}

.currency-symbol {
  position: absolute; /* absolute */
  left: 10px; /* left-3 */
  top: 50%; /* top-1/2 */
  transform: translateY(-50%); /* transform -translate-y-1/2 */
  color: #9ca3af; /* text-gray-500 */
  font-size: 14px; /* text-sm */
}

.amount-input {
  width: 100%; /* w-full */
  padding: 8px 10px 8px 30px; /* pl-8 pr-4 py-2 */
  border: 1px solid #d1d5db; /* border border-gray-300 */
  border-radius: 8px; /* rounded-md */
  font-size: 14px; /* text-sm */
  outline: none; /* outline-none */
  transition: border-color 0.2s ease, box-shadow 0.2s ease; /* focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 */
}

.amount-input:focus {
  border-color: #3b82f6; /* focus:ring-2 focus:ring-blue-500 focus:border-blue-500 */
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); /* focus:ring-2 focus:ring-blue-500 focus:border-blue-500 */
}

.method-select {
  width: 100%; /* w-full */
  padding: 8px 10px; /* px-3 py-2 */
  border: 1px solid #d1d5db; /* border border-gray-300 */
  border-radius: 8px; /* rounded-md */
  font-size: 14px; /* text-sm */
  outline: none; /* outline-none */
  transition: border-color 0.2s ease, box-shadow 0.2s ease; /* focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 */
}

.method-select:focus {
  border-color: #3b82f6; /* focus:ring-2 focus:ring-blue-500 focus:border-blue-500 */
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); /* focus:ring-2 focus:ring-blue-500 focus:border-blue-500 */
}

/* Buy Tokens Modal */
.token-packages {
  display: flex; /* flex */
  flex-direction: column; /* flex-col */
  gap: 8px; /* space-y-2 */
}

.token-package-option {
  padding: 12px; /* p-3 */
  border: 1px solid #e5e7eb; /* border border-gray-200 */
  border-radius: 8px; /* rounded-lg */
  cursor: pointer; /* cursor-pointer */
  transition: background-color 0.2s ease; /* transition-colors */
}

.token-package-option:hover {
  background-color: #f3f4f6; /* hover:bg-gray-50 */
}

.token-package-option.selected {
  border-color: #3b82f6; /* border-blue-500 */
  background-color: #e0f2fe; /* bg-blue-50 */
}

.package-header {
  display: flex; /* flex */
  justify-content: space-between; /* justify-between */
  align-items: center; /* items-center */
  margin-bottom: 4px; /* mb-1 */
}

.package-name {
  font-size: 14px; /* text-sm */
  font-weight: 500; /* font-medium */
  color: #1f2937; /* text-gray-900 */
}

.featured-badge {
  padding: 4px 8px; /* px-2 py-1 */
  font-size: 12px; /* text-xs */
  background-color: #fef3c7; /* bg-orange-100 */
  color: #d97706; /* text-orange-800 */
  border-radius: 6px; /* rounded */
}

.package-tokens {
  font-size: 14px; /* text-sm */
  color: #4b5563; /* text-gray-600 */
}

.package-price {
  font-size: 20px; /* text-lg */
  font-weight: 700; /* font-bold */
  color: #1d4ed8; /* text-blue-600 */
}

/* Withdraw Modal */
.available-balance {
  font-size: 14px; /* text-sm */
  color: #4b5563; /* text-gray-600 */
  margin-bottom: 12px; /* mb-3 */
}

.fee-info {
  font-size: 12px; /* text-xs */
  color: #6b7280; /* text-gray-500 */
  margin-top: 4px; /* mt-1 */
}
</style> 