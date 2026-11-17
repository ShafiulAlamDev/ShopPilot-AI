'use client'
import { useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { useAuthStore } from '@/store/authStore'

export function useOrganizations() {
  const { setOrganizations } = useAuthStore()
  return useQuery({
    queryKey: ['organizations'],
    queryFn: async () => {
      const res = await api.get('/v1/organizations')
      setOrganizations(res.data.organizations)
      return res.data.organizations
    },
  })
}

export function useOrganizationMembers(orgSlug: string) {
  return useQuery({
    queryKey: ['org-members', orgSlug],
    queryFn: async () => {
      const res = await api.get(`/v1/organizations/${orgSlug}/members`)
      return res.data.members
    },
    enabled: !!orgSlug,
  })
}
