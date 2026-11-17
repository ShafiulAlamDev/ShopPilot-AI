'use client'
import Link from 'next/link'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Bot, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { useRegister } from '@/hooks/useAuth'

const schema = z
  .object({
    name: z.string().min(2, 'Name must be at least 2 characters'),
    email: z.string().email('Invalid email address'),
    organization_name: z.string().min(2, 'Organization name is required'),
    password: z.string().min(8, 'Password must be at least 8 characters'),
    password_confirmation: z.string(),
  })
  .refine((d) => d.password === d.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  })

type FormData = z.infer<typeof schema>

export default function RegisterPage() {
  const { mutate: register, isPending } = useRegister()
  const {
    register: reg,
    handleSubmit,
    formState: { errors },
  } = useForm<FormData>({ resolver: zodResolver(schema) })

  const onSubmit = (data: FormData) => register(data)

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-900 to-blue-900 p-4">
      <div className="w-full max-w-md">
        <div className="mb-8 flex flex-col items-center gap-3 text-white">
          <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-500 shadow-lg">
            <Bot className="h-8 w-8 text-white" />
          </div>
          <div className="text-center">
            <h1 className="text-2xl font-bold">Create your account</h1>
            <p className="text-sm text-blue-200">Start managing your stores with AI</p>
          </div>
        </div>

        <Card className="shadow-2xl">
          <CardHeader>
            <CardTitle className="text-xl">Get started</CardTitle>
            <CardDescription>Fill in your details to create an account</CardDescription>
          </CardHeader>
          <form onSubmit={handleSubmit(onSubmit)}>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="name">Full Name</Label>
                <Input id="name" placeholder="John Doe" {...reg('name')} />
                {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
              </div>
              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input id="email" type="email" placeholder="you@example.com" {...reg('email')} />
                {errors.email && <p className="text-xs text-destructive">{errors.email.message}</p>}
              </div>
              <div className="space-y-2">
                <Label htmlFor="organization_name">Organization Name</Label>
                <Input id="organization_name" placeholder="Acme Store" {...reg('organization_name')} />
                {errors.organization_name && (
                  <p className="text-xs text-destructive">{errors.organization_name.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="password">Password</Label>
                <Input id="password" type="password" placeholder="••••••••" {...reg('password')} />
                {errors.password && (
                  <p className="text-xs text-destructive">{errors.password.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="password_confirmation">Confirm Password</Label>
                <Input
                  id="password_confirmation"
                  type="password"
                  placeholder="••••••••"
                  {...reg('password_confirmation')}
                />
                {errors.password_confirmation && (
                  <p className="text-xs text-destructive">{errors.password_confirmation.message}</p>
                )}
              </div>
            </CardContent>
            <CardFooter className="flex flex-col gap-3">
              <Button type="submit" className="w-full" disabled={isPending}>
                {isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                {isPending ? 'Creating account…' : 'Create account'}
              </Button>
              <p className="text-center text-sm text-muted-foreground">
                Already have an account?{' '}
                <Link href="/login" className="font-medium text-primary hover:underline">
                  Sign in
                </Link>
              </p>
            </CardFooter>
          </form>
        </Card>
      </div>
    </div>
  )
}
