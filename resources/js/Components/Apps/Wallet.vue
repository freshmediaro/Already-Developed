<template>
  <div class="wallet-app flex flex-col h-full">
    <!-- Toolbar -->
    <div class="wallet-toolbar flex items-center justify-between bg-gray-100 px-4 py-2 border-b">
      <div class="flex items-center space-x-4">
        <h1 class="text-lg font-semibold text-gray-800">Wallet</h1>
        <div class="balance-display bg-green-100 px-3 py-1 rounded-full">
          <span class="text-sm font-medium text-green-800">
            Balance: ${{ formatCurrency(totalBalance) }}
          </span>
        </div>
      </div>
      
      <div class="flex items-center space-x-2">
        <button 
          @click="refreshData"
          :disabled="isLoading"
          class="btn-icon"
          title="Refresh"
        >
          <ArrowPathIcon :class="['w-4 h-4', isLoading && 'animate-spin']" />
        </button>
        
        <button 
          @click="showAddFunds = true"
          class="btn-primary text-sm px-3 py-1"
        >
          Add Funds
        </button>
        
        <button 
          @click="showWithdraw = true"
          class="btn-secondary text-sm px-3 py-1"
        >
          Withdraw
        </button>
      </div>
    </div>

    <div class="flex flex-1 overflow-hidden">
      <!-- Sidebar -->
      <div class="wallet-sidebar w-64 bg-gray-50 border-r flex flex-col">
        <nav class="flex-1 p-4">
          <ul class="space-y-2">
            <li>
              <button
                @click="activeSection = 'overview'"
                :class="[
                  'nav-item w-full',
                  activeSection === 'overview' && 'active'
                ]"
              >
                <CreditCardIcon class="w-5 h-5" />
                <span>Overview</span>
              </button>
            </li>
            
            <li>
              <button
                @click="activeSection = 'transactions'"
                :class="[
                  'nav-item w-full',
                  activeSection === 'transactions' && 'active'
                ]"
              >
                <ListBulletIcon class="w-5 h-5" />
                <span>Transactions</span>
              </button>
            </li>
            
            <li>
              <button
                @click="activeSection = 'ai-tokens'"
                :class="[
                  'nav-item w-full',
                  activeSection === 'ai-tokens' && 'active'
                ]"
              >
                <CpuChipIcon class="w-5 h-5" />
                <span>AI Tokens</span>
              </button>
            </li>
            
            <li>
              <button
                @click="activeSection = 'payment-providers'"
                :class="[
                  'nav-item w-full',
                  activeSection === 'payment-providers' && 'active'
                ]"
              >
                <BuildingOfficeIcon class="w-5 h-5" />
                <span>Payment Providers</span>
              </button>
            </li>
            
            <li>
              <button
                @click="activeSection = 'withdrawals'"
                :class="[
                  'nav-item w-full',
                  activeSection === 'withdrawals' && 'active'
                ]"
              >
                <BanknotesIcon class="w-5 h-5" />
                <span>Withdrawals</span>
              </button>
            </li>
            
            <li>
              <button
                @click="activeSection = 'analytics'"
                :class="[
                  'nav-item w-full',
                  activeSection === 'analytics' && 'active'
                ]"
              >
                <ChartBarIcon class="w-5 h-5" />
                <span>Analytics</span>
              </button>
            </li>
            
            <li>
              <button
                @click="activeSection = 'settings'"
                :class="[
                  'nav-item w-full',
                  activeSection === 'settings' && 'active'
                ]"
              >
                <CogIcon class="w-5 h-5" />
                <span>Settings</span>
              </button>
            </li>
          </ul>
        </nav>
      </div>

      <!-- Main Content -->
      <div class="flex-1 flex flex-col overflow-hidden">
        <div class="flex-1 p-6 overflow-y-auto">
          <!-- Overview Section -->
          <div v-if="activeSection === 'overview'" class="overview-section">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              <!-- Main Wallet -->
              <div class="wallet-card">
                <div class="wallet-card-header">
                  <CreditCardIcon class="w-6 h-6 text-blue-600" />
                  <h3>Main Wallet</h3>
                </div>
                <div class="wallet-card-balance">
                  ${{ formatCurrency(walletBalances.main) }}
                </div>
                <div class="wallet-card-actions">
                  <button @click="showAddFunds = true" class="btn-sm btn-primary">
                    Add Funds
                  </button>
                </div>
              </div>

              <!-- AI Tokens -->
              <div class="wallet-card">
                <div class="wallet-card-header">
                  <CpuChipIcon class="w-6 h-6 text-purple-600" />
                  <h3>AI Tokens</h3>
                </div>
                <div class="wallet-card-balance">
                  {{ formatNumber(walletBalances.aiTokens) }}
                </div>
                <div class="wallet-card-actions">
                  <button @click="showBuyTokens = true" class="btn-sm btn-primary">
                    Buy Tokens
                  </button>
                </div>
              </div>

              <!-- Revenue Wallet -->
              <div class="wallet-card">
                <div class="wallet-card-header">
                  <BanknotesIcon class="w-6 h-6 text-green-600" />
                  <h3>Revenue</h3>
                </div>
                <div class="wallet-card-balance">
                  ${{ formatCurrency(walletBalances.revenue) }}
                </div>
                <div class="wallet-card-actions">
                  <button @click="showWithdraw = true" class="btn-sm btn-secondary">
                    Withdraw
                  </button>
                </div>
              </div>

              <!-- Monthly Spending -->
              <div class="wallet-card">
                <div class="wallet-card-header">
                  <ChartBarIcon class="w-6 h-6 text-orange-600" />
                  <h3>Monthly Spend</h3>
                </div>
                <div class="wallet-card-balance">
                  ${{ formatCurrency(monthlySpending) }}
                </div>
                <div class="wallet-card-subtitle">
                  This month
                </div>
              </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
              <!-- Recent Transactions -->
              <div class="bg-white rounded-lg border p-6">
                <h3 class="text-lg font-semibold mb-4">Recent Transactions</h3>
                <div class="space-y-3">
                  <div v-for="transaction in recentTransactions.slice(0, 5)" :key="transaction.id" class="transaction-item">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-3">
                        <div :class="['transaction-icon', getTransactionIconClass(transaction.type)]">
                          <component :is="getTransactionIcon(transaction.type)" class="w-4 h-4" />
                        </div>
                        <div>
                          <div class="font-medium text-sm">{{ transaction.description }}</div>
                          <div class="text-xs text-gray-500">{{ formatDate(transaction.created_at) }}</div>
                        </div>
                      </div>
                      <div :class="['font-medium', transaction.amount >= 0 ? 'text-green-600' : 'text-red-600']">
                        {{ transaction.amount >= 0 ? '+' : '' }}${{ formatCurrency(Math.abs(transaction.amount)) }}
                      </div>
                    </div>
                  </div>
                </div>
                <button @click="activeSection = 'transactions'" class="mt-4 text-blue-600 hover:text-blue-800 text-sm font-medium">
                  View All Transactions →
                </button>
              </div>

              <!-- Payment Providers -->
              <div class="bg-white rounded-lg border p-6">
                <h3 class="text-lg font-semibold mb-4">Payment Providers</h3>
                <div class="space-y-3">
                  <div v-for="provider in enabledProviders.slice(0, 4)" :key="provider.provider_name" class="provider-item">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-3">
                        <div class="provider-icon">
                          <img :src="`/images/providers/${provider.provider_name}.svg`" :alt="provider.display_name" class="w-6 h-6" />
                        </div>
                        <div>
                          <div class="font-medium text-sm">{{ provider.display_name }}</div>
                          <div class="text-xs text-gray-500">{{ provider.currency }} • {{ provider.test_mode ? 'Test' : 'Live' }}</div>
                        </div>
                      </div>
                      <div class="flex items-center space-x-2">
                        <span v-if="provider.is_default" class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">
                          Default
                        </span>
                        <span :class="['px-2 py-1 text-xs rounded', provider.can_process_payments ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                          {{ provider.can_process_payments ? 'Active' : 'Inactive' }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <button @click="activeSection = 'payment-providers'" class="mt-4 text-blue-600 hover:text-blue-800 text-sm font-medium">
                  Manage Providers →
                </button>
              </div>
            </div>
          </div>

          <!-- Transactions Section -->
          <div v-if="activeSection === 'transactions'" class="transactions-section">
            <div class="section-header">
              <h2 class="text-2xl font-bold">Transactions</h2>
              <div class="flex items-center space-x-4">
                <select v-model="transactionFilter" class="form-select">
                  <option value="all">All Transactions</option>
                  <option value="deposits">Deposits</option>
                  <option value="withdrawals">Withdrawals</option>
                  <option value="ai_tokens">AI Tokens</option>
                  <option value="provider_fees">Provider Fees</option>
                </select>
                
                <select v-model="transactionPeriod" class="form-select">
                  <option value="7">Last 7 days</option>
                  <option value="30">Last 30 days</option>
                  <option value="90">Last 90 days</option>
                  <option value="365">Last year</option>
                </select>
              </div>
            </div>

            <div class="bg-white rounded-lg border">
              <div class="table-container">
                <table class="w-full">
                  <thead>
                    <tr class="table-header">
                      <th>Date</th>
                      <th>Description</th>
                      <th>Type</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="transaction in filteredTransactions" :key="transaction.id" class="table-row">
                      <td>{{ formatDate(transaction.created_at) }}</td>
                      <td>
                        <div class="font-medium">{{ transaction.description }}</div>
                        <div v-if="transaction.reference" class="text-xs text-gray-500">Ref: {{ transaction.reference }}</div>
                      </td>
                      <td>
                        <span class="transaction-type-badge" :class="getTransactionTypeClass(transaction.type)">
                          {{ formatTransactionType(transaction.type) }}
                        </span>
                      </td>
                      <td :class="[transaction.amount >= 0 ? 'text-green-600' : 'text-red-600', 'font-medium']">
                        {{ transaction.amount >= 0 ? '+' : '' }}${{ formatCurrency(Math.abs(transaction.amount)) }}
                      </td>
                      <td>
                        <span class="status-badge" :class="getStatusBadgeClass(transaction.status)">
                          {{ transaction.status }}
                        </span>
                      </td>
                      <td>
                        <button class="btn-sm btn-ghost">
                          View Details
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- AI Tokens Section -->
          <div v-if="activeSection === 'ai-tokens'" class="ai-tokens-section">
            <div class="section-header">
              <h2 class="text-2xl font-bold">AI Tokens</h2>
              <button @click="showBuyTokens = true" class="btn-primary">
                Buy Tokens
              </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
              <!-- Token Balance -->
              <div class="bg-white rounded-lg border p-6">
                <h3 class="text-lg font-semibold mb-2">Current Balance</h3>
                <div class="text-3xl font-bold text-purple-600">
                  {{ formatNumber(walletBalances.aiTokens) }}
                </div>
                <div class="text-sm text-gray-500 mt-1">
                  tokens available
                </div>
              </div>

              <!-- Usage This Month -->
              <div class="bg-white rounded-lg border p-6">
                <h3 class="text-lg font-semibold mb-2">Monthly Usage</h3>
                <div class="text-3xl font-bold text-blue-600">
                  {{ formatNumber(monthlyTokenUsage) }}
                </div>
                <div class="text-sm text-gray-500 mt-1">
                  tokens used this month
                </div>
              </div>

              <!-- Estimated Cost -->
              <div class="bg-white rounded-lg border p-6">
                <h3 class="text-lg font-semibold mb-2">Monthly Cost</h3>
                <div class="text-3xl font-bold text-green-600">
                  ${{ formatCurrency(monthlyTokenCost) }}
                </div>
                <div class="text-sm text-gray-500 mt-1">
                  estimated savings vs. pay-per-use
                </div>
              </div>
            </div>

            <!-- Token Packages -->
            <div class="bg-white rounded-lg border p-6">
              <h3 class="text-lg font-semibold mb-4">Available Token Packages</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="package in tokenPackages" :key="package.id" class="token-package">
                  <div class="token-package-header">
                    <h4 class="font-semibold">{{ package.name }}</h4>
                    <div v-if="package.is_featured" class="featured-badge">
                      Popular
                    </div>
                  </div>
                  <div class="token-package-tokens">
                    {{ formatNumber(package.token_amount) }} tokens
                  </div>
                  <div class="token-package-price">
                    ${{ formatCurrency(package.price) }}
                  </div>
                  <div class="token-package-per-token">
                    ${{ formatCurrency(package.price / package.token_amount * 1000) }} per 1k tokens
                  </div>
                  <div v-if="package.discount_percentage > 0" class="token-package-savings">
                    Save {{ package.discount_percentage }}%
                  </div>
                  <button @click="purchaseTokens(package)" class="btn-primary w-full mt-4">
                    Purchase
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Providers Section -->
          <div v-if="activeSection === 'payment-providers'" class="payment-providers-section">
            <div class="section-header">
              <h2 class="text-2xl font-bold">Payment Providers</h2>
              <button @click="showAddProvider = true" class="btn-primary">
                Add Provider
              </button>
            </div>

            <!-- Enabled Providers -->
            <div class="bg-white rounded-lg border p-6 mb-6">
              <h3 class="text-lg font-semibold mb-4">Enabled Providers</h3>
              <div class="space-y-4">
                <div v-for="provider in enabledProviders" :key="provider.provider_name" class="provider-card">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                      <img :src="`/images/providers/${provider.provider_name}.svg`" :alt="provider.display_name" class="w-8 h-8" />
                      <div>
                        <div class="font-semibold">{{ provider.display_name }}</div>
                        <div class="text-sm text-gray-500">{{ provider.currency }} • Monthly fee: {{ provider.monthly_fee }}</div>
                      </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                      <div class="flex items-center space-x-2">
                        <span v-if="provider.is_default" class="badge badge-blue">Default</span>
                        <span :class="['badge', provider.test_mode ? 'badge-yellow' : 'badge-green']">
                          {{ provider.test_mode ? 'Test Mode' : 'Live Mode' }}
                        </span>
                        <span :class="['badge', provider.can_process_payments ? 'badge-green' : 'badge-red']">
                          {{ provider.can_process_payments ? 'Active' : 'Inactive' }}
                        </span>
                      </div>
                      
                      <div class="flex items-center space-x-2">
                        <button v-if="!provider.is_default" @click="setDefaultProvider(provider.provider_name)" class="btn-sm btn-ghost">
                          Set Default
                        </button>
                        <button @click="testProvider(provider.provider_name)" class="btn-sm btn-ghost">
                          Test
                        </button>
                        <button @click="disableProvider(provider.provider_name)" class="btn-sm btn-danger">
                          Disable
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Available Providers -->
            <div class="bg-white rounded-lg border p-6">
              <h3 class="text-lg font-semibold mb-4">Available Providers</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="provider in availableProviders" :key="provider.name" class="available-provider-card">
                  <div class="flex items-center space-x-3 mb-3">
                    <img :src="`/images/providers/${provider.name}.svg`" :alt="provider.display_name" class="w-6 h-6" />
                    <div>
                      <div class="font-medium">{{ provider.display_name }}</div>
                      <div class="text-sm text-gray-500">Monthly fee: ${{ provider.monthly_fee }}</div>
                    </div>
                  </div>
                  <div class="text-sm text-gray-600 mb-3">
                    {{ provider.description }}
                  </div>
                  <div class="flex flex-wrap gap-2 mb-3">
                    <span v-for="feature in provider.features" :key="feature" class="feature-tag">
                      {{ feature }}
                    </span>
                  </div>
                  <button @click="enableProvider(provider)" class="btn-primary w-full">
                    Enable Provider
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Withdrawals Section -->
          <div v-if="activeSection === 'withdrawals'" class="withdrawals-section">
            <div class="section-header">
              <h2 class="text-2xl font-bold">Withdrawals</h2>
              <button @click="showWithdraw = true" class="btn-primary">
                Request Withdrawal
              </button>
            </div>

            <!-- Withdrawal Requests -->
            <div class="bg-white rounded-lg border">
              <div class="table-container">
                <table class="w-full">
                  <thead>
                    <tr class="table-header">
                      <th>Reference</th>
                      <th>Date</th>
                      <th>Amount</th>
                      <th>Method</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="withdrawal in withdrawalRequests" :key="withdrawal.id" class="table-row">
                      <td>
                        <div class="font-mono text-sm">{{ withdrawal.reference_number }}</div>
                      </td>
                      <td>{{ formatDate(withdrawal.requested_at) }}</td>
                      <td>
                        <div class="font-medium">${{ formatCurrency(withdrawal.requested_amount) }}</div>
                        <div class="text-xs text-gray-500">Fee: ${{ formatCurrency(withdrawal.withdrawal_fee) }}</div>
                      </td>
                      <td>{{ withdrawal.withdrawal_method }}</td>
                      <td>
                        <span class="status-badge" :class="getWithdrawalStatusClass(withdrawal.status)">
                          {{ withdrawal.status }}
                        </span>
                      </td>
                      <td>
                        <button v-if="withdrawal.status === 'pending'" @click="cancelWithdrawal(withdrawal.id)" class="btn-sm btn-danger">
                          Cancel
                        </button>
                        <button class="btn-sm btn-ghost">
                          Details
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Analytics Section -->
          <div v-if="activeSection === 'analytics'" class="analytics-section">
            <h2 class="text-2xl font-bold mb-6">Analytics</h2>
            
            <!-- Analytics charts and data would go here -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="bg-white rounded-lg border p-6">
                <h3 class="text-lg font-semibold mb-4">Spending Overview</h3>
                <!-- Chart component would go here -->
                <div class="h-64 bg-gray-50 rounded flex items-center justify-center text-gray-500">
                  Chart Component Placeholder
                </div>
              </div>
              
              <div class="bg-white rounded-lg border p-6">
                <h3 class="text-lg font-semibold mb-4">Token Usage Trends</h3>
                <!-- Chart component would go here -->
                <div class="h-64 bg-gray-50 rounded flex items-center justify-center text-gray-500">
                  Chart Component Placeholder
                </div>
              </div>
            </div>
          </div>

          <!-- Settings Section -->
          <div v-if="activeSection === 'settings'" class="settings-section">
            <h2 class="text-2xl font-bold mb-6">Wallet Settings</h2>
            
            <div class="space-y-6">
              <!-- Auto Top-up Settings -->
              <div class="bg-white rounded-lg border p-6">
                <h3 class="text-lg font-semibold mb-4">Auto Top-up</h3>
                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="font-medium">Enable Auto Top-up</div>
                      <div class="text-sm text-gray-500">Automatically add funds when balance is low</div>
                    </div>
                    <toggle v-model="walletSettings.auto_top_up" />
                  </div>
                  
                  <div v-if="walletSettings.auto_top_up" class="space-y-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">
                        Top-up when balance falls below
                      </label>
                      <div class="relative">
                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                        <input 
                          v-model="walletSettings.auto_top_up_threshold" 
                          type="number" 
                          class="form-input pl-8"
                          min="0"
                          step="0.01"
                        />
                      </div>
                    </div>
                    
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">
                        Top-up amount
                      </label>
                      <div class="relative">
                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                        <input 
                          v-model="walletSettings.auto_top_up_amount" 
                          type="number" 
                          class="form-input pl-8"
                          min="0"
                          step="0.01"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Notifications -->
              <div class="bg-white rounded-lg border p-6">
                <h3 class="text-lg font-semibold mb-4">Notifications</h3>
                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="font-medium">Email Notifications</div>
                      <div class="text-sm text-gray-500">Get notified about transactions and important updates</div>
                    </div>
                    <toggle v-model="walletSettings.email_notifications" />
                  </div>
                  
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="font-medium">SMS Notifications</div>
                      <div class="text-sm text-gray-500">Receive text messages for critical alerts</div>
                    </div>
                    <toggle v-model="walletSettings.sms_notifications" />
                  </div>
                </div>
              </div>

              <!-- Save Settings -->
              <div class="flex justify-end">
                <button @click="saveWalletSettings" class="btn-primary">
                  Save Settings
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <!-- Add Funds Modal -->
    <div v-if="showAddFunds" class="modal-overlay" @click="showAddFunds = false">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>Add Funds</h3>
          <button @click="showAddFunds = false" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <!-- Add funds form would go here -->
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                <input v-model="addFundsAmount" type="number" class="form-input pl-8" min="0" step="0.01" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
              <select v-model="selectedPaymentMethod" class="form-select">
                <option v-for="provider in enabledProviders" :key="provider.provider_name" :value="provider.provider_name">
                  {{ provider.display_name }}
                </option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showAddFunds = false" class="btn-secondary">Cancel</button>
          <button @click="processAddFunds" class="btn-primary">Add Funds</button>
        </div>
      </div>
    </div>

    <!-- Buy Tokens Modal -->
    <div v-if="showBuyTokens" class="modal-overlay" @click="showBuyTokens = false">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>Buy AI Tokens</h3>
          <button @click="showBuyTokens = false" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <!-- Token purchase form would go here -->
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Select Package</label>
              <div class="space-y-2">
                <div v-for="package in tokenPackages" :key="package.id" class="token-package-option">
                  <label class="flex items-center space-x-3 p-3 border rounded cursor-pointer hover:bg-gray-50">
                    <input v-model="selectedTokenPackage" :value="package.id" type="radio" class="form-radio" />
                    <div class="flex-1">
                      <div class="font-medium">{{ package.name }}</div>
                      <div class="text-sm text-gray-500">{{ formatNumber(package.token_amount) }} tokens for ${{ formatCurrency(package.price) }}</div>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showBuyTokens = false" class="btn-secondary">Cancel</button>
          <button @click="processBuyTokens" class="btn-primary">Purchase Tokens</button>
        </div>
      </div>
    </div>

    <!-- Withdraw Modal -->
    <div v-if="showWithdraw" class="modal-overlay" @click="showWithdraw = false">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>Request Withdrawal</h3>
          <button @click="showWithdraw = false" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <!-- Withdrawal form would go here -->
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                <input v-model="withdrawAmount" type="number" class="form-input pl-8" min="10" step="0.01" />
              </div>
              <div class="text-xs text-gray-500 mt-1">
                Minimum: $10.00 • Fee: {{ (withdrawAmount * 0.02).toFixed(2) }}
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Withdrawal Method</label>
              <select v-model="withdrawalMethod" class="form-select">
                <option value="bank_transfer">Bank Transfer</option>
                <option value="paypal">PayPal</option>
                <option value="check">Check</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showWithdraw = false" class="btn-secondary">Cancel</button>
          <button @click="processWithdraw" class="btn-primary">Request Withdrawal</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import {
  CreditCardIcon,
  ListBulletIcon,
  CpuChipIcon,
  BuildingOfficeIcon,
  BanknotesIcon,
  ChartBarIcon,
  CogIcon,
  ArrowPathIcon,
  PlusIcon,
  MinusIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'WalletApp',
  components: {
    CreditCardIcon,
    ListBulletIcon,
    CpuChipIcon,
    BuildingOfficeIcon,
    BanknotesIcon,
    ChartBarIcon,
    CogIcon,
    ArrowPathIcon,
    PlusIcon,
    MinusIcon
  },
  setup() {
    // State
    const activeSection = ref('overview')
    const isLoading = ref(false)
    
    // Wallet data
    const walletBalances = ref({
      main: 0,
      aiTokens: 0,
      revenue: 0
    })
    
    const recentTransactions = ref([])
    const enabledProviders = ref([])
    const availableProviders = ref([])
    const tokenPackages = ref([])
    const withdrawalRequests = ref([])
    const walletSettings = ref({
      auto_top_up: false,
      auto_top_up_threshold: 50,
      auto_top_up_amount: 100,
      email_notifications: true,
      sms_notifications: false
    })
    
    // Modal states
    const showAddFunds = ref(false)
    const showBuyTokens = ref(false)
    const showWithdraw = ref(false)
    const showAddProvider = ref(false)
    
    // Form data
    const addFundsAmount = ref(100)
    const selectedPaymentMethod = ref('')
    const selectedTokenPackage = ref(null)
    const withdrawAmount = ref(100)
    const withdrawalMethod = ref('bank_transfer')
    
    // Filters
    const transactionFilter = ref('all')
    const transactionPeriod = ref('30')
    
    // Computed
    const totalBalance = computed(() => {
      return walletBalances.value.main + walletBalances.value.revenue
    })
    
    const monthlySpending = computed(() => {
      // Calculate from transactions
      return 450.75
    })
    
    const monthlyTokenUsage = computed(() => {
      return 25000
    })
    
    const monthlyTokenCost = computed(() => {
      return 42.50
    })
    
    const filteredTransactions = computed(() => {
      // Filter transactions based on current filters
      return recentTransactions.value
    })
    
    // Methods
    const refreshData = async () => {
      isLoading.value = true
      try {
        await Promise.all([
          loadWalletBalances(),
          loadTransactions(),
          loadProviders(),
          loadTokenPackages(),
          loadWithdrawalRequests(),
          loadWalletSettings()
        ])
      } finally {
        isLoading.value = false
      }
    }
    
    const loadWalletBalances = async () => {
      // Load wallet balances from API
    }
    
    const loadTransactions = async () => {
      // Load transaction history
    }
    
    const loadProviders = async () => {
      // Load payment providers
    }
    
    const loadTokenPackages = async () => {
      // Load AI token packages
    }
    
    const loadWithdrawalRequests = async () => {
      // Load withdrawal requests
    }
    
    const loadWalletSettings = async () => {
      // Load wallet settings
    }
    
    const saveWalletSettings = async () => {
      // Save wallet settings
    }
    
    const processAddFunds = async () => {
      // Process add funds
      showAddFunds.value = false
    }
    
    const processBuyTokens = async () => {
      // Process token purchase
      showBuyTokens.value = false
    }
    
    const processWithdraw = async () => {
      // Process withdrawal request
      showWithdraw.value = false
    }
    
    const setDefaultProvider = async (providerName) => {
      // Set default payment provider
    }
    
    const testProvider = async (providerName) => {
      // Test payment provider connection
    }
    
    const disableProvider = async (providerName) => {
      // Disable payment provider
    }
    
    const enableProvider = async (provider) => {
      // Enable payment provider
    }
    
    const purchaseTokens = async (package) => {
      selectedTokenPackage.value = package.id
      showBuyTokens.value = true
    }
    
    const cancelWithdrawal = async (withdrawalId) => {
      // Cancel withdrawal request
    }
    
    // Helper methods
    const formatCurrency = (amount) => {
      return Number(amount).toFixed(2)
    }
    
    const formatNumber = (number) => {
      return Number(number).toLocaleString()
    }
    
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }
    
    const getTransactionIcon = (type) => {
      const icons = {
        deposit: PlusIcon,
        withdrawal: MinusIcon,
        ai_tokens: CpuChipIcon,
        provider_fee: BuildingOfficeIcon
      }
      return icons[type] || CreditCardIcon
    }
    
    const getTransactionIconClass = (type) => {
      const classes = {
        deposit: 'bg-green-100 text-green-600',
        withdrawal: 'bg-red-100 text-red-600',
        ai_tokens: 'bg-purple-100 text-purple-600',
        provider_fee: 'bg-blue-100 text-blue-600'
      }
      return classes[type] || 'bg-gray-100 text-gray-600'
    }
    
    const getTransactionTypeClass = (type) => {
      const classes = {
        deposit: 'bg-green-100 text-green-800',
        withdrawal: 'bg-red-100 text-red-800',
        ai_tokens: 'bg-purple-100 text-purple-800',
        provider_fee: 'bg-blue-100 text-blue-800'
      }
      return classes[type] || 'bg-gray-100 text-gray-800'
    }
    
    const formatTransactionType = (type) => {
      const labels = {
        deposit: 'Deposit',
        withdrawal: 'Withdrawal',
        ai_tokens: 'AI Tokens',
        provider_fee: 'Provider Fee'
      }
      return labels[type] || type
    }
    
    const getStatusBadgeClass = (status) => {
      const classes = {
        completed: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        failed: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }
    
    const getWithdrawalStatusClass = (status) => {
      const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-blue-100 text-blue-800',
        processing: 'bg-purple-100 text-purple-800',
        completed: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }
    
    // Initialize
    onMounted(() => {
      refreshData()
    })
    
    return {
      // State
      activeSection,
      isLoading,
      walletBalances,
      recentTransactions,
      enabledProviders,
      availableProviders,
      tokenPackages,
      withdrawalRequests,
      walletSettings,
      
      // Modal states
      showAddFunds,
      showBuyTokens,
      showWithdraw,
      showAddProvider,
      
      // Form data
      addFundsAmount,
      selectedPaymentMethod,
      selectedTokenPackage,
      withdrawAmount,
      withdrawalMethod,
      
      // Filters
      transactionFilter,
      transactionPeriod,
      
      // Computed
      totalBalance,
      monthlySpending,
      monthlyTokenUsage,
      monthlyTokenCost,
      filteredTransactions,
      
      // Methods
      refreshData,
      saveWalletSettings,
      processAddFunds,
      processBuyTokens,
      processWithdraw,
      setDefaultProvider,
      testProvider,
      disableProvider,
      enableProvider,
      purchaseTokens,
      cancelWithdrawal,
      
      // Helpers
      formatCurrency,
      formatNumber,
      formatDate,
      getTransactionIcon,
      getTransactionIconClass,
      getTransactionTypeClass,
      formatTransactionType,
      getStatusBadgeClass,
      getWithdrawalStatusClass
    }
  }
}
</script>

