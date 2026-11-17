export interface User {
  id: number
  name: string
  email: string
  avatar_url: string
  phone?: string
  is_active: boolean
  email_verified_at?: string
}

export interface Organization {
  id: number
  name: string
  slug: string
  domain?: string
  logo_url?: string
  email?: string
  phone?: string
  website?: string
  plan: string
  is_active: boolean
  created_at: string
  stores: Store[]
  members?: User[]
}

export interface Store {
  id: number
  name: string
  slug: string
  currency: string
  timezone: string
  locale: string
  logo?: string
  favicon?: string
  primary_color?: string
  is_active: boolean
  created_at: string
  products_count?: number
}

export interface ProductCategory {
  id: number
  name: string
  slug: string
  description?: string
  image_url?: string
  sort_order: number
  is_active: boolean
  parent_id?: number
  children?: ProductCategory[]
}

export interface ProductTag {
  id: number
  name: string
  slug: string
}

export interface ProductImage {
  id: number
  url: string
  alt_text?: string
  sort_order: number
  is_primary: boolean
}

export interface Product {
  id: number
  name: string
  slug: string
  sku?: string
  description?: string
  short_description?: string
  price: string
  compare_price?: string
  cost_price?: string
  discount_percentage?: number
  stock_quantity: number
  low_stock_threshold: number
  track_stock: boolean
  is_in_stock: boolean
  is_low_stock: boolean
  status: 'draft' | 'active' | 'archived'
  is_featured: boolean
  weight?: string
  weight_unit?: string
  meta_title?: string
  meta_description?: string
  ai_description_generated: boolean
  created_at: string
  updated_at: string
  category?: ProductCategory
  images?: ProductImage[]
  tags?: ProductTag[]
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface AiUsageStat {
  feature: string
  requests: number
  total_tokens: number
  total_cost_usd: string
  avg_duration_ms: number
  errors: number
}
