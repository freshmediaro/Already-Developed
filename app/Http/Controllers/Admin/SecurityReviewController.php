<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppStoreApp;
use App\Models\SecurityScanResult;
use App\Services\AiSecurityScannerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Security Review Controller - Manages admin security review workflow
 *
 * This controller handles the administrative security review process for
 * applications that have been flagged by the AI security scanner. It provides
 * comprehensive review tools, decision management, and notification systems
 * for security administrators.
 *
 * Key features:
 * - Security review queue management
 * - Admin decision workflow
 * - Security scan result analysis
 * - Vulnerability assessment tools
 * - Approval and rejection workflows
 * - Rescan capabilities
 * - Notification management
 * - Security statistics and reporting
 * - Admin override capabilities
 * - Comprehensive logging
 *
 * Review workflow:
 * - Apps flagged by AI security scanner
 * - Admin review of security scan results
 * - Vulnerability assessment and analysis
 * - Decision making (approve/reject/rescan)
 * - Notification to app developers
 * - Status updates and tracking
 * - Audit trail maintenance
 *
 * Security decisions:
 * - approve: Approve app despite security issues (with justification)
 * - reject: Reject app due to security concerns
 * - rescan: Request new security scan
 * - pending: Awaiting admin review
 *
 * The controller provides:
 * - RESTful API endpoints for security review
 * - Comprehensive security analysis tools
 * - Admin decision workflow management
 * - Notification and communication systems
 * - Security statistics and reporting
 * - Audit trail and logging
 * - Multi-tenant security isolation
 *
 * @package App\Http\Controllers\Admin
 * @since 1.0.0
 */
class SecurityReviewController extends Controller
{
    /** @var AiSecurityScannerService Service for AI security scanning */
    protected AiSecurityScannerService $securityScanner;