<style scoped>
.wallet-app {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.wallet-toolbar {
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.balance-display {
  border: 1px solid #bbf7d0;
}

.wallet-sidebar {
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  border-radius: 8px;
  transition: all 0.2s ease;
  cursor: pointer;
  border: none;
  background: none;
  text-decoration: none;
}

.nav-item:hover {
  background-color: #f3f4f6;
}

.nav-item.active {
  background-color: #dbeafe;
  color: #1d4ed8;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.wallet-card {
  background-color: #ffffff;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  padding: 24px;
}

.wallet-card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.wallet-card-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.wallet-card-balance {
  font-size: 30px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 16px;
}

.wallet-card-subtitle {
  font-size: 14px;
  color: #6b7280;
  margin-top: 4px;
}

.wallet-card-actions {
  display: flex;
  gap: 8px;
}

.transaction-item {
  border-bottom: 1px solid #f3f4f6;
  padding-bottom: 12px;
}

.transaction-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.transaction-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.provider-item, .provider-card {
  border-bottom: 1px solid #f3f4f6;
  padding-bottom: 16px;
}

.provider-item:last-child, .provider-card:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.provider-icon {
  width: 32px;
  height: 32px;
  background-color: #f3f4f6;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.token-package {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 24px;
  position: relative;
}

.token-package-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.featured-badge {
  background-color: #fed7aa;
  color: #9a3412;
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 4px;
}

.token-package-tokens {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 8px;
}

.token-package-price {
  font-size: 20px;
  font-weight: 600;
  color: #2563eb;
  margin-bottom: 4px;
}

.token-package-per-token {
  font-size: 14px;
  color: #6b7280;
  margin-bottom: 8px;
}

.token-package-savings {
  font-size: 14px;
  color: #16a34a;
  font-weight: 500;
}

.available-provider-card {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
}

.feature-tag {
  background-color: #dbeafe;
  color: #1e40af;
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 4px;
}

.table-container {
  overflow-x: auto;
}

.table-header th {
  padding: 12px 24px;
  text-align: left;
  font-size: 12px;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background-color: #f9fafb;
}

.table-row td {
  padding: 16px 24px;
  white-space: nowrap;
  font-size: 14px;
  color: #111827;
  border-bottom: 1px solid #e5e7eb;
}

.transaction-type-badge {
  display: inline-flex;
  padding: 2px 8px;
  font-size: 12px;
  border-radius: 4px;
}

.status-badge {
  display: inline-flex;
  padding: 2px 8px;
  font-size: 12px;
  border-radius: 4px;
}

.badge {
  display: inline-flex;
  padding: 2px 8px;
  font-size: 12px;
  border-radius: 4px;
}

.badge-blue {
  background-color: #dbeafe;
  color: #1e40af;
}

.badge-green {
  background-color: #dcfce7;
  color: #166534;
}

.badge-yellow {
  background-color: #fef3c7;
  color: #92400e;
}

.badge-red {
  background-color: #fecaca;
  color: #991b1b;
}

.btn-icon {
  padding: 8px;
  color: #4b5563;
  border-radius: 4px;
  transition: all 0.2s ease;
  border: none;
  background: none;
  cursor: pointer;
}

.btn-icon:hover {
  color: #1f2937;
  background-color: #f3f4f6;
}

.btn-primary {
  background-color: #2563eb;
  color: #ffffff;
  padding: 8px 16px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
  border: none;
  cursor: pointer;
}

.btn-primary:hover {
  background-color: #1d4ed8;
}

.btn-secondary {
  background-color: #e5e7eb;
  color: #1f2937;
  padding: 8px 16px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
  border: none;
  cursor: pointer;
}

.btn-secondary:hover {
  background-color: #d1d5db;
}

.btn-danger {
  background-color: #dc2626;
  color: #ffffff;
  padding: 8px 16px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
  border: none;
  cursor: pointer;
}

.btn-danger:hover {
  background-color: #b91c1c;
}

.btn-ghost {
  color: #4b5563;
  padding: 8px 16px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
  border: none;
  background: none;
  cursor: pointer;
}

.btn-ghost:hover {
  background-color: #f3f4f6;
}

.btn-sm {
  font-size: 14px;
  padding: 4px 8px;
}

.form-input {
  display: block;
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  transition: border-color 0.2s ease;
}

.form-input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-select {
  display: block;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  transition: border-color 0.2s ease;
}

.form-select:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-radio {
  height: 16px;
  width: 16px;
  color: #2563eb;
  border: 1px solid #d1d5db;
}

.form-radio:focus {
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.modal-overlay {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}

.modal {
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  max-width: 448px;
  width: 100%;
  margin: 0 16px;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  font-size: 18px;
  font-weight: 600;
}

.modal-close {
  color: #9ca3af;
  font-size: 24px;
  line-height: 1;
  cursor: pointer;
  transition: color 0.2s ease;
}

.modal-close:hover {
  color: #4b5563;
}

.modal-body {
  padding: 24px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 24px;
  border-top: 1px solid #e5e7eb;
}

.token-package-option {
  transition: background-color 0.2s ease;
}

.token-package-option:hover {
  background-color: #f9fafb;
}
</style> 