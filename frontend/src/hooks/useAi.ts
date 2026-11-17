'use client'
import { useMutation, useQuery } from '@tanstack/react-query'
import api from '@/lib/api'
import { toast } from 'sonner'

export function useAiChat(orgSlug: string, storeSlug: string) {
  return useMutation({
    mutationFn: async (data: { message: string; history: Array<{ role: string; content: string }> }) => {
      const res = await api.post(`/v1/organizations/${orgSlug}/stores/${storeSlug}/ai/chat`, data)
      return res.data
    },
    onError: () => toast.error('AI assistant error. Please try again.'),
  })
}

export function useSemanticSearch(orgSlug: string, storeSlug: string) {
  return useMutation({
    mutationFn: async (data: { query: string; limit?: number }) => {
      const res = await api.post(`/v1/organizations/${orgSlug}/stores/${storeSlug}/ai/search`, data)
      return res.data
    },
  })
}

export function useAiCompare(orgSlug: string, storeSlug: string) {
  return useMutation({
    mutationFn: async (productIds: number[]) => {
      const res = await api.post(`/v1/organizations/${orgSlug}/stores/${storeSlug}/ai/compare`, {
        product_ids: productIds,
      })
      return res.data
    },
    onError: () => toast.error('AI comparison failed. Please try again.'),
  })
}

export function useAiUsage(orgSlug: string, period = 'month') {
  return useQuery({
    queryKey: ['ai-usage', orgSlug, period],
    queryFn: async () => {
      const res = await api.get(`/v1/organizations/${orgSlug}/ai/usage`, { params: { period } })
      return res.data
    },
    enabled: !!orgSlug,
  })
}
