export type SyncStatus = 'idle' | 'pending' | 'processing' | 'completed' | 'failed'

export interface Organization {
  id?: number
  yandex_url?: string | null
  name?: string | null
  avg_rating?: number | null
  ratings_count?: number | null
  reviews_count?: number | null
  sync_status?: SyncStatus
  sync_error?: string | null
  last_synced_at?: string | null
}

export interface Review {
  id: number
  author?: string | null
  rating?: number | null
  text?: string | null
  reviewed_at?: string | null
}

export interface PaginatedReviews {
  data: Review[]
  current_page: number
  last_page: number
  total: number
  per_page: number
}

export interface ReviewsResponse {
  organization: Organization
  reviews: PaginatedReviews
}

export interface SettingsUpdateResponse {
  message: string
  organization: Organization
}

export interface User {
  id: number
  name: string
  email: string
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface LoginResponse {
  user: User
}
