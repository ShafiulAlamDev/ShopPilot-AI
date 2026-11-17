<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Store;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    /**
     * Create a new organization, its default store, and attach the creator as org_admin.
     */
    public function createOrganization(User $owner, array $data): Organization
    {
        return DB::transaction(function () use ($owner, $data) {
            $organization = Organization::create([
                'name'  => $data['name'],
                'email' => $data['email'] ?? $owner->email,
            ]);

            // Default store
            Store::create([
                'organization_id' => $organization->id,
                'name'            => $data['name'] . ' Store',
                'currency'        => $data['currency'] ?? 'USD',
                'timezone'        => $data['timezone'] ?? 'UTC',
            ]);

            // Attach owner as org_admin
            $organization->users()->attach($owner->id, [
                'role'       => 'org_admin',
                'is_primary' => true,
                'is_active'  => true,
                'joined_at'  => now(),
            ]);

            return $organization->fresh(['stores', 'users']);
        });
    }

    /**
     * Send an invitation to join the organization.
     */
    public function inviteUser(
        Organization $organization,
        User $invitedBy,
        string $email,
        string $role = 'store_manager'
    ): UserInvitation {
        // Cancel any existing pending invitation for this email
        $organization->invitations()
                     ->where('email', $email)
                     ->where('status', 'pending')
                     ->update(['status' => 'cancelled']);

        $invitation = $organization->invitations()->create([
            'invited_by' => $invitedBy->id,
            'email'      => $email,
            'role'       => $role,
        ]);

        // TODO Sprint 1 email: Mail::to($email)->send(new InvitationMail($invitation));

        return $invitation;
    }

    /**
     * Accept an invitation — adds the user to the organization with the invited role.
     */
    public function acceptInvitation(UserInvitation $invitation, User $user): void
    {
        if (!$invitation->isPending()) {
            throw new \RuntimeException('Invitation is no longer valid or has expired.');
        }

        DB::transaction(function () use ($invitation, $user) {
            $isPrimary = !$user->organizations()->exists();

            $invitation->organization->users()->syncWithoutDetaching([
                $user->id => [
                    'role'       => $invitation->role,
                    'is_primary' => $isPrimary,
                    'is_active'  => true,
                    'joined_at'  => now(),
                ],
            ]);

            $invitation->markAsAccepted();
        });
    }

    /**
     * Remove a user from the organization.
     */
    public function removeUser(Organization $organization, User $user): void
    {
        $organization->users()->detach($user->id);
    }

    /**
     * Update a user's role within the organization.
     */
    public function updateUserRole(Organization $organization, User $user, string $role): void
    {
        $validRoles = ['org_admin', 'store_manager', 'customer'];

        if (!in_array($role, $validRoles, true)) {
            throw new \InvalidArgumentException("Invalid role: {$role}");
        }

        $organization->users()->updateExistingPivot($user->id, ['role' => $role]);
    }
}
