<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppStoreApp;
use App\Models\AppStoreCategory;
use App\Models\AppStoreReview;
use App\Models\AppStorePurchase;
use App\Models\InstalledApp;
use App\Models\SecurityScanResult;
use App\Services\AppStoreService;
use App\Services\ModuleInstallationService;
use App\Services\AiSecurityScannerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * App Store Controller - Manages app store operations and marketplace functionality
 *
 * This controller handles all app store operations including browsing, purchasing,
 * installing, reviewing, and managing applications in the marketplace. It provides
 * comprehensive app store functionality with security scanning and tenant isolation.
 *
 * Key features:
 * - App browsing and search functionality
 * - Purchase processing and payment integration
 * - App installation and uninstallation
 * - Review and rating system
 * - App submission and approval workflow
 * - Security scanning and vulnerability detection
 * - Developer app management
 * - Tenant-specific app installations
 *
 * Supported operations:
 * - Browse apps by category and filters
 * - View app details and screenshots
 * - Purchase apps with payment processing
 * - Install/uninstall apps for tenant teams
 * - Submit reviews and ratings
 * - Submit new apps for approval
 * - Manage developer's own apps
 * - Security scanning and validation
 *
 * The controller provides:
 * - RESTful API endpoints for app store operations
 * - Comprehensive error handling and validation
 * - Security scanning integration
 * - Payment processing integration
 * - Tenant isolation and access control
 * - Developer workflow management
 *
 * @package App\Http\Controllers\Api
 * @since 1.0.0
 */
class AppStoreController extends Controller
{
    /** @var AppStoreService Service for app store operations */
    protected AppStoreService $appStoreService;

    /** @var ModuleInstallationService Service for module installation */
    protected ModuleInstallationService $moduleService;

    /** @var AiSecurityScannerService Service for security scanning */
    protected AiSecurityScannerService $securityScanner;

    /**
     * Initialize the App Store Controller with dependencies
     *
     * @param AppStoreService $appStoreService Service for app store operations
     * @param ModuleInstallationService $moduleService Service for module installation
     * @param AiSecurityScannerService $securityScanner Service for security scanning
     */
    public function __construct(
        AppStoreService $appStoreService,
        ModuleInstallationService $moduleService,
        AiSecurityScannerService $securityScanner
    ) {
        $this->appStoreService = $appStoreService;
        $this->moduleService = $moduleService;
        $this->securityScanner = $securityScanner;
    }

