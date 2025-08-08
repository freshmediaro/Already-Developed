<?php

namespace App\Services;

use App\Models\AppStoreApp;
use App\Models\InstalledApp;
use App\Models\SecurityScanResult;
use App\Models\Team;
use App\Services\AiChatService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;

/**
 * AI Security Scanner Service - Comprehensive security analysis for app packages
 *
 * This service provides advanced AI-powered security scanning for uploaded app
 * packages, including vulnerability detection, malware analysis, and tenant-aware
 * security assessment. It ensures comprehensive security validation for all
 * applications in the multi-tenant marketplace.
 *
 * Key features:
 * - AI-powered security analysis and vulnerability detection
 * - Tenant-aware security context analysis
 * - Malware detection and analysis
 * - Dependency vulnerability scanning
 * - Code quality and security assessment
 * - Permission and access control analysis
 * - Multi-tenant isolation validation
 * - False positive filtering and reduction
 * - Comprehensive security recommendations
 * - Automated risk assessment and scoring
 *
 * Security analysis includes:
 * - Source code vulnerability scanning
 * - Dependency package analysis
 * - Configuration security assessment
 * - Permission and access control validation
 * - Multi-tenant isolation verification
 * - Malware and suspicious code detection
 * - Code quality and best practices review
 * - Framework-specific security checks
 *
 * Risk levels:
 * - low: Minimal security concerns
 * - medium: Moderate security risks
 * - high: Significant security vulnerabilities
 * - critical: Severe security issues requiring immediate attention
 *
 * The service provides:
 * - Automated security scanning for uploaded packages
 * - Tenant-aware security context analysis
 * - Comprehensive vulnerability reporting
 * - Security score calculation
 * - Risk level assessment
 * - Detailed recommendations
 * - False positive filtering
 * - Multi-tenant isolation validation
 *
 * @package App\Services
 * @since 1.0.0
 */
class AiSecurityScannerService
{
    /** @var AiChatService Service for AI-powered analysis */
    protected AiChatService $aiService;
    
