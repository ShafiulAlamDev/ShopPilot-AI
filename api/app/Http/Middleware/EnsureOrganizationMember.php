<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user has at least one of the required
 * organization-level roles within the current organization context.
 *
 * Usage: ->middleware('org.member:org_admin,store_manager')
 * Without roles: ->middleware('org.member') — just checks membership.
 */
class EnsureOrganizationMember
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user         = $request->user();
        $organization = app('current_organization');

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Super admins bypass all org-level role checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$organization) {
            return response()->json(['message' => 'Organization context required.'], 400);
        }

        $userRole = $user->roleInOrganization($organization);

        if (!$userRole) {
            return response()->json([
                'message' => 'You are not a member of this organization.',
            ], 403);
        }

        if (!empty($roles) && !in_array($userRole, $roles, true)) {
            return response()->json([
                'message'        => 'You do not have the required role to perform this action.',
                'required_roles' => $roles,
                'your_role'      => $userRole,
            ], 403);
        }

        return $next($request);
    }
}
