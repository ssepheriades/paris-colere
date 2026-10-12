import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface Theme {
  id: string
  name: string
  shortDescription: string | null
  color: string | null
  icon: string | null
}

export type CampaignPromiseStatus = 'kept' | 'partial' | 'broken' | 'in_progress'

export interface PromiseSource {
  id: string
  url: string
  title: string | null
}

export interface CampaignPromise {
  id: string
  title: string
  quote: string
  status: CampaignPromiseStatus
  illustration: string | null
  themes: Theme[]
  sources: PromiseSource[]
}

type LoadStatus = 'idle' | 'loading' | 'ok' | 'error'

const PAGE_SIZE = 30
const STATUSES: readonly CampaignPromiseStatus[] = ['kept', 'partial', 'broken', 'in_progress']

function asRecord (value: unknown): Record<string, unknown> | null {
  return value !== null && typeof value === 'object' ? value as Record<string, unknown> : null
}

function stringOrNull (value: unknown): string | null {
  return typeof value === 'string' && value !== '' ? value : null
}

function illustrationUrl (value: unknown): string | null {
  const url = stringOrNull(value)
  if (!url) {
    return null
  }

  const filename = url.split('/').at(-1) ?? ''

  return filename === '' ? null : url
}

function idFrom (record: Record<string, unknown>): string {
  if (typeof record.id === 'string' && record.id !== '') {
    return record.id
  }

  if (typeof record.id === 'number') {
    return String(record.id)
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

  return {
    id: idFrom(record),
    name,
    shortDescription: stringOrNull(record.shortDescription),
    color: stringOrNull(record.color),
    icon: stringOrNull(record.icon),
  }
}

function parseSource (value: unknown): PromiseSource | null {
  const record = asRecord(value)
  if (!record || record.isVisible === false) {
    return null
  }

  const url = stringOrNull(record.url)
  if (!url) {
    return null
  }

  return {
    id: idFrom(record),
    url,
    title: stringOrNull(record.title),
  }
}

function parsePromise (value: unknown): CampaignPromise | null {
  const record = asRecord(value)
  if (!record) {
    return null
  }

  const title = stringOrNull(record.title)
  const quote = stringOrNull(record.quote)
  const statusValue = stringOrNull(record.status)
  const status = STATUSES.find(candidate => candidate === statusValue)
  if (!title || !quote || !status) {
    return null
  }

  const themes = Array.isArray(record.theme)
    ? record.theme.flatMap(item => {
        const theme = parseTheme(item)
        return theme ? [theme] : []
      })
    : []

  const sources = Array.isArray(record.sources)
    ? record.sources.flatMap(item => {
        const source = parseSource(item)
        return source ? [source] : []
      })
    : []

  return {
    id: idFrom(record),
    title,
    quote,
    status,
    illustration: illustrationUrl(record.illustration),
    themes,
    sources,
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

export const STATUS_LABELS: Record<CampaignPromiseStatus, string> = {
  kept: 'Tenue',
  partial: 'Partiellement tenue',
  broken: 'Non tenue',
  in_progress: 'En cours',
}

export const STATUS_ICONS: Record<CampaignPromiseStatus, string> = {
  kept: 'mdi-check',
  partial: 'mdi-circle-half',
  broken: 'mdi-close',
  in_progress: 'mdi-dots-horizontal',
}

export const usePromisesStore = defineStore('promises', () => {
  const promises = ref<CampaignPromise[]>([])
  const status = ref<LoadStatus>('idle')
  const detail = ref<string | null>(null)
  const page = ref(1)
  const total = ref(0)

  async function loadPage (nextPage = 1) {
    status.value = 'loading'
    detail.value = null
    page.value = nextPage

    try {
      const response = await fetch(`/api/campaign_promises?page=${nextPage}`, {
        headers: { Accept: 'application/ld+json' },
      })

      if (!response.ok) {
        status.value = 'error'
        detail.value = 'La liste des promesses est indisponible.'
        promises.value = []
        total.value = 0
        return
      }

      const body = asRecord(await response.json())
      if (!body) {
        status.value = 'error'
        detail.value = 'Réponse illisible.'
        promises.value = []
        return
      }

      promises.value = collectionMembers(body).flatMap(item => {
        const parsed = parsePromise(item)
        return parsed ? [parsed] : []
      })
      total.value = readTotal(body)
      status.value = 'ok'
    } catch (error) {
      status.value = 'error'
      promises.value = []
      total.value = 0
      detail.value = error instanceof Error ? error.message : 'Connexion impossible.'
    }
  }

  return {
    promises,
    status,
    detail,
    page,
    total,
    pageSize: PAGE_SIZE,
    loadPage,
  }
})