    /**
     * Initialize the Security Review Controller with dependencies
     *
     * @param AiSecurityScannerService $securityScanner Service for AI security scanning
     */
    public function __construct(AiSecurityScannerService $securityScanner)
    {
        $this->securityScanner = $securityScanner;
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Get list of apps pending security review
     *
     * This method retrieves a paginated list of applications that have been
     * flagged by the AI security scanner and require administrative review.
     * It includes filtering options and security statistics.
     *
     * Query parameters:
     * - risk_level: Filter by security risk level
     * - date_from: Filter by submission date
     * - page: Pagination page number
     * - per_page: Number of items per page
     *
     * @param Request $request The HTTP request with filtering parameters
     * @return JsonResponse Response containing apps pending review and statistics
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AppStoreApp::whereHas('securityScans', function($q) {
                $q->where('scan_status', 'blocked')
                  ->where('admin_decision', 'pending');
            })
            ->with(['latestSecurityScan', 'categories'])
            ->orderBy('created_at', 'desc');

            // Filter by risk level
            if ($request->has('risk_level')) {
                $query->whereHas('latestSecurityScan', function($q) use ($request) {
                    $q->where('risk_level', $request->risk_level);
                });
            }

            // Filter by submission date
            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            $apps = $query->paginate(20);

            return response()->json([
                'apps' => $apps->items(),
                'pagination' => [
                    'current_page' => $apps->currentPage(),
                    'last_page' => $apps->lastPage(),
                    'per_page' => $apps->perPage(),
                    'total' => $apps->total(),
                ],
                'stats' => $this->getSecurityReviewStats()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch security review queue: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch review queue'], 500);
        }
    }

    /**
     * Get detailed security scan results for an app
     *
     * This method retrieves comprehensive security scan results for a specific
     * application, including vulnerability details, scan history, and
     * formatted security information for admin review.
     *
     * @param AppStoreApp $app The app store application to review
     * @return JsonResponse Response containing detailed security scan information
     */
    public function show(AppStoreApp $app): JsonResponse
    {
        try {
            $app->load(['latestSecurityScan', 'categories', 'securityScans' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(5);
            }]);

            if (!$app->latestSecurityScan) {
                return response()->json(['error' => 'No security scan found for this app'], 404);
            }

            return response()->json([
                'app' => $app,
                'security_scan' => $app->latestSecurityScan,
                'scan_history' => $app->securityScans,
                'vulnerability_details' => $this->formatVulnerabilityDetails($app->latestSecurityScan),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch app security details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch security details'], 500);
        }
    }

    /**
     * Approve app despite security issues (admin override)
     *
     * This method allows administrators to approve applications that have
     * been flagged by the security scanner, providing justification and
     * maintaining audit trails for security decisions.
     *
     * Request parameters:
     * - reason: Justification for approval despite security issues
     * - risk_assessment: Admin's risk assessment
     * - mitigation_plan: Plan to address security concerns
     *
     * @param Request $request The HTTP request with approval data
     * @param AppStoreApp $app The app store application to approve
     * @return JsonResponse Response indicating approval success or failure
     */
    public function approve(Request $request, AppStoreApp $app): JsonResponse
    {
        try {
            $request->validate([
                'override_reason' => 'required|string|min:10|max:1000',
                'conditions' => 'array',
                'conditions.*' => 'string',
            ]);

            $securityScan = $app->latestSecurityScan;
            
            if (!$securityScan || !$securityScan->isBlocked()) {
                return response()->json(['error' => 'App is not blocked or has no security scan'], 400);
            }

            // Update security scan with admin decision
            $securityScan->update([
                'admin_reviewed_at' => now(),
                'admin_reviewed_by' => auth()->id(),
                'admin_decision' => 'approved',
                'admin_override_reason' => $request->override_reason,
            ]);

            // Update app status to approved
            $app->update([
                'approval_status' => 'approved_with_conditions',
                'is_approved' => true,
                'is_published' => true,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'published_at' => now(),
                'security_scan_status' => 'admin_override',
                'security_scan_notes' => 'Approved by admin despite security issues: ' . $request->override_reason,
            ]);

            // Notify the tenant about approval
            $this->notifyTenantOfApproval($app, $securityScan, $request->override_reason);

            // Log the admin override
            Log::info('Admin approved app with security issues', [
                'app_id' => $app->id,
                'admin_id' => auth()->id(),
                'override_reason' => $request->override_reason,
                'risk_level' => $securityScan->risk_level,
                'security_score' => $securityScan->security_score,
            ]);

            return response()->json([
                'message' => 'App approved successfully despite security issues',
                'app' => $app,
                'security_scan' => $securityScan->getScanSummary(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to approve app: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to approve app'], 500);
        }
    }

    /**
     * Reject app due to security issues
     */
    public function reject(Request $request, AppStoreApp $app): JsonResponse
    {
        try {
            $request->validate([
                'rejection_reason' => 'required|string|min:10|max:1000',
                'feedback_for_developer' => 'string|max:2000',
            ]);

            $securityScan = $app->latestSecurityScan;
            
            if (!$securityScan || !$securityScan->isBlocked()) {
                return response()->json(['error' => 'App is not blocked or has no security scan'], 400);
            }

            // Update security scan with admin decision
            $securityScan->update([
                'admin_reviewed_at' => now(),
                'admin_reviewed_by' => auth()->id(),
                'admin_decision' => 'rejected',
                'admin_override_reason' => $request->rejection_reason,
            ]);

            // Update app status to permanently rejected
            $app->update([
                'approval_status' => 'security_rejected',
                'is_approved' => false,
                'is_published' => false,
                'security_scan_status' => 'failed',
                'security_scan_notes' => 'Rejected by admin: ' . $request->rejection_reason,
            ]);

            // Notify the tenant about rejection
            $this->notifyTenantOfRejection($app, $securityScan, $request->rejection_reason, $request->feedback_for_developer);

            // Log the admin rejection
            Log::info('Admin rejected app due to security issues', [
                'app_id' => $app->id,
                'admin_id' => auth()->id(),
                'rejection_reason' => $request->rejection_reason,
                'risk_level' => $securityScan->risk_level,
            ]);

            return response()->json([
                'message' => 'App rejected due to security issues',
                'app' => $app,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reject app: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to reject app'], 500);
        }
    }

    /**
     * Request manual security review or rescan
     */
    public function rescan(AppStoreApp $app): JsonResponse
    {
        try {
            if (!$app->package_path) {
                return response()->json(['error' => 'No package available for rescanning'], 400);
            }

            // Trigger new security scan
            $newScanResult = $this->securityScanner->scanAppPackage($app);

            Log::info('Admin triggered manual rescan', [
                'app_id' => $app->id,
                'admin_id' => auth()->id(),
                'previous_risk_level' => $app->latestSecurityScan?->risk_level,
                'new_risk_level' => $newScanResult->risk_level,
            ]);

            return response()->json([
                'message' => 'Security rescan completed',
                'scan_result' => $newScanResult->getScanSummary(),
                'app' => $app->fresh(['latestSecurityScan']),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to rescan app: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to rescan app'], 500);
        }
    }

    /**
     * Get security review statistics
     */
    private function getSecurityReviewStats(): array
    {
        $pending = SecurityScanResult::where('scan_status', 'blocked')
            ->where('admin_decision', 'pending')
            ->count();

        $criticalPending = SecurityScanResult::where('scan_status', 'blocked')
            ->where('admin_decision', 'pending')
            ->where('risk_level', 'critical')
            ->count();

        $approved = SecurityScanResult::where('admin_decision', 'approved')->count();
        $rejected = SecurityScanResult::where('admin_decision', 'rejected')->count();

        return [
            'pending_review' => $pending,
            'critical_pending' => $criticalPending,
            'total_approved' => $approved,
            'total_rejected' => $rejected,
        ];
    }

    /**
     * Format vulnerability details for admin review
     */
    private function formatVulnerabilityDetails(SecurityScanResult $scan): array
    {
        $vulnerabilities = $scan->vulnerabilities_found;
        $grouped = [];

        foreach ($vulnerabilities as $vuln) {
            $type = $vuln['type'] ?? 'unknown';
            $severity = $vuln['severity'] ?? 'unknown';
            
            if (!isset($grouped[$severity])) {
                $grouped[$severity] = [];
            }
            
            if (!isset($grouped[$severity][$type])) {
                $grouped[$severity][$type] = [];
            }
            
            $grouped[$severity][$type][] = $vuln;
        }

        // Sort by severity
        $severityOrder = ['critical', 'high', 'medium', 'low'];
        $sortedGrouped = [];
        
        foreach ($severityOrder as $severity) {
            if (isset($grouped[$severity])) {
                $sortedGrouped[$severity] = $grouped[$severity];
            }
        }

        return $sortedGrouped;
    }

    /**
     * Notify tenant about app approval
     */
    private function notifyTenantOfApproval(AppStoreApp $app, SecurityScanResult $scan, string $reason): void
    {
        try {
            $notification = [
                'type' => 'app_security_approved',
                'title' => 'App Approved After Security Review',
                'message' => "Your app '{$app->name}' has been approved by our security team after manual review.",
                'data' => [
                    'app_id' => $app->id,
                    'app_name' => $app->name,
                    'admin_reason' => $reason,
                ],
                'action_url' => "/app-store/my-apps/{$app->id}",
                'importance' => 'medium',
            ];

            \App\Models\Notification::create([
                'type' => $notification['type'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'data' => $notification['data'],
                'notifiable_type' => \App\Models\Team::class,
                'notifiable_id' => $app->submitted_by_team_id,
                'channels' => ['database', 'broadcast', 'email'],
                'importance' => $notification['importance'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to notify tenant of approval', ['app_id' => $app->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Notify tenant about app rejection
     */
    private function notifyTenantOfRejection(AppStoreApp $app, SecurityScanResult $scan, string $reason, ?string $feedback): void
    {
        try {
            $message = "Your app '{$app->name}' has been rejected due to security concerns after manual review.";
            if ($feedback) {
                $message .= " Developer feedback: " . $feedback;
            }

            $notification = [
                'type' => 'app_security_rejected',
                'title' => 'App Rejected After Security Review',
                'message' => $message,
                'data' => [
                    'app_id' => $app->id,
                    'app_name' => $app->name,
                    'rejection_reason' => $reason,
                    'developer_feedback' => $feedback,
                ],
                'action_url' => "/app-store/my-apps/{$app->id}/security-report",
                'importance' => 'high',
            ];

            \App\Models\Notification::create([
                'type' => $notification['type'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'data' => $notification['data'],
                'notifiable_type' => \App\Models\Team::class,
                'notifiable_id' => $app->submitted_by_team_id,
                'channels' => ['database', 'broadcast', 'email'],
                'importance' => $notification['importance'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to notify tenant of rejection', ['app_id' => $app->id, 'error' => $e->getMessage()]);
        }
    }
}