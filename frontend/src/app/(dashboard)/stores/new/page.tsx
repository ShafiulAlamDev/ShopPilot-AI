'use client'
import { useRouter } from 'next/navigation'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Loader2 } from 'lucide-react'
import { PageHeader } from '@/components/layout/PageHeader'
import { Card, CardContent, CardFooter } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useAuthStore } from '@/store/authStore'
import { useCreateStore } from '@/hooks/useStores'

const schema = z.object({
  name: z.string().min(2, 'Name is required'),
  currency: z.string().min(3, 'Currency is required'),
  timezone: z.string().min(1, 'Timezone is required'),
  locale: z.string().min(1, 'Locale is required'),
  primary_color: z.string().optional(),
})

type FormData = z.infer<typeof schema>

const CURRENCIES = ['USD', 'EUR', 'GBP', 'BDT', 'CAD', 'AUD', 'JPY', 'INR', 'SGD', 'AED']
const TIMEZONES = [
  'UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
  'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'Asia/Dhaka', 'Asia/Kolkata',
  'Asia/Singapore', 'Asia/Tokyo', 'Australia/Sydney',
]
const LOCALES = ['en-US', 'en-GB', 'en-BD', 'fr-FR', 'de-DE', 'ja-JP', 'zh-CN']

export default function NewStorePage() {
  const router = useRouter()
  const { currentOrg } = useAuthStore()
  const orgSlug = currentOrg?.slug ?? ''
  const { mutate: createStore, isPending } = useCreateStore(orgSlug)

  const { register, handleSubmit, setValue, watch, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: { currency: 'USD', timezone: 'UTC', locale: 'en-US', primary_color: '#3b82f6' },
  })

  const onSubmit = (data: FormData) => {
    createStore(data, {
      onSuccess: () => router.push('/stores'),
    })
  }

  return (
    <div>
      <PageHeader title="Create Store" description="Set up a new store for your organization" />
      <Card className="max-w-2xl">
        <form onSubmit={handleSubmit(onSubmit)}>
          <CardContent className="space-y-5 pt-6">
            <div className="space-y-2">
              <Label htmlFor="name">Store Name *</Label>
              <Input id="name" placeholder="My Awesome Store" {...register('name')} />
              {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Currency *</Label>
                <Select defaultValue="USD" onValueChange={(v) => setValue('currency', v)}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    {CURRENCIES.map((c) => <SelectItem key={c} value={c}>{c}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Locale *</Label>
                <Select defaultValue="en-US" onValueChange={(v) => setValue('locale', v)}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    {LOCALES.map((l) => <SelectItem key={l} value={l}>{l}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="space-y-2">
              <Label>Timezone *</Label>
              <Select defaultValue="UTC" onValueChange={(v) => setValue('timezone', v)}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {TIMEZONES.map((tz) => <SelectItem key={tz} value={tz}>{tz}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="primary_color">Primary Color</Label>
              <div className="flex items-center gap-3">
                <Input
                  id="primary_color"
                  type="color"
                  className="h-10 w-16 cursor-pointer p-1"
                  {...register('primary_color')}
                />
                <Input
                  placeholder="#3b82f6"
                  value={watch('primary_color') ?? ''}
                  onChange={(e) => setValue('primary_color', e.target.value)}
                  className="flex-1"
                />
              </div>
            </div>
          </CardContent>
          <CardFooter className="gap-3">
            <Button type="button" variant="outline" onClick={() => router.push('/stores')}>
              Cancel
            </Button>
            <Button type="submit" disabled={isPending}>
              {isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {isPending ? 'Creating…' : 'Create Store'}
            </Button>
          </CardFooter>
        </form>
      </Card>
    </div>
  )
}
