import client from './client'

export async function fetchOrganization() {
  const { data } = await client.get('/organization')
  return data.organization
}

export async function fetchReviews(page = 1) {
  const { data } = await client.get('/organization/reviews', {
    params: { page },
  })
  return data
}
