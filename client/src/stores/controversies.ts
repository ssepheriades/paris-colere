import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface Theme {
  id: string
  name: string
  shortDescription: string | null
  color: string | null
}

export interface Controversy {
  id: string
  name: string
  shortDescription: string | null
  illustration: string | null
  themes: Theme[]
}

type LoadStatus = 'idle' | 'loading' | 'ok' | 'error'

const PAGE_SIZE = 30

function asRecord (value: unknown): Record<string, unknown> | null {
  return value !== null && typeof value === 'object' ? value as Record<string, unknown> : null
}

function stringOrNull (value: unknown): string | null {
  return typeof value === 'string' && value !== '' ? value : null
}

function idFrom (record: Record<string, unknown>): string {
  if (typeof record.id === 'string' && record.id !== '') {
    return record.id
  }

  if (typeof record['@id'] === 'string') {
    const parts = record['@id'].split('/')
    return parts.at(-1) ?? record['@id']
  }

  return ''
}

function parseTheme (value: unknown): Theme | null {
  const record = asRecord(value)
  if (!record) {
    return null
  }

  const name = stringOrNull(record.name)
  if (!name) {
    return null
  }

  const id = typeof record.id === 'number' ? String(record.id) : idFrom(record)

  return {
    id,
    name,
    shortDescription: stringOrNull(record.shortDescription),
    color: stringOrNull(record.color),
  }
}

function parseThemes (value: unknown): Theme[] {
  return Array.isArray(value)
    ? value.flatMap(item => {
        const theme = parseTheme(item)
        return theme ? [theme] : []
      })
    : []
}

function parseControversy (value: unknown): Controversy | null {
  const record = asRecord(value)
  if (!record) {
    return null
  }

  const name = stringOrNull(record.name)
  if (!name) {
    return null
  }

  return {
    id: idFrom(record),
    name,
    shortDescription: stringOrNull(record.shortDescription),
    illustration: stringOrNull(record.illustration),
    themes: parseThemes(record.theme ?? record.themes),
  }
}

function collectionMembers (body: Record<string, unknown>): unknown[] {
  const members = body['hydra:member'] ?? body.member
  return Array.isArray(members) ? members : []
}

function readTotal (body: Record<string, unknown>): number {
  const total = body['hydra:totalItems'] ?? body.totalItems
  return typeof total === 'number' ? total : 0
}

export const useControversiesStore = defineStore('controversies', () => {
  const controversies = ref<Controversy[]>([])
  const controversy = ref<Controversy | null>(null)
  const status = ref<LoadStatus>('idle')
  const detail = ref<string | null>(null)
  const page = ref(1)
  const total = ref(0)

  async function loadPage (nextPage = 1) {
    status.value = 'loading'
    detail.value = null
    page.value = nextPage

    try {
      const response = await fetch(`/api/controversies?page=${nextPage}`, {
        headers: { Accept: 'application/ld+json' },
      })

      if (!response.ok) {
        status.value = 'error'
        detail.value = 'La liste des affaires est indisponible.'
        controversies.value = []
        total.value = 0
        return
      }

      const body = asRecord(await response.json())
      if (!body) {
        status.value = 'error'
        detail.value = 'Réponse illisible.'
        controversies.value = []
        return
      }

      controversies.value = collectionMembers(body).flatMap(item => {
        const parsed = parseControversy(item)
        return parsed ? [parsed] : []
      })
      total.value = readTotal(body)
      status.value = 'ok'
    } catch (error) {
      status.value = 'error'
      controversies.value = []
      total.value = 0
      detail.value = error instanceof Error ? error.message : 'Connexion impossible.'
    }
  }

  async function loadOne (id: string) {
    status.value = 'loading'
    detail.value = null
    controversy.value = null

    try {
      const response = await fetch(`/api/controversies/${id}`, {
        headers: { Accept: 'application/ld+json' },
      })

      if (response.status === 404) {
        status.value = 'error'
        detail.value = 'Cette affaire est introuvable.'
        return
      }

      if (!response.ok) {
        status.value = 'error'
        detail.value = 'La fiche est indisponible.'
        return
      }

      const parsed = parseControversy(await response.json())
      if (!parsed) {
        status.value = 'error'
        detail.value = 'Réponse illisible.'
        return
      }

      controversy.value = parsed
      status.value = 'ok'
    } catch (error) {
      status.value = 'error'
      detail.value = error instanceof Error ? error.message : 'Connexion impossible.'
    }
  }

  return {
    controversies,
    controversy,
    status,
    detail,
    page,
    total,
    pageSize: PAGE_SIZE,
    loadPage,
    loadOne,
  }
})
