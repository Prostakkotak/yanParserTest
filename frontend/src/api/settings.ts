import type { Organization, SettingsUpdateResponse } from '@/types/api'
import client from './client'

export async function fetchSettings(): Promise<Organization | null> {
  const { data } = await client.get<{ organization: Organization | null }>('/settings')
  return data.organization
}

export async function updateSettings(payload: {
  yandex_url: string
}): Promise<SettingsUpdateResponse> {
  const { data } = await client.put<SettingsUpdateResponse>('/settings', payload)
  return data
}

export async function reparseSettings(): Promise<SettingsUpdateResponse> {
  const { data } = await client.post<SettingsUpdateResponse>('/settings/reparse')
  return data
}
