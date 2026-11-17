'use client'
import Link from 'next/link'
import { Plus, Store as StoreIcon, ExternalLink } from 'lucide-react'
import { PageHeader } from '@/components/layout/PageHeader'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { useAuthStore } from '@/store/authStore'
import { useStores } from '@/hooks/useStores'
import type { Store } from '@/lib/types'

export default function StoresPage() {
  const { currentOrg } = useAuthStore()
  const orgSlug = currentOrg?.slug ?? ''
  const { data: stores, isLoading } = useStores(orgSlug)

  return (
    <div>
      <PageHeader
        title="Stores"
        description="Manage all your e-commerce stores"
        action={
          <Link href="/stores/new">
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              New Store
            </Button>
          </Link>
        }
      />

      {isLoading ? (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {[...Array(3)].map((_, i) => (
            <Card key={i}>
              <CardHeader><Skeleton className="h-5 w-32" /></CardHeader>
              <CardContent className="space-y-2">
                <Skeleton className="h-4 w-20" />
                <Skeleton className="h-4 w-24" />
              </CardContent>
            </Card>
          ))}
        </div>
      ) : stores && stores.length > 0 ? (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {stores.map((store: Store) => (
            <Link key={store.slug} href={`/stores/${store.slug}`}>
              <Card className="cursor-pointer transition-shadow hover:shadow-md">
                <CardHeader className="pb-3">
                  <div className="flex items-start justify-between">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
                        <StoreIcon className="h-5 w-5 text-blue-600" />
                      </div>
                      <div>
                        <CardTitle className="text-base">{store.name}</CardTitle>
                        <CardDescription className="text-xs">{store.slug}</CardDescription>
                      </div>
                    </div>
                    <Badge variant={store.is_active ? 'success' : 'outline'}>
                      {store.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">
                      Currency: <span className="font-medium text-foreground">{store.currency}</span>
                    </span>
                    {store.products_count !== undefined && (
                      <span className="text-muted-foreground">
                        <span className="font-medium text-foreground">{store.products_count}</span> products
                      </span>
                    )}
                  </div>
                </CardContent>
              </Card>
            </Link>
          ))}
        </div>
      ) : (
        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed py-16">
          <StoreIcon className="mb-4 h-10 w-10 text-muted-foreground" />
          <h3 className="mb-1 text-lg font-semibold">No stores yet</h3>
          <p className="mb-4 text-sm text-muted-foreground">Create your first store to get started</p>
          <Link href="/stores/new">
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              Create Store
            </Button>
          </Link>
        </div>
      )}
    </div>
  )
}
