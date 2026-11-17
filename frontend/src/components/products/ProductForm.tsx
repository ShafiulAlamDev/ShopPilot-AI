'use client'
import { useForm, Controller } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Loader2 } from 'lucide-react'
import { Card, CardContent, CardFooter } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { useCategories } from '@/hooks/useCategories'
import type { Product, ProductCategory } from '@/lib/types'

const schema = z.object({
  name: z.string().min(1, 'Name is required'),
  sku: z.string().optional(),
  short_description: z.string().optional(),
  description: z.string().optional(),
  price: z.string().min(1, 'Price is required'),
  compare_price: z.string().optional(),
  cost_price: z.string().optional(),
  stock_quantity: z.coerce.number().int().min(0).default(0),
  low_stock_threshold: z.coerce.number().int().min(0).default(5),
  track_stock: z.boolean().default(true),
  status: z.enum(['draft', 'active', 'archived']).default('draft'),
  is_featured: z.boolean().default(false),
  weight: z.string().optional(),
  weight_unit: z.string().optional(),
  meta_title: z.string().optional(),
  meta_description: z.string().optional(),
  category_id: z.coerce.number().optional(),
})

type FormData = z.infer<typeof schema>

interface ProductFormProps {
  orgSlug: string
  storeSlug: string
  defaultValues?: Partial<Product>
  onSubmit: (data: FormData) => void
  isPending: boolean
  onCancel: () => void
}

