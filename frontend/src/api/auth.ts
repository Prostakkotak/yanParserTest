import type { LoginCredentials, LoginResponse, User } from '@/types/api'
import client, { ensureCsrfCookie } from './client'

export async function login(credentials: LoginCredentials): Promise<LoginResponse> {
  await ensureCsrfCookie()
  const { data } = await client.post<LoginResponse>('/auth/login', credentials)
  return data
}

export async function logout(): Promise<void> {
  await client.post('/auth/logout')
}

export async function fetchUser(): Promise<User> {
  const { data } = await client.get<{ user: User }>('/auth/user')
  return data.user
}
