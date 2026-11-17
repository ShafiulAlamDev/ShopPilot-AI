'use client'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Loader2, Trash2, UserPlus } from 'lucide-react'
import { PageHeader } from '@/components/layout/PageHeader'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { Separator } from '@/components/ui/separator'
import { useAuthStore } from '@/store/authStore'
import { useOrganizationMembers } from '@/hooks/useOrganizations'
import type { User } from '@/lib/types'

const orgSchema = z.object({
  name: z.string().min(2),
  email: z.string().email().optional().or(z.literal('')),
  website: z.string().url().optional().or(z.literal('')),
  phone: z.string().optional(),
})

type OrgFormData = z.infer<typeof orgSchema>

const inviteSchema = z.object({
  email: z.string().email('Invalid email'),
  role: z.string().default('member'),
})

type InviteFormData = z.infer<typeof inviteSchema>

export default function SettingsPage() {
  const { currentOrg } = useAuthStore()
  const orgSlug = currentOrg?.slug ?? ''
  const { data: members, isLoading: membersLoading } = useOrganizationMembers(orgSlug)
  const [inviting, setInviting] = useState(false)

  const orgForm = useForm<OrgFormData>({
    resolver: zodResolver(orgSchema),
    defaultValues: {
      name: currentOrg?.name ?? '',
      email: currentOrg?.email ?? '',
      website: currentOrg?.website ?? '',
      phone: currentOrg?.phone ?? '',
    },
  })

  const inviteForm = useForm<InviteFormData>({
    resolver: zodResolver(inviteSchema),
    defaultValues: { email: '', role: 'member' },
  })

  const onOrgSubmit = (data: OrgFormData) => {
    // TODO: wire to org update endpoint when available
    console.log('Org update:', data)
  }

  const onInvite = (data: InviteFormData) => {
    // TODO: wire to invite endpoint when available
    setInviting(true)
    setTimeout(() => {
      inviteForm.reset()
      setInviting(false)
    }, 1000)
  }

  return (
    <div>
      <PageHeader title="Settings" description="Manage your organization settings and members" />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {/* Org settings */}
        <Card>
          <CardHeader>
            <CardTitle>Organization</CardTitle>
            <CardDescription>Update your organization details</CardDescription>
          </CardHeader>
          <form onSubmit={orgForm.handleSubmit(onOrgSubmit)}>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Name</Label>
                <Input {...orgForm.register('name')} />
              </div>
              <div className="space-y-2">
                <Label>Email</Label>
                <Input type="email" {...orgForm.register('email')} />
              </div>
              <div className="space-y-2">
                <Label>Website</Label>
                <Input placeholder="https://example.com" {...orgForm.register('website')} />
              </div>
              <div className="space-y-2">
                <Label>Phone</Label>
                <Input {...orgForm.register('phone')} />
              </div>
              <div className="flex items-center gap-2 rounded-md border bg-muted p-3">
                <span className="text-sm text-muted-foreground">Plan:</span>
                <Badge variant="secondary" className="capitalize">
                  {currentOrg?.plan ?? 'free'}
                </Badge>
              </div>
            </CardContent>
            <div className="px-6 pb-6">
              <Button type="submit">Save Changes</Button>
            </div>
          </form>
        </Card>

        {/* Members */}
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Members</CardTitle>
              <CardDescription>People with access to this organization</CardDescription>
            </CardHeader>
            <CardContent>
              {membersLoading ? (
                <div className="space-y-3">
                  {[...Array(3)].map((_, i) => <Skeleton key={i} className="h-10 w-full" />)}
                </div>
              ) : members && members.length > 0 ? (
                <div className="space-y-2">
                  {members.map((member: User) => (
                    <div key={member.id} className="flex items-center justify-between rounded-lg border p-3">
                      <div className="flex items-center gap-3">
                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold uppercase text-blue-600">
                          {member.name.charAt(0)}
                        </div>
                        <div>
                          <p className="text-sm font-medium">{member.name}</p>
                          <p className="text-xs text-muted-foreground">{member.email}</p>
                        </div>
                      </div>
                      <Button variant="ghost" size="icon" className="h-8 w-8 text-destructive hover:bg-destructive/10">
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground">No members found.</p>
              )}
            </CardContent>
          </Card>

          {/* Invite */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <UserPlus className="h-4 w-4" />
                Invite Member
              </CardTitle>
            </CardHeader>
            <form onSubmit={inviteForm.handleSubmit(onInvite)}>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Label>Email Address</Label>
                  <Input
                    type="email"
                    placeholder="colleague@example.com"
                    {...inviteForm.register('email')}
                  />
                  {inviteForm.formState.errors.email && (
                    <p className="text-xs text-destructive">
                      {inviteForm.formState.errors.email.message}
                    </p>
                  )}
                </div>
              </CardContent>
              <div className="px-6 pb-6">
                <Button type="submit" disabled={inviting}>
                  {inviting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                  Send Invite
                </Button>
              </div>
            </form>
          </Card>
        </div>
      </div>
    </div>
  )
}
