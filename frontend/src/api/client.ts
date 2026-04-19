import axios, {
  AxiosError,
  type AxiosInstance,
  type AxiosRequestConfig,
  type InternalAxiosRequestConfig,
} from 'axios'

/**
 * Базовый axios клиент для публичного API.
 *
 * Базовый URL — `/api/v1`. В dev режиме Vite проксирует запросы на backend,
 * в production nginx роутит `/api/*` на php сервис.
 *
 * Аутентификация:
 * - request interceptor подставляет `Authorization: Bearer {access_token}`
 *   из localStorage (`auth:token`), если токен есть;
 * - response interceptor ловит `401`, пытается обновить токен через
 *   `/auth/refresh` и ретраит оригинальный запрос;
 * - одновременные 401-запросы ждут один общий refresh через `refreshPromise`,
 *   чтобы не делать N параллельных refresh'ей.
 */
export const apiClient: AxiosInstance = axios.create({
  baseURL: '/api/v1',
  headers: {
    Accept: 'application/json',
  },
})

const ACCESS_TOKEN_KEY = 'auth:token'
const REFRESH_TOKEN_KEY = 'auth:refresh'

type RefreshResponse = {
  success: boolean
  data: {
    access_token: string
    refresh_token: string
    expires_in: number
    token_type: string
  } | null
  error: { code: string; message: string } | null
}

let refreshPromise: Promise<string | null> | null = null

/**
 * Сохраняет пару токенов в `localStorage`. Null-значения — удаляют ключ.
 */
export function setTokens(accessToken: string | null, refreshToken: string | null): void {
  if (accessToken) {
    localStorage.setItem(ACCESS_TOKEN_KEY, accessToken)
  } else {
    localStorage.removeItem(ACCESS_TOKEN_KEY)
  }
  if (refreshToken) {
    localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken)
  } else {
    localStorage.removeItem(REFRESH_TOKEN_KEY)
  }
}

/** Удаляет обе пары токенов из `localStorage`. */
export function clearTokens(): void {
  localStorage.removeItem(ACCESS_TOKEN_KEY)
  localStorage.removeItem(REFRESH_TOKEN_KEY)
}

/** Читает access_token из `localStorage`. */
export function getAccessToken(): string | null {
  return localStorage.getItem(ACCESS_TOKEN_KEY)
}

/** Читает refresh_token из `localStorage`. */
export function getRefreshToken(): string | null {
  return localStorage.getItem(REFRESH_TOKEN_KEY)
}

/**
 * Делает запрос на `/auth/refresh` через отдельный axios instance
 * (без interceptors — избегаем рекурсии на 401).
 *
 * @returns новый access_token или `null` если refresh failed
 */
async function performRefresh(): Promise<string | null> {
  const refreshToken = getRefreshToken()
  if (!refreshToken) return null

  try {
    const resp = await axios.post<RefreshResponse>(
      '/api/v1/auth/refresh',
      { refresh_token: refreshToken },
      { headers: { Accept: 'application/json' } },
    )
    const data = resp.data.data
    if (!resp.data.success || !data) {
      clearTokens()
      return null
    }
    setTokens(data.access_token, data.refresh_token)
    return data.access_token
  } catch {
    clearTokens()
    return null
  }
}

apiClient.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = getAccessToken()
  if (token) {
    config.headers = config.headers ?? {}
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    ;(config.headers as any).Authorization = `Bearer ${token}`
  }
  return config
})

interface RetryableRequest extends AxiosRequestConfig {
  _retry?: boolean
}

apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const original = error.config as RetryableRequest | undefined
    const status = error.response?.status

    // Не-401 или повторная попытка — пробрасываем
    if (status !== 401 || !original || original._retry) {
      return Promise.reject(error)
    }

    // /auth/refresh и /auth/login сами — не ретраим
    const url = original.url ?? ''
    if (url.includes('/auth/refresh') || url.includes('/auth/login')) {
      return Promise.reject(error)
    }

    original._retry = true

    refreshPromise ??= performRefresh().finally(() => {
      refreshPromise = null
    })

    const newToken = await refreshPromise
    if (!newToken) {
      return Promise.reject(error)
    }

    original.headers = original.headers ?? {}
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    ;(original.headers as any).Authorization = `Bearer ${newToken}`
    return apiClient.request(original)
  },
)
