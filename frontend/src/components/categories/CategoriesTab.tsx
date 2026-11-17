'use client'
import { useState } from 'react'
import { Plus, Trash2 } from 'lucide-react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog'
import { useCategories, useCreateCategory, useDeleteCategory } from '@/hooks/useCategories'
import type { ProductCategory } from '@/lib/types'

const schema = z.object({
  name: z.string().min(2, 'Name is required'),
  description: z.string().optional(),
})

type FormData = z.infer<typeof schema>

export function CategoriesTab({ orgSlug, storeSlug }: { orgSlug: string; storeSlug: string }) {
  const [open, setOpen] = useState(false)
  const [deleteId, setDeleteId] = useState<number | null>(null)

  const { data: categories, isLoading } = useCategories(orgSlug, storeSlug)
  const { mutate: createCategory, isPending: isCreating } = useCreateCategory(orgSlug, storeSlug)
  const { mutate: deleteCategory, isPending: isDeleting } = useDeleteCategory(orgSlug, storeSlug)

  const { register, handleSubmit, reset, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
  })

  const onSubmit = (data: FormData) => {
    createCategory(data, {
      onSuccess: () => {
        reset()
        setOpen(false)
      },
    })
  }

  return (
    <div>
      <div className="mb-4 flex justify-end">
        <Button size="sm" onClick={() => setOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Add Category
        </Button>
      </div>

      <div className="rounded-lg border bg-white">
        <table className="w-full text-sm">
          <thead className="border-b bg-slate-50">
            <tr>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Slug</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Parent</th>
              <th className="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
              <th className="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y">
            {isLoading ? (
              [...Array(3)].map((_, i) => (
                <tr key={i}>
                  {[...Array(5)].map((_, j) => (
                    <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                  ))}
                </tr>
              ))
            ) : categories && categories.length > 0 ? (
              categories.map((cat: ProductCategory) => (
                <tr key={cat.id} className="hover:bg-slate-50/50">
                  <td className="px-4 py-3 font-medium">{cat.name}</td>
                  <td className="px-4 py-3 text-muted-foreground">{cat.slug}</td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {cat.parent_id ? `#${cat.parent_id}` : '—'}
                  </td>
                  <td className="px-4 py-3">
                    <Badge variant={cat.is_active ? 'success' : 'outline'}>
                      {cat.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 text-destructive hover:bg-destructive/10"
                      onClick={() => setDeleteId(cat.id)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </td>
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan={5} className="px-4 py-10 text-center text-muted-foreground">
                  No categories yet. Add one to get started.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      {/* Add Category Dialog */}
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Add Category</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-4 py-2">
              <div className="space-y-2">
                <Label>Category Name *</Label>
                <Input placeholder="e.g. Electronics" {...register('name')} />
                {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
              </div>
              <div className="space-y-2">
                <Label>Description</Label>
                <Input placeholder="Optional description" {...register('description')} />
              </div>
            </div>
            <DialogFooter className="mt-4">
              <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
              <Button type="submit" disabled={isCreating}>
                {isCreating ? 'Creating…' : 'Create Category'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation */}
      <Dialog open={deleteId !== null} onOpenChange={(open) => !open && setDeleteId(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Delete Category</DialogTitle>
          </DialogHeader>
          <p className="text-sm text-muted-foreground">
            Are you sure you want to delete this category? This action cannot be undone.
          </p>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteId(null)}>Cancel</Button>
            <Button
              variant="destructive"
              disabled={isDeleting}
              onClick={() => deleteId && deleteCategory(deleteId, { onSuccess: () => setDeleteId(null) })}
            >
              {isDeleting ? 'Deleting…' : 'Delete'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  )
}
