import type { Organization, ReviewsResponse } from '@/types/api'
import client from './client'

export async function fetchOrganization(): Promise<Organization> {
  const { data } = await client.get<{ organization: Organization }>('/organization')
  return data.organization
}

export async function fetchReviews(page = 1, perPage = 50): Promise<ReviewsResponse> {
  const { data } = await client.get<ReviewsResponse>('/organization/reviews', {
    params: { page, per_page: perPage },
  })
  return data
}