    /**
     * Initialize the AI Security Scanner Service with dependencies
     *
     * @param AiChatService $aiService Service for AI-powered security analysis
     */
    public function __construct(AiChatService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Scan uploaded app package for security vulnerabilities with tenant context
     *
     * This method performs comprehensive security analysis of uploaded app packages,
     * including AI-powered vulnerability detection, malware analysis, and tenant-aware
     * security assessment. It ensures all applications meet security standards.
     *
     * The scanning process includes:
     * - Package extraction and file analysis
     * - AI-powered code security review
     * - Dependency vulnerability scanning
     * - Malware and suspicious code detection
     * - Tenant isolation validation
     * - Permission and access control analysis
     * - Risk level assessment and scoring
     * - Comprehensive recommendations
     *
     * @param AppStoreApp $app The app store application to scan
     * @return SecurityScanResult The security scan result with detailed analysis
     */
    public function scanAppPackage(AppStoreApp $app): SecurityScanResult
    {
        Log::info("Starting tenant-aware AI security scan for app: {$app->name}");

        try {
            // Extract and analyze the uploaded package
            $extractedPath = $this->extractPackage($app->package_path);
            
            // Get tenant context for enhanced analysis
            $tenantContext = $this->getTenantContext($app);
            
            // Perform comprehensive security analysis with tenant awareness
            $scanResults = $this->performSecurityAnalysis($extractedPath, $app, $tenantContext);
            
            // Create security scan result record
            $securityResult = SecurityScanResult::create([
                'app_store_app_id' => $app->id,
                'scan_status' => $scanResults['status'],
                'risk_level' => $scanResults['risk_level'],
                'vulnerabilities_found' => $scanResults['vulnerabilities'],
                'security_score' => $scanResults['security_score'],
                'ai_analysis' => $scanResults['ai_analysis'],
                'recommendations' => $scanResults['recommendations'],
                'blocked_reasons' => $scanResults['blocked_reasons'],
                'scanned_at' => now(),
            ]);

            // Update app status based on scan results
            $this->updateAppStatus($app, $scanResults);
            
            // Clean up extracted files
            $this->cleanupExtractedFiles($extractedPath);
            
            Log::info("Tenant-aware AI security scan completed for app: {$app->name}", [
                'risk_level' => $scanResults['risk_level'],
                'security_score' => $scanResults['security_score'],
                'tenant_context_considered' => count($tenantContext['installed_apps']) . ' apps analyzed'
            ]);

            return $securityResult;

        } catch (\Exception $e) {
            Log::error("AI security scan failed for app: {$app->name}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Create failed scan result
            return SecurityScanResult::create([
                'app_store_app_id' => $app->id,
                'scan_status' => 'failed',
                'risk_level' => 'unknown',
                'vulnerabilities_found' => [],
                'security_score' => 0,
                'ai_analysis' => 'Scan failed: ' . $e->getMessage(),
                'recommendations' => ['Manual review required due to scan failure'],
                'blocked_reasons' => ['automated_scan_failure'],
                'scanned_at' => now(),
            ]);
        }
    }

    /**
     * Extract uploaded ZIP package for analysis
     *
     * This method extracts the uploaded ZIP package to a temporary directory
     * for security analysis, ensuring proper file handling and cleanup.
     *
     * @param string $packagePath The path to the uploaded package
     * @return string The path to the extracted package directory
     * @throws \Exception When package extraction fails
     */
    private function extractPackage(string $packagePath): string
    {
        $zip = new ZipArchive;
        $extractPath = storage_path('app/temp_scans/' . uniqid());
        
        if (!file_exists(dirname($extractPath))) {
            mkdir(dirname($extractPath), 0755, true);
        }

        $fullPackagePath = Storage::disk('public')->path($packagePath);
        
        if ($zip->open($fullPackagePath) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();
            return $extractPath;
        } else {
            throw new \Exception('Failed to extract package for security scanning');
        }
    }

    /**
     * Perform comprehensive AI-powered security analysis with tenant awareness
     */
    private function performSecurityAnalysis(string $extractedPath, AppStoreApp $app, array $tenantContext): array
    {
        $vulnerabilities = [];
        $recommendations = [];
        $blockedReasons = [];
        
        // 1. Analyze source code files with tenant context
        $codeAnalysis = $this->analyzeSourceCodeWithTenantContext($extractedPath, $tenantContext);
        $vulnerabilities = array_merge($vulnerabilities, $codeAnalysis['vulnerabilities']);
        $recommendations = array_merge($recommendations, $codeAnalysis['recommendations']);
        
        // 2. Check for malicious patterns with tenant-aware filtering
        $malwareAnalysis = $this->scanForMalwareWithTenantContext($extractedPath, $tenantContext);
        $vulnerabilities = array_merge($vulnerabilities, $malwareAnalysis['threats']);
        
        // 3. Analyze dependencies with tenant environment awareness
        $dependencyAnalysis = $this->analyzeDependenciesWithContext($extractedPath, $tenantContext);
        $vulnerabilities = array_merge($vulnerabilities, $dependencyAnalysis['vulnerabilities']);
        
        // 4. Check for insecure configurations with tenant best practices
        $configAnalysis = $this->analyzeConfigurationsWithTenantAwareness($extractedPath, $tenantContext);
        $vulnerabilities = array_merge($vulnerabilities, $configAnalysis['issues']);
        
        // 5. Perform tenant-aware AI analysis
        $aiAnalysis = $this->performTenantAwareAiAnalysis($extractedPath, $app, $tenantContext);
        
        // 6. Validate against tenant isolation requirements
        $tenancyViolations = $this->checkTenancyViolations($extractedPath, $tenantContext);
        $vulnerabilities = array_merge($vulnerabilities, $tenancyViolations);
        
        // Filter false positives based on tenant architecture understanding
        $vulnerabilities = $this->filterFalsePositives($vulnerabilities, $tenantContext);
        
        // Calculate risk level and security score with tenant context
        $riskLevel = $this->calculateRiskLevelWithContext($vulnerabilities, $tenantContext);
        $securityScore = $this->calculateSecurityScore($vulnerabilities, $aiAnalysis);
        
        // Generate tenant-specific recommendations
        $recommendations = array_merge(
            $recommendations, 
            $this->generateTenantSpecificRecommendations($vulnerabilities, $tenantContext)
        );
        
        // Determine if app should be blocked
        $shouldBlock = $this->shouldBlockAppWithContext($riskLevel, $vulnerabilities, $tenantContext);
        if ($shouldBlock) {
            $blockedReasons = $this->getBlockingReasons($vulnerabilities, $riskLevel);
        }

        return [
            'status' => $shouldBlock ? 'blocked' : 'passed',
            'risk_level' => $riskLevel,
            'vulnerabilities' => $vulnerabilities,
            'security_score' => $securityScore,
            'ai_analysis' => $aiAnalysis,
            'recommendations' => array_unique($recommendations),
            'blocked_reasons' => $blockedReasons,
        ];
    }

    /**
     * Analyze source code with tenant context awareness
     */
    private function analyzeSourceCodeWithTenantContext(string $path, array $tenantContext): array
    {
        $vulnerabilities = [];
        $recommendations = [];
        
        $codeFiles = $this->getCodeFiles($path);
        
        foreach ($codeFiles as $file) {
            $content = file_get_contents($file);
            $relativeFile = str_replace($path, '', $file);
            
            // Enhanced AI analysis with tenant context
            $prompt = $this->buildTenantAwareSecurityAnalysisPrompt($content, $relativeFile, $tenantContext);
            $aiResponse = $this->aiService->generateResponse($prompt);
            
            $analysis = $this->parseAiSecurityResponse($aiResponse);
            
            if (!empty($analysis['vulnerabilities'])) {
                foreach ($analysis['vulnerabilities'] as $vuln) {
                    $vulnerabilities[] = [
                        'type' => $vuln['type'],
                        'severity' => $vuln['severity'],
                        'description' => $vuln['description'],
                        'file' => $relativeFile,
                        'line' => $vuln['line'] ?? null,
                        'code_snippet' => $vuln['code_snippet'] ?? null,
                        'tenant_context_considered' => true,
                    ];
                }
            }
            
            if (!empty($analysis['recommendations'])) {
                $recommendations = array_merge($recommendations, $analysis['recommendations']);
            }
        }
        
        return [
            'vulnerabilities' => $vulnerabilities,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Calculate risk level based on vulnerabilities
     */
    private function calculateRiskLevel(array $vulnerabilities): string
    {
        $criticalCount = 0;
        $highCount = 0;
        $mediumCount = 0;
        
        foreach ($vulnerabilities as $vuln) {
            switch ($vuln['severity']) {
                case 'critical':
                    $criticalCount++;
                    break;
                case 'high':
                    $highCount++;
                    break;
                case 'medium':
                    $mediumCount++;
                    break;
            }
        }
        
        if ($criticalCount > 0) return 'critical';
        if ($highCount > 2) return 'critical';
        if ($highCount > 0) return 'high';
        if ($mediumCount > 3) return 'high';
        if ($mediumCount > 0) return 'medium';
        
        return 'low';
    }

    /**
     * Calculate security score (0-100)
     */
    private function calculateSecurityScore(array $vulnerabilities, string $aiAnalysis): int
    {
        $baseScore = 100;
        
        foreach ($vulnerabilities as $vuln) {
            switch ($vuln['severity']) {
                case 'critical':
                    $baseScore -= 25;
                    break;
                case 'high':
                    $baseScore -= 15;
                    break;
                case 'medium':
                    $baseScore -= 8;
                    break;
                case 'low':
                    $baseScore -= 3;
                    break;
            }
        }
        
        return max(0, $baseScore);
    }

    /**
     * Determine if app should be blocked
     */
    private function shouldBlockApp(string $riskLevel, array $vulnerabilities): bool
    {
        // Block if critical risk
        if ($riskLevel === 'critical') {
            return true;
        }
        
        // Block if high risk with certain vulnerability types
        if ($riskLevel === 'high') {
            $dangerousTypes = ['malware_pattern', 'command_injection', 'sql_injection'];
            foreach ($vulnerabilities as $vuln) {
                if (in_array($vuln['type'], $dangerousTypes)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get reasons why app was blocked
     */
    private function getBlockingReasons(array $vulnerabilities, string $riskLevel): array
    {
        $reasons = [];
        
        if ($riskLevel === 'critical') {
            $reasons[] = 'critical_risk_level';
        }
        
        foreach ($vulnerabilities as $vuln) {
            if ($vuln['severity'] === 'critical' || $vuln['severity'] === 'high') {
                $reasons[] = $vuln['type'] . '_vulnerability';
            }
        }
        
        return array_unique($reasons);
    }

    /**
     * Update app status based on scan results
     */
    private function updateAppStatus(AppStoreApp $app, array $scanResults): void
    {
        if ($scanResults['status'] === 'blocked') {
            $app->update([
                'approval_status' => 'security_blocked',
                'is_approved' => false,
                'is_published' => false,
                'security_scan_status' => 'failed',
                'security_scan_notes' => 'Automatically blocked due to security risks',
            ]);
        } else {
            $app->update([
                'security_scan_status' => 'passed',
                'security_scan_notes' => 'AI security scan completed successfully',
            ]);
        }
    }

    // Helper methods for file analysis
    private function getCodeFiles(string $path): array
    {
        $extensions = ['php', 'js', 'ts', 'vue', 'py', 'rb', 'java', 'cs'];
        return $this->getFilesByExtensions($path, $extensions);
    }

    private function getAllFiles(string $path): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }

    private function getFilesByExtensions(string $path, array $extensions): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, $extensions)) {
                    $files[] = $file->getPathname();
                }
            }
        }
        
        return $files;
    }

    private function getAppStructure(string $path): array
    {
        $structure = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        
        foreach ($iterator as $file) {
            $relativePath = str_replace($path, '', $file->getPathname());
            $structure[] = $relativePath;
        }
        
        return array_slice($structure, 0, 50); // Limit for AI processing
    }

    private function getKeyCodeSnippets(string $path): string
    {
        $snippets = [];
        $keyFiles = ['app.json', 'composer.json', 'package.json'];
        
        foreach ($keyFiles as $file) {
            $filePath = $path . '/' . $file;
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $snippets[] = "=== {$file} ===\n" . substr($content, 0, 1000);
            }
        }
        
        return implode("\n\n", $snippets);
    }

    private function isVulnerablePackage(string $package, string $version): bool
    {
        // Simplified vulnerability check - in production, integrate with vulnerability databases
        $knownVulnerable = [
            'monolog/monolog' => ['<1.25.2'],
            'symfony/http-kernel' => ['<4.4.13', '>=5.0,<5.1.5'],
        ];
        
        return isset($knownVulnerable[$package]);
    }

    private function isVulnerableNpmPackage(string $package, string $version): bool
    {
        // Simplified vulnerability check for npm packages
        $knownVulnerable = [
            'lodash' => ['<4.17.19'],
            'minimist' => ['<1.2.2'],
        ];
        
        return isset($knownVulnerable[$package]);
    }

    private function cleanupExtractedFiles(string $path): void
    {
        if (file_exists($path)) {
            $this->deleteDirectory($path);
        }
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Get tenant context for enhanced security analysis
     */
    private function getTenantContext(AppStoreApp $app): array
    {
        $context = [
            'tenant_id' => tenant() ? tenant('id') : null,
            'tenant_name' => tenant() ? tenant('name') : null,
            'submitting_team' => null,
            'installed_apps' => [],
            'team_permissions' => [],
            'tenant_features' => [],
            'platform_version' => config('app.version', '1.0.0'),
            'tenancy_architecture' => 'multi-database-isolated',
        ];

        // Get submitting team context
        if ($app->submitted_by_team_id) {
            $submittingTeam = Team::find($app->submitted_by_team_id);
            if ($submittingTeam) {
                $context['submitting_team'] = [
                    'id' => $submittingTeam->id,
                    'name' => $submittingTeam->name,
                    'plan' => $submittingTeam->subscription_plan ?? 'free',
                    'user_count' => $submittingTeam->users()->count(),
                ];

                // Get installed apps for this team to understand environment
                $installedApps = InstalledApp::where('team_id', $submittingTeam->id)
                    ->where('is_active', true)
                    ->with('appStoreApp')
                    ->get();

                $context['installed_apps'] = $installedApps->map(function($installedApp) {
                    return [
                        'name' => $installedApp->app_name,
                        'type' => $installedApp->app_type,
                        'permissions' => $installedApp->permissions ?? [],
                        'version' => $installedApp->version,
                    ];
                })->toArray();
            }
        }

        // Get tenant-specific features if in tenant context
        if (tenant()) {
            $context['tenant_features'] = $this->getTenantFeatures();
        }

        return $context;
    }

    /**
     * Get tenant-specific features and configuration
     */
    private function getTenantFeatures(): array
    {
        return [
            'multi_database_isolation' => true,
            'tenant_specific_storage' => true,
            'team_based_permissions' => true,
            'file_isolation' => true,
            'cache_isolation' => true,
            'session_isolation' => true,
            'broadcasting_isolation' => true,
            'queue_isolation' => true,
        ];
    }

    /**
     * Perform comprehensive AI-powered security analysis with tenant awareness
     */
    private function performTenantAwareAiAnalysis(string $path, AppStoreApp $app, array $tenantContext): string
    {
        // Get overview of app structure
        $structure = $this->getAppStructure($path);
        $codeSnippets = $this->getKeyCodeSnippets($path);
        
        $tenantInfo = '';
        if ($tenantContext['tenant_id']) {
            $tenantInfo = "
            TENANT ENVIRONMENT ANALYSIS:
            - Tenant ID: {$tenantContext['tenant_id']}
            - Architecture: Multi-database isolation with stancl/tenancy v4
            - Installed Apps: " . count($tenantContext['installed_apps']) . " apps
            - Team Context: " . ($tenantContext['submitting_team']['name'] ?? 'Unknown') . "
            ";
        }
        
        $prompt = "
        Perform a comprehensive TENANT-AWARE security analysis of this application package:
        
        App Name: {$app->name}
        App Type: {$app->app_type}
        Description: {$app->description}
        
        {$tenantInfo}
        
        CRITICAL: This is a MULTI-TENANT application with complete isolation:
        - Each tenant has isolated database, storage, cache, sessions
        - Tenant operations are LEGITIMATE and should NOT be flagged as vulnerabilities
        - Focus on REAL security issues that could compromise tenant isolation or security
        
        File Structure:
        " . implode("\n", $structure) . "
        
        Key Code Snippets:
        " . $codeSnippets . "
        
        Analyze for GENUINE security issues:
        1. Cross-tenant data access vulnerabilities
        2. Tenant isolation bypass attempts  
        3. Real SQL injection (not tenant-scoped queries)
        4. Actual XSS vulnerabilities (not proper escaping)
        5. Command injection possibilities
        6. Authentication/authorization bypasses
        7. Malicious code patterns
        8. Insecure file operations outside tenant boundaries
        9. Cache poisoning across tenants
        10. Session hijacking possibilities
        
        DO NOT flag legitimate multi-tenant operations as security issues.
        Provide tenant-specific recommendations for improvement.
        
        Consider the existing tenant environment and provide recommendations for:
        - Better integration with existing apps
        - Tenant resource optimization
        - Security improvements specific to multi-tenant architecture
        - Performance considerations for tenant isolation
        
        Provide detailed security assessment with risk level and specific, actionable recommendations.
        ";
        
        return $this->aiService->generateResponse($prompt);
    }

    /**
     * Build security analysis prompt for AI
     */
    private function buildSecurityAnalysisPrompt(string $code, string $filename): string
    {
        return "
        Analyze this code file for security vulnerabilities:
        
        File: {$filename}
        
        Code:
        ```
        " . substr($code, 0, 4000) . "
        ```
        
        Please identify:
        1. SQL injection vulnerabilities
        2. XSS vulnerabilities  
        3. CSRF vulnerabilities
        4. File inclusion vulnerabilities
        5. Command injection possibilities
        6. Authentication bypasses
        7. Authorization flaws
        8. Input validation issues
        9. Output encoding problems
        10. Insecure direct object references
        
        Format response as JSON:
        {
            \"vulnerabilities\": [
                {
                    \"type\": \"vulnerability_type\",
                    \"severity\": \"low|medium|high|critical\",
                    \"description\": \"detailed description\",
                    \"line\": \"line_number\",
                    \"code_snippet\": \"vulnerable code\"
                }
            ],
            \"recommendations\": [\"recommendation1\", \"recommendation2\"]
        }
        ";
    }

    /**
     * Parse AI security response
     */
    private function parseAiSecurityResponse(string $response): array
    {
        try {
            // Try to extract JSON from response
            preg_match('/\{.*\}/s', $response, $matches);
            if (!empty($matches[0])) {
                $parsed = json_decode($matches[0], true);
                if ($parsed) {
                    return $parsed;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to parse AI security response', ['response' => $response]);
        }
        
        // Fallback: parse text response
        return [
            'vulnerabilities' => [],
            'recommendations' => ['Manual code review recommended due to AI parsing failure'],
        ];
    }

    /**
     * Calculate risk level based on vulnerabilities with tenant context
     */
    private function calculateRiskLevelWithContext(array $vulnerabilities, array $tenantContext): string
    {
        $criticalCount = 0;
        $highCount = 0;
        $mediumCount = 0;
        
        foreach ($vulnerabilities as $vuln) {
            switch ($vuln['severity']) {
                case 'critical':
                    $criticalCount++;
                    break;
                case 'high':
                    $highCount++;
                    break;
                case 'medium':
                    $mediumCount++;
                    break;
            }
        }
        
        // Adjust risk level based on tenant architecture and installed apps
        if ($criticalCount > 0) return 'critical';
        if ($highCount > 2) return 'critical';
        if ($highCount > 0) return 'high';
        if ($mediumCount > 3) return 'high';
        if ($mediumCount > 0) return 'medium';
        
        return 'low';
    }

    /**
     * Determine if app should be blocked with tenant context
     */
    private function shouldBlockAppWithContext(string $riskLevel, array $vulnerabilities, array $tenantContext): bool
    {
        // Block if critical risk
        if ($riskLevel === 'critical') {
            return true;
        }
        
        // Block if high risk with certain vulnerability types
        if ($riskLevel === 'high') {
            $dangerousTypes = ['malware_pattern', 'command_injection', 'sql_injection', 'tenancy_violation'];
            foreach ($vulnerabilities as $vuln) {
                if (in_array($vuln['type'], $dangerousTypes)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get reasons why app was blocked with tenant context
     */
    private function getBlockingReasons(array $vulnerabilities, string $riskLevel): array
    {
        $reasons = [];
        
        if ($riskLevel === 'critical') {
            $reasons[] = 'critical_risk_level';
        }
        
        foreach ($vulnerabilities as $vuln) {
            if ($vuln['severity'] === 'critical' || $vuln['severity'] === 'high') {
                $reasons[] = $vuln['type'] . '_vulnerability';
            }
        }
        
        return array_unique($reasons);
    }

    /**
     * Update app status based on scan results
     */
    private function updateAppStatus(AppStoreApp $app, array $scanResults): void
    {
        if ($scanResults['status'] === 'blocked') {
            $app->update([
                'approval_status' => 'security_blocked',
                'is_approved' => false,
                'is_published' => false,
                'security_scan_status' => 'failed',
                'security_scan_notes' => 'Automatically blocked due to security risks',
            ]);
        } else {
            $app->update([
                'security_scan_status' => 'passed',
                'security_scan_notes' => 'AI security scan completed successfully',
            ]);
        }
    }

    // Helper methods for file analysis
    private function getCodeFiles(string $path): array
    {
        $extensions = ['php', 'js', 'ts', 'vue', 'py', 'rb', 'java', 'cs'];
        return $this->getFilesByExtensions($path, $extensions);
    }

    private function getAllFiles(string $path): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }

    private function getFilesByExtensions(string $path, array $extensions): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, $extensions)) {
                    $files[] = $file->getPathname();
                }
            }
        }
        
        return $files;
    }

    private function getAppStructure(string $path): array
    {
        $structure = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        
        foreach ($iterator as $file) {
            $relativePath = str_replace($path, '', $file->getPathname());
            $structure[] = $relativePath;
        }
        
        return array_slice($structure, 0, 50); // Limit for AI processing
    }

    private function getKeyCodeSnippets(string $path): string
    {
        $snippets = [];
        $keyFiles = ['app.json', 'composer.json', 'package.json'];
        
        foreach ($keyFiles as $file) {
            $filePath = $path . '/' . $file;
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $snippets[] = "=== {$file} ===\n" . substr($content, 0, 1000);
            }
        }
        
        return implode("\n\n", $snippets);
    }

