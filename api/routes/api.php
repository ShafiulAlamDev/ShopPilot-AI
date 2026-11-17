<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductCategoryController;
use App\Http\Controllers\Api\V1\AiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes – ShopPilot AI v1
|--------------------------------------------------------------------------
|
| All routes versioned under /api/v1/.
| Organization-scoped routes use {organization} slug parameter,
| resolved by the SetOrganizationContext middleware.
|
*/

Route::prefix('v1')->group(function () {

    // -------------------------------------------------------------------------
    // Public Routes (no authentication required)
    // -------------------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);
    });

    // -------------------------------------------------------------------------
    // Authenticated Routes
    // -------------------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {

        // Auth management
        Route::prefix('auth')->group(function () {
            Route::post('logout',                           [AuthController::class, 'logout']);
            Route::get('me',                                [AuthController::class, 'me']);
            Route::post('email/verification-notification',  [AuthController::class, 'sendVerificationEmail']);
            Route::post('invitations/{token}/accept',       [OrganizationController::class, 'acceptInvitation']);
        });

        // User's organizations list + create
        Route::get('organizations',  [OrganizationController::class, 'index']);
        Route::post('organizations', [OrganizationController::class, 'store']);

        // -------------------------------------------------------------------------
        // Organization-Scoped Routes
        // Middleware chain: SetOrganizationContext → EnsureOrganizationMember
        // -------------------------------------------------------------------------
        Route::prefix('organizations/{organization}')
             ->middleware(['org.context', 'org.member'])
             ->group(function () {

            // Organization details & update
            Route::get('/', [OrganizationController::class, 'show']);
            Route::put('/', [OrganizationController::class, 'update'])
                 ->middleware('org.member:org_admin');

            // Members management
            Route::prefix('members')->group(function () {
                Route::get('/',                   [OrganizationController::class, 'members']);
                Route::post('invite',             [OrganizationController::class, 'invite'])
                     ->middleware('org.member:org_admin');
                Route::delete('{user}',           [OrganizationController::class, 'removeMember'])
                     ->middleware('org.member:org_admin');
                Route::patch('{user}/role',       [OrganizationController::class, 'updateMemberRole'])
                     ->middleware('org.member:org_admin');
            });

            // -------------------------------------------------------------------------
            // Stores management
            // -------------------------------------------------------------------------
            Route::prefix('stores')->group(function () {
                Route::get('/',  [StoreController::class, 'index']);
                Route::post('/', [StoreController::class, 'store'])
                     ->middleware('org.member:org_admin');

                Route::prefix('{store}')->group(function () {
                    Route::get('/',    [StoreController::class, 'show']);
                    Route::put('/',    [StoreController::class, 'update'])
                         ->middleware('org.member:org_admin');
                    Route::delete('/', [StoreController::class, 'destroy'])
                         ->middleware('org.member:org_admin');

                    // Store settings
                    Route::get('settings',  [StoreController::class, 'settings']);
                    Route::post('settings', [StoreController::class, 'updateSettings'])
                         ->middleware('org.member:org_admin');

                    // Products
                    Route::get('products',              [ProductController::class, 'index']);
                    Route::post('products',             [ProductController::class, 'store'])
                         ->middleware('org.member:org_admin,store_manager');
                    Route::get('products/{product}',    [ProductController::class, 'show']);
                    Route::put('products/{product}',    [ProductController::class, 'update'])
                         ->middleware('org.member:org_admin,store_manager');
                    Route::delete('products/{product}', [ProductController::class, 'destroy'])
                         ->middleware('org.member:org_admin');

                    // Categories
                    Route::get('categories',               [ProductCategoryController::class, 'index']);
                    Route::post('categories',              [ProductCategoryController::class, 'store'])
                         ->middleware('org.member:org_admin,store_manager');
                    Route::get('categories/{category}',    [ProductCategoryController::class, 'show']);
                    Route::put('categories/{category}',    [ProductCategoryController::class, 'update'])
                         ->middleware('org.member:org_admin,store_manager');
                    Route::delete('categories/{category}', [ProductCategoryController::class, 'destroy'])
                         ->middleware('org.member:org_admin');

                    // -------------------------------------------------------------------------
                    // AI Routes — store-scoped (nested inside {store})
                    // -------------------------------------------------------------------------
                    // Shopping assistant chat
                    Route::post('ai/chat',    [AiController::class, 'chat']);
                    // Semantic search
                    Route::post('ai/search',  [AiController::class, 'semanticSearch']);
                    // Product comparison
                    Route::post('ai/compare', [AiController::class, 'compare']);

                    // Per-product AI actions
                    Route::post('products/{product}/ai/description', [AiController::class, 'generateDescription'])
                         ->middleware('org.member:org_admin,store_manager');
                    Route::post('products/{product}/ai/embed', [AiController::class, 'embedProduct'])
                         ->middleware('org.member:org_admin,store_manager');
                });
            });

            // -------------------------------------------------------------------------
            // AI Routes — org-scoped
            // -------------------------------------------------------------------------
            // Org-level AI usage stats
            Route::get('ai/usage', [AiController::class, 'usage']);

        });

    });

});
