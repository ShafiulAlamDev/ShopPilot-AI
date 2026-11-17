'use client'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Loader2 } from 'lucide-react'
import { Card, CardContent, CardFooter } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { useUpdateStore } from '@/hooks/useStores'
import type { Store } from '@/lib/types'

const schema = z.object({
  name: z.string().min(2),
  currency: z.string().min(3),
  timezone: z.string().min(1),
  locale: z.string().min(1),
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

export function StoreSettingsForm({
  orgSlug, storeSlug, store,
}: {
  orgSlug: string
  storeSlug: string
  store: Store
}) {
  const { mutate: updateStore, isPending } = useUpdateStore(orgSlug, storeSlug)

  const { register, handleSubmit, setValue, watch, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      name: store.name,
      currency: store.currency,
      timezone: store.timezone,
      locale: store.locale,
      primary_color: store.primary_color ?? '#3b82f6',
    },
  })

  return (
    <Card className="max-w-2xl">
      <form onSubmit={handleSubmit((d) => updateStore(d))}>
        <CardContent className="space-y-5 pt-6">
          <div className="space-y-2">
            <Label>Store Name *</Label>
            <Input {...register('name')} />
            {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Currency *</Label>
              <Select value={watch('currency')} onValueChange={(v) => setValue('currency', v)}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {CURRENCIES.map((c) => <SelectItem key={c} value={c}>{c}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Locale *</Label>
              <Select value={watch('locale')} onValueChange={(v) => setValue('locale', v)}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {LOCALES.map((l) => <SelectItem key={l} value={l}>{l}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="space-y-2">
            <Label>Timezone *</Label>
            <Select value={watch('timezone')} onValueChange={(v) => setValue('timezone', v)}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                {TIMEZONES.map((tz) => <SelectItem key={tz} value={tz}>{tz}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label>Primary Color</Label>
            <div className="flex items-center gap-3">
              <Input
                type="color"
                className="h-10 w-16 cursor-pointer p-1"
                {...register('primary_color')}
              />
              <Input
                value={watch('primary_color') ?? ''}
                onChange={(e) => setValue('primary_color', e.target.value)}
                className="flex-1"
              />
            </div>
          </div>
        </CardContent>
        <CardFooter>
          <Button type="submit" disabled={isPending}>
            {isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Save Changes
          </Button>
        </CardFooter>
      </form>
    </Card>
  )
}
