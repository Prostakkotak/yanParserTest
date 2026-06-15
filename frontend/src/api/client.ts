import axios from 'axios'

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api'

const client = axios.create({
  baseURL: apiBaseUrl,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export async function ensureCsrfCookie(): Promise<void> {
  await axios.get('/sanctum/csrf-cookie', {
    withCredentials: true,
  })
}

export default client
