<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\InviteUserRequest;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizationService
    ) {}

    /**
     * List all organizations the authenticated user belongs to.
     *
     * GET /api/v1/organizations
     */
    public function index(Request $request): JsonResponse
    {
        $organizations = $request->user()
                                 ->organizations()
                                 ->wherePivot('is_active', true)
                                 ->with('stores')
                                 ->get();

        return response()->json([
            'organizations' => OrganizationResource::collection($organizations),
        ]);
    }

    /**
     * Create a new organization for the authenticated user.
     *
     * POST /api/v1/organizations
     */
    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $organization = $this->organizationService->createOrganization(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message'      => 'Organization created successfully.',
            'organization' => new OrganizationResource($organization->load('stores')),
        ], 201);
    }

    /**
     * Show a single organization (resolved by SetOrganizationContext middleware).
     *
     * GET /api/v1/organizations/{organization}
     */
    public function show(): JsonResponse
    {
        $organization = app('current_organization');

        return response()->json([
            'organization' => new OrganizationResource(
                $organization->load('stores', 'users')
            ),
        ]);
    }

    /**
     * Update organization details.
     *
     * PUT /api/v1/organizations/{organization}
     */
    public function update(StoreOrganizationRequest $request): JsonResponse
    {
        $organization = app('current_organization');
        $organization->update($request->validated());

        return response()->json([
            'message'      => 'Organization updated successfully.',
            'organization' => new OrganizationResource($organization->fresh()),
        ]);
    }

    // -------------------------------------------------------------------------
    // Members
    // -------------------------------------------------------------------------

    /**
     * List active members of the organization.
     *
     * GET /api/v1/organizations/{organization}/members
     */
    public function members(): JsonResponse
    {
        $organization = app('current_organization');

        $members = $organization->users()
                                ->withPivot(['role', 'is_primary', 'is_active', 'joined_at'])
                                ->wherePivot('is_active', true)
                                ->get();

        return response()->json([
            'members' => UserResource::collection($members),
        ]);
    }

    /**
     * Invite a user to join the organization.
     *
     * POST /api/v1/organizations/{organization}/members/invite
     */
    public function invite(InviteUserRequest $request): JsonResponse
    {
        $organization = app('current_organization');

        $invitation = $this->organizationService->inviteUser(
            $organization,
            $request->user(),
            $request->email,
            $request->role
        );

        return response()->json([
            'message'    => "Invitation sent to {$request->email}.",
            'invitation' => [
                'id'         => $invitation->id,
                'email'      => $invitation->email,
                'role'       => $invitation->role,
                'status'     => $invitation->status,
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Accept an invitation by token (authenticated user only).
     *
     * POST /api/v1/auth/invitations/{token}/accept
     */
    public function acceptInvitation(Request $request, string $token): JsonResponse
    {
        $invitation = UserInvitation::where('token', $token)
                                    ->where('status', 'pending')
                                    ->first();

        if (!$invitation || !$invitation->isPending()) {
            return response()->json([
                'message' => 'Invalid or expired invitation.',
            ], 422);
        }

        $this->organizationService->acceptInvitation($invitation, $request->user());

        return response()->json([
            'message'      => 'Invitation accepted. You have joined the organization.',
            'organization' => new OrganizationResource(
                $invitation->organization->load('stores')
            ),
        ]);
    }

    /**
     * Remove a member from the organization.
     *
     * DELETE /api/v1/organizations/{organization}/members/{user}
     */
    public function removeMember(Request $request, User $user): JsonResponse
    {
        $organization = app('current_organization');

        // Cannot remove yourself if you are the only admin
        if ($request->user()->id === $user->id) {
            $adminCount = $organization->users()
                                       ->wherePivot('role', 'org_admin')
                                       ->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'message' => 'You cannot remove yourself as the only administrator.',
                ], 422);
            }
        }

        $this->organizationService->removeUser($organization, $user);

        return response()->json(['message' => 'Member removed successfully.']);
    }

    /**
     * Update a member's role within the organization.
     *
     * PATCH /api/v1/organizations/{organization}/members/{user}/role
     */
    public function updateMemberRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role' => ['required', 'in:org_admin,store_manager,customer'],
        ]);

        $organization = app('current_organization');

        $this->organizationService->updateUserRole($organization, $user, $request->role);

        return response()->json(['message' => 'Member role updated successfully.']);
    }
}
