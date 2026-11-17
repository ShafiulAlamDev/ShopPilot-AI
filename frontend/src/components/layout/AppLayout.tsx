'use client'
import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { useAuthStore } from '@/store/authStore'
import { Sidebar } from './Sidebar'

export function AppLayout({ children }: { children: React.ReactNode }) {
  const { token } = useAuthStore()
  const router = useRouter()

  useEffect(() => {
    if (!token) {
      router.replace('/login')
    }
  }, [token, router])

  if (!token) return null

  return (
    <div className="flex h-screen overflow-hidden bg-slate-50">
      <Sidebar />
      <main className="flex-1 overflow-y-auto">
        <div className="min-h-full p-6">{children}</div>
      </main>
    </div>
  )
}