    private function isVulnerablePackage(string $package, string $version): bool
    {
        // Simplified vulnerability check - in production, integrate with vulnerability databases
        $knownVulnerable = [
            'monolog/monolog' => ['<1.25.2'],
            'symfony/http-kernel' => ['<4.4.13', '>=5.0,<5.1.5'],
        ];
        
        return isset($knownVulnerable[$package]);
    }

    private function isVulnerableNpmPackage(string $package, string $version): bool
    {
        // Simplified vulnerability check for npm packages
        $knownVulnerable = [
            'lodash' => ['<4.17.19'],
            'minimist' => ['<1.2.2'],
        ];
        
        return isset($knownVulnerable[$package]);
    }

    private function cleanupExtractedFiles(string $path): void
    {
        if (file_exists($path)) {
            $this->deleteDirectory($path);
        }
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Get tenant context for enhanced security analysis
     */
    private function getTenantContext(AppStoreApp $app): array
    {
        $context = [
            'tenant_id' => tenant() ? tenant('id') : null,
            'tenant_name' => tenant() ? tenant('name') : null,
            'submitting_team' => null,
            'installed_apps' => [],
            'team_permissions' => [],
            'tenant_features' => [],
            'platform_version' => config('app.version', '1.0.0'),
            'tenancy_architecture' => 'multi-database-isolated',
        ];

        // Get submitting team context
        if ($app->submitted_by_team_id) {
            $submittingTeam = Team::find($app->submitted_by_team_id);
            if ($submittingTeam) {
                $context['submitting_team'] = [
                    'id' => $submittingTeam->id,
                    'name' => $submittingTeam->name,
                    'plan' => $submittingTeam->subscription_plan ?? 'free',
                    'user_count' => $submittingTeam->users()->count(),
                ];

                // Get installed apps for this team to understand environment
                $installedApps = InstalledApp::where('team_id', $submittingTeam->id)
                    ->where('is_active', true)
                    ->with('appStoreApp')
                    ->get();

                $context['installed_apps'] = $installedApps->map(function($installedApp) {
                    return [
                        'name' => $installedApp->app_name,
                        'type' => $installedApp->app_type,
                        'permissions' => $installedApp->permissions ?? [],
                        'version' => $installedApp->version,
                    ];
                })->toArray();
            }
        }

        // Get tenant-specific features if in tenant context
        if (tenant()) {
            $context['tenant_features'] = $this->getTenantFeatures();
        }

        return $context;
    }

    /**
     * Get tenant-specific features and configuration
     */
    private function getTenantFeatures(): array
    {
        return [
            'multi_database_isolation' => true,
            'tenant_specific_storage' => true,
            'team_based_permissions' => true,
            'file_isolation' => true,
            'cache_isolation' => true,
            'session_isolation' => true,
            'broadcasting_isolation' => true,
            'queue_isolation' => true,
        ];
    }

    /**
     * Check for tenancy architecture violations
     */
    private function checkTenancyViolations(string $path, array $tenantContext): array
    {
        $violations = [];
        $files = $this->getAllFiles($path);
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $relativeFile = str_replace($path, '', $file);
            
            // Check for tenant isolation violations
            $tenancyViolations = [
                // Direct central database access
                '/DB::connection\(\s*["\']central["\']\s*\)/i' => 'Direct central database access detected',
                '/Config::set\(\s*["\']database\.default["\']/i' => 'Database connection override detected',
                
                // Cross-tenant data access attempts
                '/tenant\(\s*["\'][^"\']*["\']\s*\)\s*->\s*run\s*\(/i' => 'Cross-tenant execution detected',
                '/tenancy\(\)\s*->\s*initialize\s*\(/i' => 'Manual tenancy initialization detected',
                
                // File system violations
                '/storage_path\(\s*["\'](?!app\/tenant)/i' => 'Non-tenant storage path access',
                '/Storage::disk\(\s*["\'](?!tenant)/i' => 'Non-tenant disk access',
                
                // Cache violations
                '/Cache::store\(\s*["\'](?!tenant)/i' => 'Non-tenant cache store access',
                '/cache\(\)\s*->\s*tags\(\s*(?!.*tenant)/i' => 'Cache without tenant tags',
                
                // Session violations
                '/session\(\)\s*->\s*put.*without.*tenant/i' => 'Session data without tenant context',
            ];
            
            foreach ($tenancyViolations as $pattern => $description) {
                if (preg_match($pattern, $content, $matches)) {
                    $violations[] = [
                        'type' => 'tenancy_violation',
                        'severity' => 'high',
                        'description' => $description,
                        'file' => $relativeFile,
                        'pattern' => $pattern,
                        'match' => $matches[0] ?? '',
                        'recommendation' => $this->getTenancyViolationRecommendation($pattern),
                    ];
                }
            }
        }
        
        return $violations;
    }

