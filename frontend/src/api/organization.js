import client from './client'

export async function fetchOrganization() {
  const { data } = await client.get('/organization')
  return data.organization
}

export async function fetchReviews(page = 1, perPage = 10) {
  const { data } = await client.get('/organization/reviews', {
    params: { page, per_page: perPage },
  })
  return data
}
