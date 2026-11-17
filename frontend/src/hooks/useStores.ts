'use client'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { toast } from 'sonner'

export function useStores(orgSlug: string) {
  return useQuery({
    queryKey: ['stores', orgSlug],
    queryFn: async () => {
      const res = await api.get(`/v1/organizations/${orgSlug}/stores`)
      return res.data.stores
    },
    enabled: !!orgSlug,
  })
}

export function useStore(orgSlug: string, storeSlug: string) {
  return useQuery({
    queryKey: ['store', orgSlug, storeSlug],
    queryFn: async () => {
      const res = await api.get(`/v1/organizations/${orgSlug}/stores/${storeSlug}`)
      return res.data.store
    },
    enabled: !!orgSlug && !!storeSlug,
  })
}

export function useCreateStore(orgSlug: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (data: any) => {
      const res = await api.post(`/v1/organizations/${orgSlug}/stores`, data)
      return res.data
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['stores', orgSlug] })
      toast.success('Store created!')
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Failed to create store'),
  })
}

export function useUpdateStore(orgSlug: string, storeSlug: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (data: any) => {
      const res = await api.put(`/v1/organizations/${orgSlug}/stores/${storeSlug}`, data)
      return res.data
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['stores', orgSlug] })
      qc.invalidateQueries({ queryKey: ['store', orgSlug, storeSlug] })
      toast.success('Store updated!')
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Failed to update store'),
  })
}
