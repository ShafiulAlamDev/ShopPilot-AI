'use client'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { toast } from 'sonner'

export function useCategories(orgSlug: string, storeSlug: string) {
  return useQuery({
    queryKey: ['categories', orgSlug, storeSlug],
    queryFn: async () => {
      const res = await api.get(`/v1/organizations/${orgSlug}/stores/${storeSlug}/categories`)
      return res.data.categories
    },
    enabled: !!orgSlug && !!storeSlug,
  })
}

export function useCreateCategory(orgSlug: string, storeSlug: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (data: any) => {
      const res = await api.post(`/v1/organizations/${orgSlug}/stores/${storeSlug}/categories`, data)
      return res.data
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['categories', orgSlug, storeSlug] })
      toast.success('Category created!')
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Failed'),
  })
}

export function useDeleteCategory(orgSlug: string, storeSlug: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (categoryId: number) => {
      await api.delete(`/v1/organizations/${orgSlug}/stores/${storeSlug}/categories/${categoryId}`)
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['categories', orgSlug, storeSlug] })
      toast.success('Category deleted.')
    },
  })
}
