'use client'
import { useMutation, useQuery } from '@tanstack/react-query'
import { useRouter } from 'next/navigation'
import api from '@/lib/api'
import { useAuthStore } from '@/store/authStore'
import { toast } from 'sonner'

export function useLogin() {
  const { setAuth } = useAuthStore()
  const router = useRouter()

  return useMutation({
    mutationFn: async (data: { email: string; password: string }) => {
      const res = await api.post('/v1/auth/login', data)
      return res.data
    },
    onSuccess: (data) => {
      localStorage.setItem('token', data.token)
      setAuth(data.token, data.user)
      toast.success('Logged in successfully')
      router.push('/dashboard')
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message ?? 'Login failed')
    },
  })
}

export function useRegister() {
  const { setAuth } = useAuthStore()
  const router = useRouter()

  return useMutation({
    mutationFn: async (data: {
      name: string
      email: string
      password: string
      password_confirmation: string
      organization_name: string
    }) => {
      const res = await api.post('/v1/auth/register', data)
      return res.data
    },
    onSuccess: (data) => {
      localStorage.setItem('token', data.token)
      setAuth(data.token, data.user)
      toast.success('Account created! Please verify your email.')
      router.push('/dashboard')
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message ?? 'Registration failed')
    },
  })
}

export function useLogout() {
  const { logout } = useAuthStore()
  const router = useRouter()

  return useMutation({
    mutationFn: async () => {
      await api.post('/v1/auth/logout')
    },
    onSuccess: () => {
      logout()
      localStorage.removeItem('token')
      localStorage.removeItem('shoppilot-auth')
      router.push('/login')
    },
  })
}

export function useMe() {
  return useQuery({
    queryKey: ['me'],
    queryFn: async () => {
      const res = await api.get('/v1/auth/me')
      return res.data.user
    },
    retry: false,
  })
}
