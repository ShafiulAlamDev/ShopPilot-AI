'use client'
import { useParams, useRouter } from 'next/navigation'
import { PageHeader } from '@/components/layout/PageHeader'
import { ProductForm } from '@/components/products/ProductForm'
import { useAuthStore } from '@/store/authStore'
import { useCreateProduct } from '@/hooks/useProducts'

export default function NewProductPage() {
  const { storeSlug } = useParams<{ storeSlug: string }>()
  const router = useRouter()
  const { currentOrg } = useAuthStore()
  const orgSlug = currentOrg?.slug ?? ''
  const { mutate: createProduct, isPending } = useCreateProduct(orgSlug, storeSlug)

  const handleSubmit = (data: any) => {
    createProduct(data, {
      onSuccess: () => router.push(`/stores/${storeSlug}`),
    })
  }

  return (
    <div>
      <PageHeader title="New Product" description="Add a new product to your store" />
      <ProductForm
        orgSlug={orgSlug}
        storeSlug={storeSlug}
        onSubmit={handleSubmit}
        isPending={isPending}
        onCancel={() => router.push(`/stores/${storeSlug}`)}
      />
    </div>
  )
}
