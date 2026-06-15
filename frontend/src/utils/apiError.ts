import type { AxiosError } from 'axios'

interface ApiErrorBody {
  message?: string
}

export function getApiErrorMessage(error: unknown, fallback: string): string {
  if (!isAxiosError(error)) {
    return fallback
  }

  const data = error.response?.data as ApiErrorBody | undefined
  return data?.message ?? fallback
}

export function getApiErrorStatus(error: unknown): number | undefined {
  if (!isAxiosError(error)) {
    return undefined
  }

  return error.response?.status
}

function isAxiosError(error: unknown): error is AxiosError {
  return typeof error === 'object' && error !== null && 'isAxiosError' in error
}