    /**
     * Get featured apps and categories for store homepage
     * Returns data in exact format expected by static frontend
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Return data in exact format expected by static frontend
            $featured = [
                [
                    'section' => 'SYSTEM',
                    'image' => '/img/featured1.png',
                    'title' => 'OS Apps',
                    'subtitle' => 'Apps that helps you cloud and system'
                ],
                [
                    'section' => 'Website',
                    'image' => '/img/featured2.png',
                    'title' => 'Website Apps',
                    'subtitle' => 'Apps that power up your website'
                ]
            ];

            // Get published and approved apps, organized by sections
            $ecommerceApps = AppStoreApp::published()
                ->approved()
                ->whereHas('categories', function ($query) {
                    $query->where('slug', 'ecommerce');
                })
                ->with(['developer'])
                ->orderBy('download_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($app) {
                    return [
                        'iconClass' => $app->icon_class ?? 'fa-cube',
                        'iconBgClass' => $app->icon_bg_class ?? 'blue-icon',
                        'name' => $app->name,
                        'subtitle' => $app->description,
                        'price' => $this->formatPrice($app),
                        'priceDescription' => $this->formatPriceDescription($app),
                        'author' => $app->developer_name ?? 'Unknown'
                    ];
                });

            $sellingApps = AppStoreApp::published()
                ->approved()
                ->whereHas('categories', function ($query) {
                    $query->where('slug', 'selling');
                })
                ->with(['developer'])
                ->orderBy('download_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($app) {
                    return [
                        'iconClass' => $app->icon_class ?? 'fa-cube',
                        'iconBgClass' => $app->icon_bg_class ?? 'blue-icon',
                        'name' => $app->name,
                        'subtitle' => $app->description,
                        'price' => $this->formatPrice($app),
                        'priceDescription' => $this->formatPriceDescription($app),
                        'author' => $app->developer_name ?? 'Unknown'
                    ];
                });

            // Fallback to static data if no apps in database
            if ($ecommerceApps->isEmpty()) {
                $ecommerceApps = collect([
                    [
                        'iconClass' => 'fa-cart-shopping',
                        'iconBgClass' => 'blue-icon',
                        'name' => 'eCommerce',
                        'subtitle' => 'Sell online and in person, locally and globally, on desktop and mobile.',
                        'price' => '$49.99',
                        'priceDescription' => 'One time payment',
                        'author' => 'Alien Host'
                    ],
                    [
                        'iconClass' => 'fa-utensils',
                        'iconBgClass' => 'purple-icon',
                        'name' => 'Food Menu',
                        'subtitle' => 'Online menu & food ordering for your restaurant and fastfood.',
                        'price' => '$49.99',
                        'priceDescription' => 'In-app purchases',
                        'author' => 'John Doe'
                    ],
                    [
                        'iconClass' => 'fa-hotel',
                        'iconBgClass' => 'green-icon',
                        'name' => 'Bookings',
                        'subtitle' => 'Get rooms reservations online and in person, on desktop and mobile.',
                        'price' => 'GET APP',
                        'priceDescription' => 'Free',
                        'author' => 'John Doe'
                    ]
                ]);
            }

            if ($sellingApps->isEmpty()) {
                $sellingApps = collect([
                    [
                        'iconClass' => 'fa-cash-register',
                        'iconBgClass' => 'blue-icon',
                        'name' => 'POS System',
                        'subtitle' => 'Point of sale for your business to sell in person.',
                        'price' => '$49.99',
                        'priceDescription' => 'One time payment',
                        'author' => 'John Doe'
                    ],
                    [
                        'iconClass' => 'fa-users',
                        'iconBgClass' => 'purple-icon',
                        'name' => 'eCommerce Marketplaces',
                        'subtitle' => 'Sell your products to marketplaces like Amazon, eBay, and Etsy.',
                        'price' => '$49.99',
                        'priceDescription' => 'In-app purchases',
                        'author' => 'John Doe'
                    ]
                ]);
            }

            $appSections = [
                [
                    'title' => 'Start selling online',
                    'seeAll' => '#',
                    'apps' => $ecommerceApps->toArray()
                ],
                [
                    'title' => 'Sell Channels',
                    'seeAll' => '#',
                    'apps' => $sellingApps->toArray()
                ]
            ];

            return response()->json([
                'featured' => $featured,
                'sections' => $appSections
            ]);

        } catch (\Exception $e) {
            Log::error('AppStore index error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load app store'], 500);
        }
    }

    /**
     * Format app price for display
     */
    private function formatPrice(AppStoreApp $app): string
    {
        if ($app->pricing_type === 'free') {
            return 'GET APP';
        }

        // Check if user already has this app installed
        if (auth()->check() && auth()->user()->currentTeam) {
            $isInstalled = InstalledApp::where('team_id', auth()->user()->currentTeam->id)
                ->where('app_store_app_id', $app->id)
                ->exists();

            if ($isInstalled) {
                return 'INSTALLED';
            }
        }

        if ($app->pricing_type === 'one_time') {
            return '$' . number_format($app->price, 2);
        }

        if ($app->pricing_type === 'monthly') {
            return '$' . number_format($app->monthly_price, 2);
        }

        return 'GET APP';
    }

    /**
     * Format price description for display
     */
    private function formatPriceDescription(AppStoreApp $app): string
    {
        switch ($app->pricing_type) {
            case 'free':
                return 'Free';
            case 'one_time':
                return 'One time payment';
            case 'monthly':
                return 'Monthly subscription';
            case 'freemium':
                return 'In-app purchases';
            default:
                return 'Free';
        }
    }

