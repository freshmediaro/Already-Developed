<?php

namespace App\Console\Commands;

use App\Services\Wallet\OmnipayProviderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Renew Payment Provider Subscriptions Command - Automated subscription renewal
 *
 * This command automatically renews expiring payment provider subscriptions
 * for tenant teams, ensuring continuous access to payment processing
 * capabilities. It handles subscription validation, payment processing,
 * and renewal confirmation.
 *
 * Key features:
 * - Automated subscription renewal processing
 * - Dry-run mode for previewing renewals
 * - Provider-specific renewal filtering
 * - Balance validation and fund checking
 * - Subscription status management
 * - Payment processing integration
 * - Comprehensive logging and reporting
 * - Error handling and recovery
 * - Multi-tenant subscription management
 *
 * Command options:
 * - --dry-run: Preview renewals without processing
 * - --provider: Renew specific provider only
 *
 * The command provides:
 * - Subscription renewal automation
 * - Balance validation
 * - Payment processing
 * - Status updates
 * - Comprehensive reporting
 * - Error handling
 *
 * @package App\Console\Commands
 * @since 1.0.0
 */
class RenewPaymentProviderSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:renew-provider-subscriptions 
                            {--dry-run : Run without making actual changes}
                            {--provider= : Renew specific provider only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Renew expiring payment provider subscriptions';

    /** @var OmnipayProviderService Service for payment provider management */
    protected $omnipayService;

    /**
     * Create a new command instance
     *
     * This constructor initializes the command with the Omnipay provider
     * service for payment processing and subscription management.
     *
     * @param OmnipayProviderService $omnipayService Service for payment provider operations
     */
    public function __construct(OmnipayProviderService $omnipayService)
    {
        parent::__construct();
        $this->omnipayService = $omnipayService;
    }

    /**
     * Execute the console command
     *
     * This method processes payment provider subscription renewals,
     * supporting both dry-run preview and actual renewal processing.
     *
     * @return int Command exit code (SUCCESS or FAILURE)
     */
    public function handle(): int
    {
        $this->info('Starting payment provider subscription renewals...');
        
        $dryRun = $this->option('dry-run');
        $specificProvider = $this->option('provider');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No actual renewals will be processed');
        }

        try {
            if ($dryRun) {
                $result = $this->previewRenewals($specificProvider);
                $this->displayPreview($result);
            } else {
                $result = $this->omnipayService->renewSubscriptions();
                $this->displayResults($result);
            }

            $this->info('Payment provider subscription renewal completed successfully.');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to process subscription renewals: ' . $e->getMessage());
            Log::error('Payment provider subscription renewal failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Preview renewals without processing them
     *
     * This method analyzes expiring subscriptions and determines which
     * can be renewed based on user balance and subscription status.
     *
     * @param string|null $specificProvider Optional provider name to filter by
     * @return array Preview data including renewal statistics
     */
    protected function previewRenewals(?string $specificProvider = null): array
    {
        $this->info('Previewing subscription renewals...');

        // Get expiring configs
        $query = \App\Models\Wallet\PaymentProviderConfig::where('subscription_status', 'active')
            ->where('subscription_expires_at', '<=', now()->addDay());

        if ($specificProvider) {
            $query->where('provider_name', $specificProvider);
        }

        $expiringConfigs = $query->get();

        $preview = [
            'total_expiring' => $expiringConfigs->count(),
            'can_renew' => 0,
            'insufficient_funds' => 0,
            'configs' => []
        ];

        foreach ($expiringConfigs as $config) {
            $user = $config->user;
            $wallet = $user->getMainWallet();
            $canAfford = $wallet->balance >= $config->monthly_fee;

            $configInfo = [
                'config_id' => $config->id,
                'user_id' => $config->user_id,
                'team_id' => $config->team_id,
                'provider_name' => $config->provider_name,
                'monthly_fee' => $config->monthly_fee,
                'expires_at' => $config->subscription_expires_at->toDateString(),
                'wallet_balance' => $wallet->balance,
                'can_afford' => $canAfford
            ];

            if ($canAfford) {
                $preview['can_renew']++;
            } else {
                $preview['insufficient_funds']++;
            }

            $preview['configs'][] = $configInfo;
        }

        return $preview;
    }

    /**
     * Display preview results
     */
    protected function displayPreview(array $preview): void
    {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Expiring', $preview['total_expiring']],
                ['Can Renew', $preview['can_renew']],
                ['Insufficient Funds', $preview['insufficient_funds']]
            ]
        );

        if ($preview['total_expiring'] > 0) {
            $this->info('Expiring Subscriptions Details:');
            
            $headers = ['Config ID', 'User ID', 'Team ID', 'Provider', 'Monthly Fee', 'Expires At', 'Wallet Balance', 'Can Afford'];
            $rows = [];

            foreach ($preview['configs'] as $config) {
                $rows[] = [
                    $config['config_id'],
                    $config['user_id'],
                    $config['team_id'] ?? 'N/A',
                    $config['provider_name'],
                    '$' . number_format($config['monthly_fee'], 2),
                    $config['expires_at'],
                    '$' . number_format($config['wallet_balance'], 2),
                    $config['can_afford'] ? '✓' : '✗'
                ];
            }

            $this->table($headers, $rows);
        }
    }

    /**
     * Display actual renewal results
     */
    protected function displayResults(array $result): void
    {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $result['total_processed']],
                ['Successfully Renewed', $result['renewed']],
                ['Failed Renewals', $result['failed']]
            ]
        );

        if ($result['renewed'] > 0) {
            $this->info("✓ {$result['renewed']} subscription(s) renewed successfully");
        }

        if ($result['failed'] > 0) {
            $this->warn("⚠ {$result['failed']} subscription(s) failed to renew");
        }

        // Log summary
        Log::info('Payment provider subscription renewals completed', [
            'total_processed' => $result['total_processed'],
            'renewed' => $result['renewed'],
            'failed' => $result['failed']
        ]);
    }
} 