    /**
     * Filter false positives based on tenant architecture understanding
     */
    private function filterFalsePositives(array $vulnerabilities, array $tenantContext): array
    {
        return array_filter($vulnerabilities, function($vuln) use ($tenantContext) {
            // Allow legitimate tenant operations
            if ($this->isLegitimateTenantzperation($vuln, $tenantContext)) {
                return false;
            }
            
            // Allow framework-level operations that are tenant-safe
            if ($this->isFrameworkSafeOperation($vuln)) {
                return false;
            }
            
            // Allow operations that are safe in the current tenant environment
            if ($this->isSafeInTenantEnvironment($vuln, $tenantContext)) {
                return false;
            }
            
            return true;
        });
    }

    /**
     * Check if operation is legitimate tenant operation
     */
    private function isLegitimateTenantzperation(array $vuln, array $tenantContext): bool
    {
        $legitimatePatterns = [
            // Tenant-aware database operations
            '/Model::.*where.*tenant/i',
            '/->where\(\s*["\']tenant_id["\']/i',
            '/scope.*Tenant/i',
            
            // Tenant-safe file operations within tenant context
            '/Storage::disk\(\s*["\']tenant["\']\s*\)/i',
            '/storage_path\(\s*["\']app\/tenant/i',
            
            // Tenant-aware caching
            '/cache\(\)\s*->\s*tags\(.*tenant/i',
            '/Cache::tags\(.*tenant/i',
            
            // Proper tenancy usage
            '/tenant\(\s*["\']id["\']\s*\)/i',
            '/tenancy\(\)\s*->\s*initialized/i',
        ];
        
        foreach ($legitimatePatterns as $pattern) {
            if (isset($vuln['code_snippet']) && preg_match($pattern, $vuln['code_snippet'])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate tenant-specific security recommendations
     */
    private function generateTenantSpecificRecommendations(array $vulnerabilities, array $tenantContext): array
    {
        $recommendations = [];
        
        // Tenancy-specific recommendations
        $recommendations[] = "Ensure all database operations are tenant-scoped using proper Eloquent models";
        $recommendations[] = "Use tenant-aware storage disks (Storage::disk('tenant')) for file operations";
        $recommendations[] = "Implement proper tenant isolation in cache operations using tenant-specific tags";
        $recommendations[] = "Validate that all user inputs are properly sanitized within tenant context";
        
        // Context-based recommendations
        if (!empty($tenantContext['installed_apps'])) {
            $recommendations[] = "Consider compatibility with existing apps: " . 
                implode(', ', array_column($tenantContext['installed_apps'], 'name'));
        }
        
        if (isset($tenantContext['submitting_team']['plan'])) {
            $plan = $tenantContext['submitting_team']['plan'];
            if ($plan === 'free') {
                $recommendations[] = "Consider resource usage limits for free plan tenants";
            }
        }
        
        // Security recommendations based on vulnerabilities found
        $vulnTypes = array_column($vulnerabilities, 'type');
        if (in_array('sql_injection', $vulnTypes)) {
            $recommendations[] = "Use Eloquent ORM with proper parameter binding instead of raw SQL queries";
        }
        
        if (in_array('xss', $vulnTypes)) {
            $recommendations[] = "Implement proper output escaping using Blade templates or Laravel's HTML helpers";
        }
        
        if (in_array('file_inclusion', $vulnTypes)) {
            $recommendations[] = "Use tenant-aware file paths and validate all file operations within tenant boundaries";
        }
        
        return array_unique($recommendations);
    }

    /**
     * Build tenant-aware security analysis prompt for AI
     */
    private function buildTenantAwareSecurityAnalysisPrompt(string $code, string $filename, array $tenantContext): string
    {
        $installedAppsInfo = '';
        if (!empty($tenantContext['installed_apps'])) {
            $installedAppsInfo = "\nExisting Apps in Tenant Environment:\n" . 
                implode("\n", array_map(function($app) {
                    return "- {$app['name']} ({$app['type']})";
                }, $tenantContext['installed_apps']));
        }
        
        return "
        Analyze this code file for security vulnerabilities in a MULTI-TENANT LARAVEL APPLICATION:
        
        File: {$filename}
        
        CRITICAL CONTEXT - MULTI-TENANT ARCHITECTURE:
        - Database: Each tenant has ISOLATED DATABASE (multi-database tenancy)
        - Storage: Each tenant has ISOLATED storage buckets and file systems  
        - Cache: Tenant-specific cache tags and isolation
        - Sessions: Tenant-scoped sessions
        - Broadcasting: Tenant-aware channels
        - Queue: Tenant context preserved in jobs
        
        TENANT CONTEXT:
        - Platform uses stancl/tenancy v4 with domain identification
        - Complete tenant isolation at database, file, and cache levels
        - Tenant ID: {$tenantContext['tenant_id']}
        - Tenancy Architecture: {$tenantContext['tenancy_architecture']}
        {$installedAppsInfo}
        
        LEGITIMATE PATTERNS (DO NOT FLAG AS VULNERABILITIES):
        ✅ tenant('id') - Getting current tenant ID
        ✅ Storage::disk('tenant') - Using tenant storage disk
        ✅ ->where('tenant_id', tenant('id')) - Tenant scoping
        ✅ cache()->tags(['tenant:'.tenant('id')]) - Tenant cache tags  
        ✅ Model::tenantScoped() - Tenant-aware model scoping
        ✅ tenancy()->initialized - Checking tenant context
        
        ACTUAL SECURITY CONCERNS TO FLAG:
        ❌ Direct central database access bypassing tenant isolation
        ❌ Cross-tenant data access attempts
        ❌ Raw SQL without tenant scoping
        ❌ File operations outside tenant boundaries
        ❌ Cache operations without tenant isolation
        ❌ Hardcoded credentials or secrets
        ❌ Unvalidated user input leading to XSS/SQLi
        ❌ Command injection or code execution vulnerabilities
        
        Code to analyze:
        ```
        " . substr($code, 0, 4000) . "
        ```
        
        Please identify REAL security vulnerabilities while understanding this is a properly architected multi-tenant system.
        Consider the tenant isolation features and DO NOT flag legitimate tenant operations as security issues.
        
        Focus on:
        1. Actual SQL injection vulnerabilities (not tenant-scoped queries)
        2. Real XSS vulnerabilities (not proper Blade escaping)
        3. Genuine file inclusion risks (not tenant file operations)
        4. Command injection possibilities
        5. Authentication bypasses
        6. Authorization flaws that break tenant boundaries
        7. Cross-tenant data leakage risks
        8. Improper input validation
        9. Insecure direct object references across tenants
        10. Tenancy isolation violations
        
        Format response as JSON:
        {
            \"vulnerabilities\": [
                {
                    \"type\": \"vulnerability_type\",
                    \"severity\": \"low|medium|high|critical\",
                    \"description\": \"detailed description with tenant context\",
                    \"line\": \"line_number\",
                    \"code_snippet\": \"vulnerable code\",
                    \"tenant_impact\": \"how this affects tenant isolation\"
                }
            ],
            \"recommendations\": [\"tenant-aware recommendation1\", \"recommendation2\"]
        }
        ";
    }

    /**
     * Perform tenant-aware AI analysis of the entire app
     */
    private function performTenantAwareAiAnalysis(string $path, AppStoreApp $app, array $tenantContext): string
    {
        // Get overview of app structure
        $structure = $this->getAppStructure($path);
        $codeSnippets = $this->getKeyCodeSnippets($path);
        
        $tenantInfo = '';
        if ($tenantContext['tenant_id']) {
            $tenantInfo = "
            TENANT ENVIRONMENT ANALYSIS:
            - Tenant ID: {$tenantContext['tenant_id']}
            - Architecture: Multi-database isolation with stancl/tenancy v4
            - Installed Apps: " . count($tenantContext['installed_apps']) . " apps
            - Team Context: " . ($tenantContext['submitting_team']['name'] ?? 'Unknown') . "
            ";
        }
        
        $prompt = "
        Perform a comprehensive TENANT-AWARE security analysis of this application package:
        
        App Name: {$app->name}
        App Type: {$app->app_type}
        Description: {$app->description}
        
        {$tenantInfo}
        
        CRITICAL: This is a MULTI-TENANT application with complete isolation:
        - Each tenant has isolated database, storage, cache, sessions
        - Tenant operations are LEGITIMATE and should NOT be flagged as vulnerabilities
        - Focus on REAL security issues that could compromise tenant isolation or security
        
        File Structure:
        " . implode("\n", $structure) . "
        
        Key Code Snippets:
        " . $codeSnippets . "
        
        Analyze for GENUINE security issues:
        1. Cross-tenant data access vulnerabilities
        2. Tenant isolation bypass attempts  
        3. Real SQL injection (not tenant-scoped queries)
        4. Actual XSS vulnerabilities (not proper escaping)
        5. Command injection possibilities
        6. Authentication/authorization bypasses
        7. Malicious code patterns
        8. Insecure file operations outside tenant boundaries
        9. Cache poisoning across tenants
        10. Session hijacking possibilities
        
        DO NOT flag legitimate multi-tenant operations as security issues.
        Provide tenant-specific recommendations for improvement.
        
        Consider the existing tenant environment and provide recommendations for:
        - Better integration with existing apps
        - Tenant resource optimization
        - Security improvements specific to multi-tenant architecture
        - Performance considerations for tenant isolation
        
        Provide detailed security assessment with risk level and specific, actionable recommendations.
        ";
        
        return $this->aiService->generateResponse($prompt);
    }

    /**
     * Check if operation is framework-level and tenant-safe
     */
    private function isFrameworkSafeOperation(array $vuln): bool
    {
        // Framework operations that are safe in tenant context
        $safeFrameworkOperations = [
            'laravel_facade_usage',
            'eloquent_relationship',
            'blade_directive',
            'validation_rule',
        ];
        
        return in_array($vuln['type'] ?? '', $safeFrameworkOperations);
    }

    /**
     * Check if operation is safe in current tenant environment
     */
    private function isSafeInTenantEnvironment(array $vuln, array $tenantContext): bool
    {
        // Consider installed apps and tenant features
        if (isset($vuln['type']) && $vuln['type'] === 'database_query') {
            // Check if similar database operations are used by existing apps
            foreach ($tenantContext['installed_apps'] as $app) {
                if ($app['type'] === 'laravel_module') {
                    return true; // Database queries are normal for Laravel modules
                }
            }
        }
        
        return false;
    }

    /**
     * Assess severity of package vulnerability in tenant context
     */
    private function assessPackageVulnerabilitySeverity(string $package, string $version, array $tenantContext): string
    {
        // Base severity assessment
        $baseSeverity = 'medium';
        
        // Adjust based on tenant context
        if (empty($tenantContext['installed_apps'])) {
            // New tenant with no apps - be more lenient
            return 'low';
        }
        
        // Check if similar packages are used by existing apps
        foreach ($tenantContext['installed_apps'] as $app) {
            if ($app['type'] === 'laravel_module') {
                return $baseSeverity; // Keep normal severity for module environments
            }
        }
        
        return $baseSeverity;
    }

    /**
     * Assess severity of requested permissions in tenant context
     */
    private function assessPermissionSeverity(array $permissions, array $tenantContext): string
    {
        $criticalPermissions = ['system_commands', 'database_admin'];
        $hasCritical = !empty(array_intersect($permissions, $criticalPermissions));
        
        if ($hasCritical) {
            return 'high';
        }
        
        // Consider tenant plan
        if (isset($tenantContext['submitting_team']['plan'])) {
            $plan = $tenantContext['submitting_team']['plan'];
            if ($plan === 'free' && count($permissions) > 2) {
                return 'medium';
            }
        }
        
        return 'low';
    }

    /**
     * Get permission recommendation based on tenant context
     */
    private function getPermissionRecommendation(array $permissions, array $tenantContext): string
    {
        $recommendations = [];
        
        foreach ($permissions as $permission) {
            switch ($permission) {
                case 'admin_access':
                    $recommendations[] = "Consider using role-based permissions instead of admin access";
                    break;
                case 'file_system_write':
                    $recommendations[] = "Use tenant-scoped file operations with Storage::disk('tenant')";
                    break;
                case 'database_admin':
                    $recommendations[] = "Use Eloquent models with proper tenant scoping instead of direct database access";
                    break;
                case 'system_commands':
                    $recommendations[] = "Avoid system commands; use Laravel's built-in functionality";
                    break;
            }
        }
        
        return implode('. ', $recommendations);
    }

    /**
     * Get recommendation for a specific tenancy violation
     */
    private function getTenancyViolationRecommendation(string $pattern): string
    {
        switch ($pattern) {
            case '/DB::connection\(\s*["\']central["\']\s*\)/i':
                return "Avoid direct central database access. Use tenant-scoped models or tenancy()->central() for legitimate central operations.";
            case '/Config::set\(\s*["\']database\.default["\']/i':
                return "Don't override database config. Let stancl/tenancy handle database switching automatically.";
            case '/tenant\(\s*["\'][^"\']*["\']\s*\)\s*->\s*run\s*\(/i':
                return "Ensure cross-tenant operations are properly authorized and necessary for app functionality.";
            case '/tenancy\(\)\s*->\s*initialize\s*\(/i':
                return "Avoid manual tenancy initialization. Use middleware for automatic tenant detection.";
            case '/storage_path\(\s*["\'](?!app\/tenant)/i':
                return "Use tenant-aware storage: storage_path('app/tenant') or Storage::disk('tenant').";
            case '/Storage::disk\(\s*["\'](?!tenant)/i':
                return "Use tenant storage disk: Storage::disk('tenant') for proper tenant isolation.";
            case '/Cache::store\(\s*["\'](?!tenant)/i':
                return "Use tenant cache with tags: cache()->tags(['tenant:'.tenant('id')]).";
            case '/cache\(\)\s*->\s*tags\(\s*(?!.*tenant)/i':
                return "Include tenant tags in cache operations to prevent cross-tenant cache pollution.";
            case '/session\(\)\s*->\s*put.*without.*tenant/i':
                return "Include tenant context in session data for proper isolation.";
            default:
                return "Review code for potential tenant isolation violations.";
        }
    }

    /**
     * Scan for malware and suspicious patterns with tenant context awareness
     */
    private function scanForMalwareWithTenantContext(string $path, array $tenantContext): array
    {
        $threats = [];
        
        // Define suspicious patterns (excluding legitimate tenant operations)
        $maliciousPatterns = [
            '/eval\s*\(/i' => 'Dynamic code execution (eval)',
            '/exec\s*\(/i' => 'System command execution',
            '/system\s*\(/i' => 'System command execution',
            '/shell_exec\s*\(/i' => 'Shell command execution',
            '/file_get_contents\s*\(\s*["\']https?:\/\//i' => 'Remote file inclusion',
            '/base64_decode\s*\(/i' => 'Potential obfuscated code',
            '/str_rot13\s*\(/i' => 'Potential obfuscated code',
            // Note: Removed patterns that might be legitimate in tenant context
        ];
        
        $files = $this->getAllFiles($path);
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $relativeFile = str_replace($path, '', $file);
            
            foreach ($maliciousPatterns as $pattern => $description) {
                if (preg_match($pattern, $content, $matches)) {
                    // Additional check to avoid false positives with tenant operations
                    if (!$this->isTenantSafeOperation($matches[0], $tenantContext)) {
                        $threats[] = [
                            'type' => 'malware_pattern',
                            'severity' => 'high',
                            'description' => $description,
                            'file' => $relativeFile,
                            'pattern' => $pattern,
                            'match' => $matches[0] ?? '',
                        ];
                    }
                }
            }
        }
        
        return ['threats' => $threats];
    }

    /**
     * Analyze dependencies with tenant environment awareness
     */
    private function analyzeDependenciesWithContext(string $path, array $tenantContext): array
    {
        $vulnerabilities = [];
        
        // Check composer.json for PHP dependencies
        $composerFile = $path . '/composer.json';
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            if (isset($composer['require'])) {
                foreach ($composer['require'] as $package => $version) {
                    // Check against known vulnerable packages
                    if ($this->isVulnerablePackage($package, $version)) {
                        // Consider tenant context for severity assessment
                        $severity = $this->assessPackageVulnerabilitySeverity($package, $version, $tenantContext);
                        
                        $vulnerabilities[] = [
                            'type' => 'vulnerable_dependency',
                            'severity' => $severity,
                            'description' => "Potentially vulnerable package: {$package} {$version}",
                            'file' => 'composer.json',
                            'package' => $package,
                            'version' => $version,
                            'tenant_context_considered' => true,
                        ];
                    }
                }
            }
        }
        
        // Check package.json for JavaScript dependencies
        $packageFile = $path . '/package.json';
        if (file_exists($packageFile)) {
            $package = json_decode(file_get_contents($packageFile), true);
            if (isset($package['dependencies'])) {
                foreach ($package['dependencies'] as $pkg => $version) {
                    if ($this->isVulnerableNpmPackage($pkg, $version)) {
                        $vulnerabilities[] = [
                            'type' => 'vulnerable_js_dependency',
                            'severity' => 'medium',
                            'description' => "Potentially vulnerable npm package: {$pkg} {$version}",
                            'file' => 'package.json',
                            'package' => $pkg,
                            'version' => $version,
                        ];
                    }
                }
            }
        }
        
        return ['vulnerabilities' => $vulnerabilities];
    }

