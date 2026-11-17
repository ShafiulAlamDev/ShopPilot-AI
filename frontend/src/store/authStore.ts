import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { User, Organization } from '@/lib/types'

interface AuthState {
  token: string | null
  user: User | null
  organizations: Organization[]
  currentOrg: Organization | null
  currentStore: { id: number; slug: string; name: string } | null
  setAuth: (token: string, user: User) => void
  setOrganizations: (orgs: Organization[]) => void
  setCurrentOrg: (org: Organization) => void
  setCurrentStore: (store: { id: number; slug: string; name: string } | null) => void
  logout: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      token: null,
      user: null,
      organizations: [],
      currentOrg: null,
      currentStore: null,
      setAuth: (token, user) => set({ token, user }),
      setOrganizations: (organizations) =>
        set({ organizations, currentOrg: organizations[0] ?? null }),
      setCurrentOrg: (org) => set({ currentOrg: org }),
      setCurrentStore: (store) => set({ currentStore: store }),
      logout: () =>
        set({ token: null, user: null, organizations: [], currentOrg: null, currentStore: null }),
    }),
    { name: 'shoppilot-auth' }
  )
)
