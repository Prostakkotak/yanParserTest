import client, { ensureCsrfCookie } from './client'

export async function login(credentials) {
  await ensureCsrfCookie()
  const { data } = await client.post('/auth/login', credentials)
  return data
}

export async function logout() {
  const { data } = await client.post('/auth/logout')
  return data
}

export async function fetchUser() {
  const { data } = await client.get('/auth/user')
  return data.user
}