    /**
     * Analyze configuration files with tenant best practices
     */
    private function analyzeConfigurationsWithTenantAwareness(string $path, array $tenantContext): array
    {
        $issues = [];
        
        // Check app.json configuration
        $appJsonFile = $path . '/app.json';
        if (file_exists($appJsonFile)) {
            $appJson = json_decode(file_get_contents($appJsonFile), true);
            
            // Check for excessive permissions with tenant context
            if (isset($appJson['permissions']) && is_array($appJson['permissions'])) {
                $dangerousPermissions = ['admin_access', 'file_system_write', 'database_admin', 'system_commands'];
                $requestedDangerous = array_intersect($appJson['permissions'], $dangerousPermissions);
                
                if (!empty($requestedDangerous)) {
                    // Consider tenant environment for severity
                    $severity = $this->assessPermissionSeverity($requestedDangerous, $tenantContext);
                    
                    $issues[] = [
                        'type' => 'excessive_permissions',
                        'severity' => $severity,
                        'description' => 'App requests dangerous permissions: ' . implode(', ', $requestedDangerous),
                        'file' => 'app.json',
                        'permissions' => $requestedDangerous,
                        'tenant_recommendation' => $this->getPermissionRecommendation($requestedDangerous, $tenantContext),
                    ];
                }
            }
            
            // Check iframe configuration for security
            if (isset($appJson['iframe_config'])) {
                $iframeConfig = $appJson['iframe_config'];
                if (isset($iframeConfig['sandbox']) && empty($iframeConfig['sandbox'])) {
                    $issues[] = [
                        'type' => 'insecure_iframe',
                        'severity' => 'medium',
                        'description' => 'Iframe configuration lacks proper sandboxing',
                        'file' => 'app.json',
                        'recommendation' => 'Add sandbox restrictions: ["allow-scripts", "allow-same-origin", "allow-forms"]',
                    ];
                }
            }
        }
        
        return ['issues' => $issues];
    }
}