    /**
     * Browse apps with filtering and search
     */
    public function browse(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'category' => 'nullable|string|exists:app_store_categories,slug',
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|in:popular,newest,rating,price_low,price_high,name',
                'pricing' => 'nullable|in:free,paid,freemium',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
            ]);

            $query = AppStoreApp::published()
                ->approved()
                ->with(['categories', 'developer']);

            // Apply category filter
            if ($request->category) {
                $query->byCategory($request->category);
            }

            // Apply search filter
            if ($request->search) {
                $query->search($request->search);
            }

            // Apply pricing filter
            if ($request->pricing) {
                switch ($request->pricing) {
                    case 'free':
                        $query->where('pricing_type', 'free');
                        break;
                    case 'paid':
                        $query->whereIn('pricing_type', ['one_time', 'monthly']);
                        break;
                    case 'freemium':
                        $query->where('pricing_type', 'freemium');
                        break;
                }
            }

            // Apply sorting
            switch ($request->sort ?? 'popular') {
                case 'newest':
                    $query->latest('published_at');
                    break;
                case 'rating':
                    $query->orderBy('rating_average', 'desc');
                    break;
                case 'price_low':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('price', 'desc');
                    break;
                case 'name':
                    $query->orderBy('name', 'asc');
                    break;
                default: // popular
                    $query->orderBy('download_count', 'desc');
                    break;
            }

            $perPage = $request->per_page ?? 20;
            $apps = $query->paginate($perPage);

            return response()->json([
                'apps' => $apps->items(),
                'pagination' => [
                    'current_page' => $apps->currentPage(),
                    'last_page' => $apps->lastPage(),
                    'per_page' => $apps->perPage(),
                    'total' => $apps->total(),
                    'has_more' => $apps->hasMorePages(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('AppStore browse error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to browse apps'], 500);
        }
    }

    /**
     * Get app details with reviews and related apps
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $app = AppStoreApp::where('slug', $slug)
                ->published()
                ->approved()
                ->with([
                    'categories',
                    'developer',
                    'dependencies' => function ($query) {
                        $query->published()->approved();
                    },
                    'reviews' => function ($query) {
                        $query->approved()
                            ->with('user')
                            ->latest()
                            ->take(10);
                    }
                ])
                ->firstOrFail();

            // Check if user has already purchased this app
            $hasPurchased = false;
            $isInstalled = false;
            $canInstall = false;

            if ($user = $request->user()) {
                $hasPurchased = $app->purchases()
                    ->where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->exists();

                $isInstalled = $app->installations()
                    ->where('team_id', $user->currentTeam->id)
                    ->where('is_active', true)
                    ->exists();

                $canInstall = $app->canBeInstalledBy($user);
            }

            // Get related apps from same categories
            $relatedApps = AppStoreApp::published()
                ->approved()
                ->whereHas('categories', function ($query) use ($app) {
                    $query->whereIn('app_store_category_id', $app->categories->pluck('id'));
                })
                ->where('id', '!=', $app->id)
                ->with(['categories', 'developer'])
                ->take(6)
                ->get();

            return response()->json([
                'app' => $app,
                'has_purchased' => $hasPurchased,
                'is_installed' => $isInstalled,
                'can_install' => $canInstall,
                'installation_price' => $user ? $app->getInstallationPrice($user) : $app->price,
                'related_apps' => $relatedApps,
            ]);

        } catch (\Exception $e) {
            Log::error('AppStore show error: ' . $e->getMessage());
            return response()->json(['error' => 'App not found'], 404);
        }
    }

    /**
     * Purchase an app
     */
    public function purchase(Request $request, string $slug): JsonResponse
    {
        try {
            $request->validate([
                'payment_method' => 'required|string',
                'subscription_type' => 'nullable|in:monthly,yearly',
            ]);

            $app = AppStoreApp::where('slug', $slug)
                ->published()
                ->approved()
                ->firstOrFail();

            $user = $request->user();
            $team = $user->currentTeam;

            // Check if app is free
            if ($app->isFree()) {
                return response()->json(['error' => 'This app is free'], 400);
            }

            // Check if already purchased
            $existingPurchase = $app->purchases()
                ->where('user_id', $user->id)
                ->where('team_id', $team->id)
                ->where('status', 'completed')
                ->first();

            if ($existingPurchase && !$app->isSubscription()) {
                return response()->json(['error' => 'App already purchased'], 400);
            }

            DB::beginTransaction();

            try {
                $purchase = $this->appStoreService->createPurchase($app, $user, $team, [
                    'payment_method' => $request->payment_method,
                    'subscription_type' => $request->subscription_type,
                ]);

                // Create payment intent with payment provider
                $paymentIntent = $this->appStoreService->createPaymentIntent($purchase);

                DB::commit();

                return response()->json([
                    'purchase_id' => $purchase->id,
                    'payment_intent' => $paymentIntent,
                    'client_secret' => $paymentIntent['client_secret'] ?? null,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('AppStore purchase error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create purchase'], 500);
        }
    }

    /**
     * Install an app
     */
    public function install(Request $request, string $slug): JsonResponse
    {
        try {
            $app = AppStoreApp::where('slug', $slug)
                ->published()
                ->approved()
                ->firstOrFail();

            $user = $request->user();
            $team = $user->currentTeam;

            // Check if app can be installed
            if (!$app->canBeInstalledBy($user)) {
                return response()->json(['error' => 'App cannot be installed'], 400);
            }

            // Check payment for paid apps
            if (!$app->isFree()) {
                $hasPurchase = $app->purchases()
                    ->where('user_id', $user->id)
                    ->where('team_id', $team->id)
                    ->where('status', 'completed')
                    ->exists();

                if (!$hasPurchase) {
                    return response()->json(['error' => 'App must be purchased first'], 400);
                }
            }

            DB::beginTransaction();

            try {
                $installation = $this->appStoreService->installApp($app, $user, $team);

                // Handle different app types
                switch ($app->app_type) {
                    case 'laravel_module':
                        $this->moduleService->installModule($app, $installation);
                        break;
                    case 'wordpress_plugin':
                        $this->appStoreService->installWordPressPlugin($app, $installation);
                        break;
                    case 'iframe':
                    case 'vue':
                    case 'external':
                        // These are handled by the frontend
                        break;
                }

                // Update app statistics
                $app->incrementDownloadCount();
                $app->incrementActiveInstalls();

                DB::commit();

                return response()->json([
                    'message' => 'App installed successfully',
                    'installation' => $installation->load(['appStoreApp', 'purchase']),
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('AppStore install error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to install app'], 500);
        }
    }

    /**
     * Uninstall an app
     */
    public function uninstall(Request $request, string $slug): JsonResponse
    {
        try {
            $user = $request->user();
            $team = $user->currentTeam;

            $installation = InstalledApp::where('app_id', $slug)
                ->where('team_id', $team->id)
                ->where('is_active', true)
                ->firstOrFail();

            DB::beginTransaction();

            try {
                // Handle different app types
                if ($installation->isLaravelModule()) {
                    $this->moduleService->uninstallModule($installation);
                } elseif ($installation->isWordPressPlugin()) {
                    $this->appStoreService->uninstallWordPressPlugin($installation);
                }

                // Deactivate installation
                $installation->update(['is_active' => false]);

                // Update app statistics
                if ($installation->appStoreApp) {
                    $installation->appStoreApp->decrementActiveInstalls();
                }

                DB::commit();

                return response()->json(['message' => 'App uninstalled successfully']);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('AppStore uninstall error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to uninstall app'], 500);
        }
    }

    /**
     * Submit a review for an app
     */
    public function submitReview(Request $request, string $slug): JsonResponse
    {
        try {
            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'title' => 'nullable|string|max:255',
                'review' => 'nullable|string|max:2000',
            ]);

            $app = AppStoreApp::where('slug', $slug)
                ->published()
                ->approved()
                ->firstOrFail();

            $user = $request->user();
            $team = $user->currentTeam;

            // Check if app is installed
            $isInstalled = $app->installations()
                ->where('team_id', $team->id)
                ->where('is_active', true)
                ->exists();

            if (!$isInstalled) {
                return response()->json(['error' => 'App must be installed to review'], 400);
            }

            // Check if user already reviewed
            $existingReview = $app->reviews()
                ->where('user_id', $user->id)
                ->first();

            if ($existingReview) {
                return response()->json(['error' => 'You have already reviewed this app'], 400);
            }

            // Check if it's a verified purchase
            $isVerifiedPurchase = $app->purchases()
                ->where('user_id', $user->id)
                ->where('team_id', $team->id)
                ->where('status', 'completed')
                ->exists();

            $review = AppStoreReview::create([
                'app_store_app_id' => $app->id,
                'user_id' => $user->id,
                'team_id' => $team->id,
                'rating' => $request->rating,
                'title' => $request->title,
                'review' => $request->review,
                'is_verified_purchase' => $isVerifiedPurchase,
                'version_reviewed' => $app->version,
                'is_approved' => true, // Auto-approve for now
            ]);

            // Update app rating
            $app->updateRating($request->rating);

            return response()->json([
                'message' => 'Review submitted successfully',
                'review' => $review->load('user'),
            ]);

        } catch (\Exception $e) {
            Log::error('AppStore review error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to submit review'], 500);
        }
    }

    /**
     * Get installed apps for current team
     */
    public function installed(Request $request): JsonResponse
    {
        try {
            $team = $request->user()->currentTeam;

            $installedApps = InstalledApp::where('team_id', $team->id)
                ->where('is_active', true)
                ->with(['appStoreApp.categories', 'purchase'])
                ->orderBy('last_used_at', 'desc')
                ->get();

            return response()->json(['installed_apps' => $installedApps]);

        } catch (\Exception $e) {
            Log::error('AppStore installed error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get installed apps'], 500);
        }
    }

    /**
     * Get user's app purchases
     */
    public function purchases(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $team = $user->currentTeam;

            $purchases = AppStorePurchase::where('user_id', $user->id)
                ->where('team_id', $team->id)
                ->with(['app'])
                ->latest()
                ->paginate(20);

            return response()->json([
                'purchases' => $purchases->items(),
                'pagination' => [
                    'current_page' => $purchases->currentPage(),
                    'last_page' => $purchases->lastPage(),
                    'per_page' => $purchases->perPage(),
                    'total' => $purchases->total(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('AppStore purchases error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get purchases'], 500);
        }
    }

    /**
     * Submit a new app for review (tenant developer)
     */
    public function submitApp(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:500',
                'detailed_description' => 'required|string',
                'app_type' => 'required|string|in:module,vue,iframe,api_integration,external',
                'pricing_type' => 'required|string|in:free,one_time,monthly,freemium',
                'price' => 'nullable|numeric|min:0',
                'monthly_price' => 'nullable|numeric|min:0',
                'currency' => 'required|string|max:3',
                'categories' => 'required|array',
                'categories.*' => 'exists:app_store_categories,slug',
                'tags' => 'array',
                'icon_class' => 'required|string',
                'icon_bg_class' => 'required|string',
                'app_package' => 'required|file|mimes:zip|max:51200', // 50MB max
                'icon' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                'screenshots' => 'array|max:5',
                'screenshots.*' => 'image|mimes:jpeg,png,jpg|max:2048',
                'is_public' => 'boolean',
                'iframe_config' => 'nullable|array',
            ]);

            $teamId = auth()->user()->currentTeam->id;
            $userId = auth()->id();

            // Store uploaded files
            $packagePath = $request->file('app_package')->store('app-packages', 'public');
            $iconPath = $request->file('icon')->store('app-icons', 'public');
            
            $screenshotPaths = [];
            if ($request->has('screenshots')) {
                foreach ($request->file('screenshots') as $screenshot) {
                    $screenshotPaths[] = $screenshot->store('app-screenshots', 'public');
                }
            }

            // Create slug from name
            $slug = \Str::slug($request->name);
            
            // Ensure unique slug
            $counter = 1;
            $originalSlug = $slug;
            while (AppStoreApp::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Create the app record
            $app = AppStoreApp::create([
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'detailed_description' => $request->detailed_description,
                'icon' => $iconPath,
                'screenshots' => $screenshotPaths,
                'app_type' => $request->app_type,
                'version' => '1.0.0',
                'developer_id' => $userId,
                'developer_name' => auth()->user()->name,
                'developer_email' => auth()->user()->email,
                'pricing_type' => $request->pricing_type,
                'price' => $request->price,
                'monthly_price' => $request->monthly_price,
                'currency' => $request->currency,
                'is_published' => false,
                'is_featured' => false,
                'is_approved' => false,
                'approval_status' => $request->is_public ? 'pending' : 'auto_approved',
                'tags' => $request->tags ?? [],
                'icon_class' => $request->icon_class,
                'icon_bg_class' => $request->icon_bg_class,
                'iframe_config' => $request->iframe_config,
                'requires_admin_approval' => $request->is_public,
                'package_path' => $packagePath,
                'submitted_by_team_id' => $teamId,
            ]);

            // Associate categories
            $categories = AppStoreCategory::whereIn('slug', $request->categories)->get();
            $app->categories()->attach($categories);

            // Trigger AI security scan
            $scanResult = $this->securityScanner->scanAppPackage($app);
            
            // Handle scan results
            if ($scanResult->isBlocked()) {
                // App was blocked due to security issues
                $message = 'App submission blocked due to security concerns. ';
                $message .= 'Our AI detected potential security risks in your application. ';
                $message .= 'Please review the security report and contact support if you believe this is an error.';
                
                // Notify tenant about security issues
                $this->notifyTenantOfSecurityIssues($app, $scanResult);
                
                // Notify central admin for review
                $this->notifyAdminOfBlockedApp($app, $scanResult);
                
                return response()->json([
                    'message' => $message,
                    'app' => $app,
                    'security_scan' => $scanResult->getScanSummary(),
                    'status' => 'blocked'
                ], 422);
            }
            
            // Auto-approve private apps if they pass security scan
            if (!$request->is_public) {
                $app->update([
                    'is_approved' => true,
                    'is_published' => true,
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                    'published_at' => now(),
                ]);
                
                $message = 'App submitted successfully and passed security scan. Your app is now available in your tenant.';
            } else {
                $message = 'App submitted for review and passed initial security scan. You will be notified when it is approved for public distribution.';
            }

            return response()->json([
                'message' => $message,
                'app' => $app,
                'security_scan' => $scanResult->getScanSummary(),
                'status' => 'success'
            ], 201);

        } catch (\Exception $e) {
            Log::error('App submission failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to submit app'], 500);
        }
    }

    /**
     * Get apps submitted by current tenant
     */
    public function myApps(Request $request): JsonResponse
    {
        try {
            $teamId = auth()->user()->currentTeam->id;
            
            $apps = AppStoreApp::where('submitted_by_team_id', $teamId)
                ->with(['categories'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json($apps);

        } catch (\Exception $e) {
            Log::error('Failed to fetch my apps: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch apps'], 500);
        }
    }

    /**
     * Notify tenant about security issues found in their app
     */
    private function notifyTenantOfSecurityIssues(AppStoreApp $app, SecurityScanResult $scanResult): void
    {
        try {
            $tenant = auth()->user();
            $team = $tenant->currentTeam;
            
            // Create notification for tenant
            $notification = [
                'type' => 'app_security_blocked',
                'title' => 'App Submission Blocked - Security Issues Detected',
                'message' => "Your app '{$app->name}' was blocked due to security concerns detected by our AI security scanner.",
                'data' => [
                    'app_id' => $app->id,
                    'app_name' => $app->name,
                    'risk_level' => $scanResult->risk_level,
                    'security_score' => $scanResult->security_score,
                    'vulnerability_count' => count($scanResult->vulnerabilities_found),
                    'scan_id' => $scanResult->id,
                ],
                'action_url' => "/app-store/my-apps/{$app->id}/security-report",
                'importance' => 'high',
            ];

            // Store notification in database
            \App\Models\Notification::create([
                'type' => $notification['type'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'data' => $notification['data'],
                'notifiable_type' => get_class($team),
                'notifiable_id' => $team->id,
                'channels' => ['database', 'broadcast'],
                'importance' => $notification['importance'],
            ]);

            Log::info("Notified tenant about blocked app", [
                'app_id' => $app->id,
                'tenant_id' => $team->id,
                'risk_level' => $scanResult->risk_level
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to notify tenant about security issues', [
                'app_id' => $app->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify central admin about blocked app requiring review
     */
    private function notifyAdminOfBlockedApp(AppStoreApp $app, SecurityScanResult $scanResult): void
    {
        try {
            // Get admin users (users with admin role or permissions)
            $adminUsers = \App\Models\User::whereHas('roles', function($query) {
                $query->where('name', 'admin');
            })->get();

            foreach ($adminUsers as $admin) {
                $notification = [
                    'type' => 'app_security_review_required',
                    'title' => 'App Requires Security Review',
                    'message' => "App '{$app->name}' was automatically blocked due to security issues and requires admin review.",
                    'data' => [
                        'app_id' => $app->id,
                        'app_name' => $app->name,
                        'developer_name' => $app->developer_name,
                        'submitted_by_team_id' => $app->submitted_by_team_id,
                        'risk_level' => $scanResult->risk_level,
                        'security_score' => $scanResult->security_score,
                        'vulnerability_count' => count($scanResult->vulnerabilities_found),
                        'scan_id' => $scanResult->id,
                        'critical_vulnerabilities' => count($scanResult->getCriticalVulnerabilities()),
                    ],
                    'action_url' => "/admin/app-store/security-review/{$app->id}",
                    'importance' => 'high',
                ];

                // Store notification for admin
                \App\Models\Notification::create([
                    'type' => $notification['type'],
                    'title' => $notification['title'],
                    'message' => $notification['message'],
                    'data' => $notification['data'],
                    'notifiable_type' => get_class($admin),
                    'notifiable_id' => $admin->id,
                    'channels' => ['database', 'broadcast', 'email'],
                    'importance' => $notification['importance'],
                ]);
            }

            Log::info("Notified admins about blocked app requiring review", [
                'app_id' => $app->id,
                'admin_count' => $adminUsers->count(),
                'risk_level' => $scanResult->risk_level
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to notify admins about blocked app', [
                'app_id' => $app->id,
                'error' => $e->getMessage()
            ]);
        }
    }
} 