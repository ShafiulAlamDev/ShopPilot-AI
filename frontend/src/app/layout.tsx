import type { Metadata } from 'next'
import './globals.css'
import { QueryProvider } from '@/components/providers/QueryProvider'
import { ToasterProvider } from '@/components/ui/toaster'

export const metadata: Metadata = {
  title: 'ShopPilot AI',
  description: 'AI-powered e-commerce management dashboard',
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>
        <QueryProvider>
          {children}
          <ToasterProvider />
        </QueryProvider>
      </body>
    </html>
  )
}
