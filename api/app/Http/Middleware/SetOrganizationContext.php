<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current organization context from:
 *   1. {organization} route parameter (slug)
 *   2. X-Organization request header
 *   3. Subdomain
 *
 * Binds the resolved organization and its ID to the IoC container
 * so the BelongsToOrganization trait and other services can use it.
 */
class SetOrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $this->resolveOrganization($request);

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found or inactive.',
            ], 404);
        }

        $user = $request->user();

        // Non-super-admins must be members of the org
        if ($user && !$user->isSuperAdmin()) {
            if (!$user->belongsToOrganization($organization)) {
                return response()->json([
                    'message' => 'You do not have access to this organization.',
                ], 403);
            }
        }

        // Bind to container for the entire request lifecycle
        app()->instance('current_organization_id', $organization->id);
        app()->instance('current_organization', $organization);

        return $next($request);
    }

    private function resolveOrganization(Request $request): ?Organization
    {
        // 1. Route parameter (highest priority) — {organization} = slug
        if ($slug = $request->route('organization')) {
            return Organization::where('slug', $slug)
                               ->where('is_active', true)
                               ->first();
        }

        // 2. X-Organization header
        if ($slug = $request->header('X-Organization')) {
            return Organization::where('slug', $slug)
                               ->where('is_active', true)
                               ->first();
        }

        // 3. Subdomain (e.g., acme.shoppilot.ai)
        $host    = $request->getHost();
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        if ($appHost && str_ends_with($host, '.' . $appHost)) {
            $subdomain = explode('.', $host)[0];

            return Organization::where('slug', $subdomain)
                               ->where('is_active', true)
                               ->first();
        }

        return null;
    }
}
