'use client'
import { useParams, useRouter } from 'next/navigation'
import { Loader2, Sparkles } from 'lucide-react'
import { PageHeader } from '@/components/layout/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { ProductForm } from '@/components/products/ProductForm'
import { useAuthStore } from '@/store/authStore'
import { useProduct, useUpdateProduct, useGenerateDescription } from '@/hooks/useProducts'

export default function EditProductPage() {
  const { storeSlug, productId } = useParams<{ storeSlug: string; productId: string }>()
  const router = useRouter()
  const { currentOrg } = useAuthStore()
  const orgSlug = currentOrg?.slug ?? ''

  const { data: product, isLoading } = useProduct(orgSlug, storeSlug, productId)
  const { mutate: updateProduct, isPending } = useUpdateProduct(orgSlug, storeSlug, productId)
  const { mutate: generateDesc, isPending: isGenerating } = useGenerateDescription(orgSlug, storeSlug, productId)

  if (isLoading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    )
  }

  if (!product) {
    return <div className="text-center text-muted-foreground py-16">Product not found.</div>
  }

  const handleSubmit = (data: any) => {
    updateProduct(data, {
      onSuccess: () => router.push(`/stores/${storeSlug}`),
    })
  }

  return (
    <div>
      <PageHeader
        title="Edit Product"
        description={product.name}
        action={
          <div className="flex items-center gap-2">
            {product.ai_description_generated && (
              <Badge variant="secondary">✨ AI Description</Badge>
            )}
            <Button
              variant="outline"
              size="sm"
              onClick={() => generateDesc()}
              disabled={isGenerating}
            >
              {isGenerating ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              ) : (
                <Sparkles className="mr-2 h-4 w-4 text-amber-500" />
              )}
              {isGenerating ? 'Generating…' : 'Generate AI Description'}
            </Button>
          </div>
        }
      />
      <ProductForm
        orgSlug={orgSlug}
        storeSlug={storeSlug}
        defaultValues={product}
        onSubmit={handleSubmit}
        isPending={isPending}
        onCancel={() => router.push(`/stores/${storeSlug}`)}
      />
    </div>
  )
}
