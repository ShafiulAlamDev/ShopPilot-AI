'use client'
import { useState } from 'react'
import Link from 'next/link'
import { Plus, Search, Pencil, Trash2, ChevronLeft, ChevronRight } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
  Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog'
import { useProducts, useDeleteProduct } from '@/hooks/useProducts'
import { formatPrice } from '@/lib/utils'
import type { Product, PaginatedResponse } from '@/lib/types'

const statusVariant: Record<string, any> = {
  active: 'success',
  draft: 'secondary',
  archived: 'outline',
}

export function ProductsTab({ orgSlug, storeSlug }: { orgSlug: string; storeSlug: string }) {
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('all')
  const [page, setPage] = useState(1)
  const [deleteId, setDeleteId] = useState<number | null>(null)

  const params: Record<string, any> = { page, per_page: 15 }
  if (search) params.search = search
  if (status !== 'all') params.status = status

  const { data, isLoading } = useProducts(orgSlug, storeSlug, params)
  const { mutate: deleteProduct, isPending: isDeleting } = useDeleteProduct(orgSlug, storeSlug)

  const products: Product[] = data?.data ?? []
  const meta = data?.meta

  const handleDelete = () => {
    if (deleteId) {
      deleteProduct(deleteId, { onSuccess: () => setDeleteId(null) })
    }
  }

  return (
    <div>
      {/* Toolbar */}
      <div className="mb-4 flex items-center gap-3">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search products…"
            className="pl-9"
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1) }}
          />
        </div>
        <Select value={status} onValueChange={(v) => { setStatus(v); setPage(1) }}>
          <SelectTrigger className="w-36">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Status</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="draft">Draft</SelectItem>
            <SelectItem value="archived">Archived</SelectItem>
          </SelectContent>
        </Select>
        <Link href={`/stores/${storeSlug}/products/new`}>
          <Button size="sm">
            <Plus className="mr-2 h-4 w-4" />
            New Product
          </Button>
        </Link>
      </div>

      {/* Table */}
      <div className="rounded-lg border bg-white">
        <table className="w-full text-sm">
          <thead className="border-b bg-slate-50">
            <tr>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">SKU</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Price</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Stock</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Featured</th>
              <th className="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y">
            {isLoading ? (
              [...Array(5)].map((_, i) => (
                <tr key={i}>
                  {[...Array(7)].map((_, j) => (
                    <td key={j} className="px-4 py-3">
                      <Skeleton className="h-4 w-full" />
                    </td>
                  ))}
                </tr>
              ))
            ) : products.length === 0 ? (
              <tr>
                <td colSpan={7} className="px-4 py-10 text-center text-muted-foreground">
                  No products found.
                </td>
              </tr>
            ) : (
              products.map((p) => (
                <tr key={p.id} className="hover:bg-slate-50/50">
                  <td className="px-4 py-3 font-medium">{p.name}</td>
                  <td className="px-4 py-3 text-muted-foreground">{p.sku ?? '—'}</td>
                  <td className="px-4 py-3">{formatPrice(p.price)}</td>
                  <td className="px-4 py-3">
                    <span className={p.is_low_stock ? 'text-amber-600 font-medium' : ''}>
                      {p.stock_quantity}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <Badge variant={statusVariant[p.status] ?? 'outline'} className="capitalize">
                      {p.status}
                    </Badge>
                  </td>
                  <td className="px-4 py-3">
                    {p.is_featured ? <Badge variant="secondary">★ Featured</Badge> : <span className="text-muted-foreground">—</span>}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-1">
                      <Link href={`/stores/${storeSlug}/products/${p.id}`}>
                        <Button variant="ghost" size="icon" className="h-8 w-8">
                          <Pencil className="h-4 w-4" />
                        </Button>
                      </Link>
                      <Button
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8 text-destructive hover:bg-destructive/10"
                        onClick={() => setDeleteId(p.id)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="mt-4 flex items-center justify-between text-sm text-muted-foreground">
          <span>
            Page {meta.current_page} of {meta.last_page} ({meta.total} total)
          </span>
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={meta.current_page === 1}
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))}
              disabled={meta.current_page === meta.last_page}
            >
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        </div>
      )}

      {/* Delete confirmation dialog */}
      <Dialog open={deleteId !== null} onOpenChange={(open) => !open && setDeleteId(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Delete Product</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete this product? This action cannot be undone.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteId(null)}>Cancel</Button>
            <Button variant="destructive" onClick={handleDelete} disabled={isDeleting}>
              {isDeleting ? 'Deleting…' : 'Delete'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
