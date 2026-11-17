'use client'
import Link from 'next/link'
import { Store, Package, Bot, CreditCard, ArrowRight, TrendingUp } from 'lucide-react'
import { PageHeader } from '@/components/layout/PageHeader'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { useAuthStore } from '@/store/authStore'
import { useAiUsage } from '@/hooks/useAi'
import { useStores } from '@/hooks/useStores'
import { formatNumber } from '@/lib/utils'
import type { AiUsageStat } from '@/lib/types'

export default function DashboardPage() {
  const { user, currentOrg } = useAuthStore()
  const orgSlug = currentOrg?.slug ?? ''
  const { data: usageData, isLoading: usageLoading } = useAiUsage(orgSlug)
  const { data: stores, isLoading: storesLoading } = useStores(orgSlug)

  const stats = (usageData?.by_feature ?? usageData?.stats) as AiUsageStat[] | undefined
  const totalRequests = usageData?.totals?.total_requests ?? stats?.reduce((s: number, r: AiUsageStat) => s + r.requests, 0) ?? 0

  return (
    <div>
      <PageHeader
        title={`Welcome back, ${user?.name?.split(' ')[0] ?? 'there'} 👋`}
        description="Here's what's happening with your stores today."
      />

      {/* Stats row */}
      <div className="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          icon={<Store className="h-5 w-5 text-blue-500" />}
          label="Total Stores"
          value={storesLoading ? null : String(stores?.length ?? 0)}
          bg="bg-blue-50"
        />
        <StatCard
          icon={<Package className="h-5 w-5 text-violet-500" />}
          label="Total Products"
          value={storesLoading ? null : String(
            stores?.reduce((s: number, st: any) => s + (st.products_count ?? 0), 0) ?? 0
          )}
          bg="bg-violet-50"
        />
        <StatCard
          icon={<Bot className="h-5 w-5 text-emerald-500" />}
          label="AI Requests (Month)"
          value={usageLoading ? null : formatNumber(totalRequests)}
          bg="bg-emerald-50"
        />
        <StatCard
          icon={<CreditCard className="h-5 w-5 text-amber-500" />}
          label="Plan"
          value={currentOrg?.plan ?? '—'}
          bg="bg-amber-50"
          badge
        />
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {/* AI Usage Table */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <TrendingUp className="h-4 w-4 text-blue-500" />
              AI Usage This Month
            </CardTitle>
            <CardDescription>Breakdown by feature</CardDescription>
          </CardHeader>
          <CardContent>
            {usageLoading ? (
              <div className="space-y-3">
                {[...Array(4)].map((_, i) => <Skeleton key={i} className="h-8 w-full" />)}
              </div>
            ) : stats && stats.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b text-left text-xs font-medium text-muted-foreground">
                      <th className="pb-2">Feature</th>
                      <th className="pb-2 text-right">Requests</th>
                      <th className="pb-2 text-right">Tokens</th>
                      <th className="pb-2 text-right">Cost</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y">
                    {stats.map((s: AiUsageStat) => (
                      <tr key={s.feature}>
                        <td className="py-2 font-medium capitalize">{s.feature.replace(/_/g, ' ')}</td>
                        <td className="py-2 text-right">{formatNumber(s.requests)}</td>
                        <td className="py-2 text-right">{formatNumber(s.total_tokens)}</td>
                        <td className="py-2 text-right">${s.total_cost_usd}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <p className="text-center text-sm text-muted-foreground py-6">No AI usage recorded yet.</p>
            )}
          </CardContent>
        </Card>

        {/* Quick links */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Quick Actions</CardTitle>
            <CardDescription>Get started with common tasks</CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            <QuickLink href="/stores" label="View all stores" icon={<Store className="h-4 w-4" />} />
            <QuickLink href="/stores/new" label="Create a new store" icon={<Store className="h-4 w-4" />} />
            <QuickLink href="/settings" label="Manage organization settings" icon={<CreditCard className="h-4 w-4" />} />
          </CardContent>
        </Card>
      </div>
    </div>
  )
}

function StatCard({
  icon, label, value, bg, badge,
}: {
  icon: React.ReactNode
  label: string
  value: string | null
  bg: string
  badge?: boolean
}) {
  return (
    <Card>
      <CardContent className="pt-6">
        <div className="flex items-center gap-4">
          <div className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${bg}`}>{icon}</div>
          <div>
            <p className="text-xs text-muted-foreground">{label}</p>
            {value === null ? (
              <Skeleton className="mt-1 h-6 w-16" />
            ) : badge ? (
              <Badge variant="secondary" className="mt-1 capitalize">{value}</Badge>
            ) : (
              <p className="text-2xl font-bold">{value}</p>
            )}
          </div>
        </div>
      </CardContent>
    </Card>
  )
}

function QuickLink({ href, label, icon }: { href: string; label: string; icon: React.ReactNode }) {
  return (
    <Link
      href={href}
      className="flex items-center justify-between rounded-lg border p-3 text-sm font-medium transition-colors hover:bg-accent"
    >
      <span className="flex items-center gap-2">
        {icon}
        {label}
      </span>
      <ArrowRight className="h-4 w-4 text-muted-foreground" />
    </Link>
  )
}
