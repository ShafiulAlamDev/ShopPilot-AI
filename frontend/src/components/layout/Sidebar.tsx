'use client'
import Link from 'next/link'
import { usePathname, useRouter } from 'next/navigation'
import {
  LayoutDashboard,
  Store,
  Settings,
  LogOut,
  ChevronDown,
  Bot,
  ShoppingBag,
} from 'lucide-react'
import { cn } from '@/lib/utils'
import { useAuthStore } from '@/store/authStore'
import { useLogout } from '@/hooks/useAuth'
import { useOrganizations } from '@/hooks/useOrganizations'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const navItems = [
  { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
  { label: 'Stores', href: '/stores', icon: Store },
  { label: 'Settings', href: '/settings', icon: Settings },
]

export function Sidebar() {
  const pathname = usePathname()
  const { user, currentOrg, organizations, setCurrentOrg } = useAuthStore()
  const { mutate: logout, isPending } = useLogout()
  useOrganizations()

  return (
    <aside className="flex h-screen w-64 flex-col border-r bg-slate-900 text-white">
      {/* Logo */}
      <div className="flex items-center gap-3 border-b border-slate-700 px-6 py-5">
        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-500">
          <Bot className="h-5 w-5 text-white" />
        </div>
        <span className="text-lg font-bold tracking-tight">ShopPilot AI</span>
      </div>

      {/* Org Selector */}
      {organizations.length > 0 && (
        <div className="border-b border-slate-700 px-4 py-3">
          <p className="mb-1.5 text-xs font-medium text-slate-400 uppercase tracking-wider">Organization</p>
          <Select
            value={currentOrg?.slug ?? ''}
            onValueChange={(slug) => {
              const org = organizations.find((o) => o.slug === slug)
              if (org) setCurrentOrg(org)
            }}
          >
            <SelectTrigger className="border-slate-600 bg-slate-800 text-white hover:bg-slate-700">
              <SelectValue placeholder="Select org" />
            </SelectTrigger>
            <SelectContent>
              {organizations.map((org) => (
                <SelectItem key={org.slug} value={org.slug}>
                  {org.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      )}

      {/* Navigation */}
      <nav className="flex-1 space-y-1 px-3 py-4">
        {navItems.map(({ label, href, icon: Icon }) => {
          const active = pathname === href || pathname.startsWith(href + '/')
          return (
            <Link
              key={href}
              href={href}
              className={cn(
                'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                active
                  ? 'bg-blue-600 text-white'
                  : 'text-slate-300 hover:bg-slate-800 hover:text-white'
              )}
            >
              <Icon className="h-4 w-4 shrink-0" />
              {label}
            </Link>
          )
        })}
      </nav>

      {/* User area */}
      <div className="border-t border-slate-700 p-4">
        <div className="mb-3 flex items-center gap-3">
          <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-xs font-bold uppercase">
            {user?.name?.charAt(0) ?? 'U'}
          </div>
          <div className="min-w-0 flex-1">
            <p className="truncate text-sm font-medium text-white">{user?.name}</p>
            <p className="truncate text-xs text-slate-400">{user?.email}</p>
          </div>
        </div>
        <Button
          variant="ghost"
          size="sm"
          className="w-full justify-start text-slate-300 hover:bg-slate-800 hover:text-white"
          onClick={() => logout()}
          disabled={isPending}
        >
          <LogOut className="mr-2 h-4 w-4" />
          {isPending ? 'Logging out…' : 'Logout'}
        </Button>
      </div>
    </aside>
  )
}
