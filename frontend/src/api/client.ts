import axios, { type AxiosInstance } from 'axios'

/**
 * Базовый axios клиент для публичного API.
 *
 * Базовый URL — `/api/v1`. В dev режиме Vite проксирует запросы на backend,
 * в production nginx роутит `/api/*` на php сервис.
 */
export const apiClient: AxiosInstance = axios.create({
  baseURL: '/api/v1',
  headers: {
    Accept: 'application/json',
  },
})
