'use client'
import { useParams } from 'next/navigation'
import { Loader2 } from 'lucide-react'
import { PageHeader } from '@/components/layout/PageHeader'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Badge } from '@/components/ui/badge'
import { useAuthStore } from '@/store/authStore'
import { useStore, useUpdateStore } from '@/hooks/useStores'
import { ProductsTab } from '@/components/products/ProductsTab'
import { CategoriesTab } from '@/components/categories/CategoriesTab'
import { AiTab } from '@/components/ai/AiTab'
import { StoreSettingsForm } from '@/components/stores/StoreSettingsForm'

export default function StoreDetailPage() {
  const { storeSlug } = useParams<{ storeSlug: string }>()
  const { currentOrg } = useAuthStore()
  const orgSlug = currentOrg?.slug ?? ''
  const { data: store, isLoading } = useStore(orgSlug, storeSlug)

  if (isLoading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    )
  }

  if (!store) {
    return <div className="text-center text-muted-foreground py-16">Store not found.</div>
  }

  return (
    <div>
      <PageHeader
        title={store.name}
        description={`${store.currency} · ${store.timezone} · ${store.locale}`}
        action={
          <Badge variant={store.is_active ? 'success' : 'outline'}>
            {store.is_active ? 'Active' : 'Inactive'}
          </Badge>
        }
      />

      <Tabs defaultValue="products">
        <TabsList className="mb-6">
          <TabsTrigger value="products">Products</TabsTrigger>
          <TabsTrigger value="categories">Categories</TabsTrigger>
          <TabsTrigger value="ai">AI Hub</TabsTrigger>
          <TabsTrigger value="settings">Settings</TabsTrigger>
        </TabsList>

        <TabsContent value="products">
          <ProductsTab orgSlug={orgSlug} storeSlug={storeSlug} />
        </TabsContent>

        <TabsContent value="categories">
          <CategoriesTab orgSlug={orgSlug} storeSlug={storeSlug} />
        </TabsContent>

        <TabsContent value="ai">
          <AiTab orgSlug={orgSlug} storeSlug={storeSlug} />
        </TabsContent>

        <TabsContent value="settings">
          <StoreSettingsForm orgSlug={orgSlug} storeSlug={storeSlug} store={store} />
        </TabsContent>
      </Tabs>
    </div>
  )
}