export function ProductForm({ orgSlug, storeSlug, defaultValues, onSubmit, isPending, onCancel }: ProductFormProps) {
  const { data: categories } = useCategories(orgSlug, storeSlug)

  const { register, handleSubmit, control, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      name: defaultValues?.name ?? '',
      sku: defaultValues?.sku ?? '',
      short_description: defaultValues?.short_description ?? '',
      description: defaultValues?.description ?? '',
      price: defaultValues?.price ?? '',
      compare_price: defaultValues?.compare_price ?? '',
      cost_price: defaultValues?.cost_price ?? '',
      stock_quantity: defaultValues?.stock_quantity ?? 0,
      low_stock_threshold: defaultValues?.low_stock_threshold ?? 5,
      track_stock: defaultValues?.track_stock ?? true,
      status: defaultValues?.status ?? 'draft',
      is_featured: defaultValues?.is_featured ?? false,
      weight: defaultValues?.weight ?? '',
      weight_unit: defaultValues?.weight_unit ?? 'kg',
      meta_title: defaultValues?.meta_title ?? '',
      meta_description: defaultValues?.meta_description ?? '',
      category_id: defaultValues?.category?.id,
    },
  })

  return (
    <Card className="max-w-3xl">
      <form onSubmit={handleSubmit(onSubmit)}>
        <CardContent className="space-y-6 pt-6">
          {/* Basic Info */}
          <div>
            <h3 className="mb-4 text-sm font-semibold text-muted-foreground uppercase tracking-wider">Basic Information</h3>
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="name">Product Name *</Label>
                  <Input id="name" {...register('name')} />
                  {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
                </div>
                <div className="space-y-2">
                  <Label htmlFor="sku">SKU</Label>
                  <Input id="sku" placeholder="PROD-001" {...register('sku')} />
                </div>
              </div>
              <div className="space-y-2">
                <Label htmlFor="short_description">Short Description</Label>
                <Input id="short_description" placeholder="Brief product summary" {...register('short_description')} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea id="description" rows={4} placeholder="Full product description" {...register('description')} />
              </div>
            </div>
          </div>

          {/* Pricing */}
          <div>
            <h3 className="mb-4 text-sm font-semibold text-muted-foreground uppercase tracking-wider">Pricing</h3>
            <div className="grid grid-cols-3 gap-4">
              <div className="space-y-2">
                <Label>Price *</Label>
                <Input type="number" step="0.01" placeholder="0.00" {...register('price')} />
                {errors.price && <p className="text-xs text-destructive">{errors.price.message}</p>}
              </div>
              <div className="space-y-2">
                <Label>Compare Price</Label>
                <Input type="number" step="0.01" placeholder="0.00" {...register('compare_price')} />
              </div>
              <div className="space-y-2">
                <Label>Cost Price</Label>
                <Input type="number" step="0.01" placeholder="0.00" {...register('cost_price')} />
              </div>
            </div>
          </div>

          {/* Inventory */}
          <div>
            <h3 className="mb-4 text-sm font-semibold text-muted-foreground uppercase tracking-wider">Inventory</h3>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Stock Quantity</Label>
                <Input type="number" {...register('stock_quantity')} />
              </div>
              <div className="space-y-2">
                <Label>Low Stock Threshold</Label>
                <Input type="number" {...register('low_stock_threshold')} />
              </div>
            </div>
            <div className="mt-4 flex items-center gap-6">
              <label className="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" className="rounded" {...register('track_stock')} />
                Track Stock
              </label>
              <label className="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" className="rounded" {...register('is_featured')} />
                Featured Product
              </label>
            </div>
          </div>

          {/* Status & Category */}
          <div>
            <h3 className="mb-4 text-sm font-semibold text-muted-foreground uppercase tracking-wider">Organization</h3>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Status</Label>
                <Controller
                  name="status"
                  control={control}
                  render={({ field }) => (
                    <Select value={field.value} onValueChange={field.onChange}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="draft">Draft</SelectItem>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="archived">Archived</SelectItem>
                      </SelectContent>
                    </Select>
                  )}
                />
              </div>
              <div className="space-y-2">
                <Label>Category</Label>
                <Controller
                  name="category_id"
                  control={control}
                  render={({ field }) => (
                    <Select
                      value={field.value ? String(field.value) : ''}
                      onValueChange={(v) => field.onChange(Number(v))}
                    >
                      <SelectTrigger><SelectValue placeholder="Select category" /></SelectTrigger>
                      <SelectContent>
                        {(categories ?? []).map((cat: ProductCategory) => (
                          <SelectItem key={cat.id} value={String(cat.id)}>{cat.name}</SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  )}
                />
              </div>
            </div>
          </div>

          {/* Weight */}
          <div>
            <h3 className="mb-4 text-sm font-semibold text-muted-foreground uppercase tracking-wider">Shipping</h3>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Weight</Label>
                <Input type="number" step="0.01" placeholder="0.00" {...register('weight')} />
              </div>
              <div className="space-y-2">
                <Label>Weight Unit</Label>
                <Controller
                  name="weight_unit"
                  control={control}
                  render={({ field }) => (
                    <Select value={field.value ?? 'kg'} onValueChange={field.onChange}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="kg">kg</SelectItem>
                        <SelectItem value="g">g</SelectItem>
                        <SelectItem value="lb">lb</SelectItem>
                        <SelectItem value="oz">oz</SelectItem>
                      </SelectContent>
                    </Select>
                  )}
                />
              </div>
            </div>
          </div>

          {/* SEO */}
          <div>
            <h3 className="mb-4 text-sm font-semibold text-muted-foreground uppercase tracking-wider">SEO</h3>
            <div className="space-y-4">
              <div className="space-y-2">
                <Label>Meta Title</Label>
                <Input {...register('meta_title')} />
              </div>
              <div className="space-y-2">
                <Label>Meta Description</Label>
                <Textarea rows={2} {...register('meta_description')} />
              </div>
            </div>
          </div>
        </CardContent>

        <CardFooter className="gap-3">
          <Button type="button" variant="outline" onClick={onCancel}>Cancel</Button>
          <Button type="submit" disabled={isPending}>
            {isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            {isPending ? 'Saving…' : 'Save Product'}
          </Button>
        </CardFooter>
      </form>
    </Card>
  )
}
