<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Security Scan Result Model - Manages AI security scan results and admin decisions
 *
 * This model represents the results of AI-powered security scans for app store
 * applications, including vulnerability assessments, risk levels, and administrative
 * review decisions within the multi-tenant system.
 *
 * Key features:
 * - Security scan result storage and management
 * - Vulnerability assessment tracking
 * - Risk level classification and scoring
 * - Admin review workflow management
 * - Security recommendations storage
 * - Blocking reasons and decisions
 * - Scan status tracking
 * - Audit trail maintenance
 * - Multi-tenant security isolation
 *
 * Scan statuses:
 * - passed: Security scan passed without issues
 * - blocked: Security scan blocked due to vulnerabilities
 * - failed: Security scan failed to complete
 * - pending: Awaiting security scan
 *
 * Risk levels:
 * - low: Minimal security concerns
 * - medium: Moderate security risks
 * - high: Significant security vulnerabilities
 * - critical: Severe security issues requiring immediate attention
 *
 * Admin decisions:
 * - pending: Awaiting admin review
 * - approved: Admin approved despite security issues
 * - rejected: Admin rejected due to security concerns
 * - rescan: Admin requested new security scan
 *
 * The model provides:
 * - Comprehensive security scan result storage
 * - Vulnerability assessment tracking
 * - Risk level classification
 * - Admin review workflow management
 * - Security recommendations storage
 * - Audit trail maintenance
 * - Multi-tenant security isolation
 *
 * @package App\Models
 * @since 1.0.0
 */
class SecurityScanResult extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'app_store_app_id',
        'scan_status',
        'risk_level',
        'vulnerabilities_found',
        'security_score',
        'ai_analysis',
        'recommendations',
        'blocked_reasons',
        'scanned_at',
        'admin_reviewed_at',
        'admin_reviewed_by',
        'admin_override_reason',
        'admin_decision',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'vulnerabilities_found' => 'array',
        'recommendations' => 'array', 
        'blocked_reasons' => 'array',
        'scanned_at' => 'datetime',
        'admin_reviewed_at' => 'datetime',
    ];

    /**
     * Get the app store app that this scan belongs to
     *
     * This relationship provides access to the app store application
     * that was scanned, including app metadata and configuration.
     *
     * @return BelongsTo Relationship to AppStoreApp model
     */
    public function appStoreApp(): BelongsTo
    {
        return $this->belongsTo(AppStoreApp::class);
    }

    /**
     * Get the admin user who reviewed this scan
     *
     * This relationship provides access to the admin user who
     * reviewed the security scan result and made the decision.
     *
     * @return BelongsTo Relationship to User model (admin reviewer)
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_reviewed_by');
    }

    /**
     * Check if the security scan was blocked due to vulnerabilities
     *
     * This method determines whether the security scan resulted in
     * the app being blocked due to security vulnerabilities.
     *
     * @return bool True if the scan was blocked, false otherwise
     */
    public function isBlocked(): bool
    {
        return $this->scan_status === 'blocked';
    }

    /**
     * Check if the security scan passed without issues
     *
     * This method determines whether the security scan passed
     * without any security issues or vulnerabilities.
     *
     * @return bool True if the scan passed, false otherwise
     */
    public function isPassed(): bool
    {
        return $this->scan_status === 'passed';
    }

    /**
     * Check if the security scan failed to complete
     *
     * This method determines whether the security scan failed
     * to complete due to technical issues or errors.
     *
     * @return bool True if the scan failed, false otherwise
     */
    public function isFailed(): bool
    {
        return $this->scan_status === 'failed';
    }

    /**
     * Check if an admin has reviewed this security scan
     *
     * This method determines whether an administrator has
     * reviewed the security scan result and made a decision.
     *
     * @return bool True if admin has reviewed, false otherwise
     */
    public function isAdminReviewed(): bool
    {
        return !is_null($this->admin_reviewed_at);
    }

    /**
     * Check if admin approved the app despite security issues
     *
     * This method determines whether an administrator approved
     * the app despite security vulnerabilities, providing an override.
     *
     * @return bool True if admin approved, false otherwise
     */
    public function isAdminApproved(): bool
    {
        return $this->admin_decision === 'approved';
    }

    /**
     * Check if admin rejected the app due to security concerns
     *
     * This method determines whether an administrator rejected
     * the app due to security concerns or vulnerabilities.
     *
     * @return bool True if admin rejected, false otherwise
     */
    public function isAdminRejected(): bool
    {
        return $this->admin_decision === 'rejected';
    }

    /**
     * Get risk level color for UI
     */
    public function getRiskLevelColor(): string
    {
        return match($this->risk_level) {
            'critical' => 'red',
            'high' => 'orange', 
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray'
        };
    }

    /**
     * Get security score badge class
     */
    public function getSecurityScoreBadge(): string
    {
        if ($this->security_score >= 90) return 'badge-success';
        if ($this->security_score >= 70) return 'badge-warning';
        if ($this->security_score >= 50) return 'badge-orange';
        return 'badge-danger';
    }

    /**
     * Get vulnerability count by severity
     */
    public function getVulnerabilityCountBySeverity(): array
    {
        $counts = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
        
        foreach ($this->vulnerabilities_found as $vuln) {
            $severity = $vuln['severity'] ?? 'unknown';
            if (isset($counts[$severity])) {
                $counts[$severity]++;
            }
        }
        
        return $counts;
    }

    /**
     * Get most critical vulnerabilities
     */
    public function getCriticalVulnerabilities(): array
    {
        return array_filter($this->vulnerabilities_found, function($vuln) {
            return ($vuln['severity'] ?? '') === 'critical';
        });
    }

    /**
     * Get summary of scan results
     */
    public function getScanSummary(): array
    {
        $vulnCounts = $this->getVulnerabilityCountBySeverity();
        $totalVulns = array_sum($vulnCounts);
        
        return [
            'total_vulnerabilities' => $totalVulns,
            'vulnerability_counts' => $vulnCounts,
            'risk_level' => $this->risk_level,
            'security_score' => $this->security_score,
            'is_blocked' => $this->isBlocked(),
            'requires_admin_review' => $this->isBlocked() && !$this->isAdminReviewed(),
        ];
    }
}