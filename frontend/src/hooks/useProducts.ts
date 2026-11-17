'use client'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import api from '@/lib/api'
import { toast } from 'sonner'

export function useProducts(orgSlug: string, storeSlug: string, params?: Record<string, any>) {
  return useQuery({
    queryKey: ['products', orgSlug, storeSlug, params],
    queryFn: async () => {
      const res = await api.get(`/v1/organizations/${orgSlug}/stores/${storeSlug}/products`, { params })
      return res.data
    },
    enabled: !!orgSlug && !!storeSlug,
  })
}

export function useProduct(orgSlug: string, storeSlug: string, productId: string) {
  return useQuery({
    queryKey: ['product', orgSlug, storeSlug, productId],
    queryFn: async () => {
      const res = await api.get(`/v1/organizations/${orgSlug}/stores/${storeSlug}/products/${productId}`)
      return res.data.data
    },
    enabled: !!productId,
  })
}

export function useCreateProduct(orgSlug: string, storeSlug: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (data: any) => {
      const res = await api.post(`/v1/organizations/${orgSlug}/stores/${storeSlug}/products`, data)
      return res.data
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['products', orgSlug, storeSlug] })
      toast.success('Product created!')
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Failed to create product'),
  })
}

export function useUpdateProduct(orgSlug: string, storeSlug: string, productId: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (data: any) => {
      const res = await api.put(`/v1/organizations/${orgSlug}/stores/${storeSlug}/products/${productId}`, data)
      return res.data
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['products', orgSlug, storeSlug] })
      qc.invalidateQueries({ queryKey: ['product', orgSlug, storeSlug, productId] })
      toast.success('Product updated!')
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Failed to update product'),
  })
}

export function useDeleteProduct(orgSlug: string, storeSlug: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (productId: number) => {
      await api.delete(`/v1/organizations/${orgSlug}/stores/${storeSlug}/products/${productId}`)
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['products', orgSlug, storeSlug] })
      toast.success('Product deleted.')
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'Failed to delete product'),
  })
}

export function useGenerateDescription(orgSlug: string, storeSlug: string, productId: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async () => {
      const res = await api.post(
        `/v1/organizations/${orgSlug}/stores/${storeSlug}/products/${productId}/ai/description`
      )
      return res.data
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['product', orgSlug, storeSlug, productId] })
      toast.success('AI description generated!')
    },
    onError: (e: any) => toast.error(e.response?.data?.message ?? 'AI generation failed'),
  })
}
