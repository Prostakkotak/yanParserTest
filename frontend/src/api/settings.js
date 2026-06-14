import client from './client'

export async function fetchSettings() {
  const { data } = await client.get('/settings')
  return data.organization
}

export async function updateSettings(payload) {
  const { data } = await client.put('/settings', payload)
  return data
}
