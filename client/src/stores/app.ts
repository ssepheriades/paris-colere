import { defineStore } from 'pinia'
import { ref } from 'vue'

type ApiStatus = 'idle' | 'loading' | 'ok' | 'error'

export const useAppStore = defineStore('app', () => {
  const status = ref<ApiStatus>('idle')
  const httpStatus = ref<number | null>(null)
  const title = ref<string | null>(null)
  const detail = ref<string | null>(null)

  async function loadApi () {
    status.value = 'loading'
    title.value = null
    detail.value = null

    try {
      const response = await fetch('/api', {
        headers: { Accept: 'application/ld+json' },
      })
      httpStatus.value = response.status
      const body: unknown = await response.json()

      if (!response.ok) {
        status.value = 'error'
        detail.value = 'L’API a répondu avec une erreur.'
        return
      }

      status.value = 'ok'
      if (body && typeof body === 'object') {
        const record = body as Record<string, unknown>
        title.value = typeof record.title === 'string' ? record.title : 'Paris Colère'
        detail.value = typeof record['@id'] === 'string' ? record['@id'] : null
      }
    } catch (error) {
      status.value = 'error'
      httpStatus.value = null
      detail.value = error instanceof Error ? error.message : 'Connexion impossible.'
    }
  }

  return { status, httpStatus, title, detail, loadApi }
})
