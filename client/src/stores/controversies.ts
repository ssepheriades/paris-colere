import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface Theme {
  id: string
  name: string
  shortDescription: string | null
  color: string | null
}

export interface KeyFigure {
  id: string
  figure: string
  label: string
  subLabel: string | null
  priority: number
}

export type SourceProvider = 'link' | 'youtube' | 'dailymotion' | 'vimeo' | 'tweet'

export interface Source {
  id: string
  url: string
  provider: SourceProvider
  externalId: string | null
  title: string | null
  thumbnailUrl: string | null
  authorName: string | null
  embedText: string | null
}

export interface ItemPerson {
  id: string
  firstname: string
  lastname: string
  photo: string | null
  shortDescription: string | null
}

export interface ControversyItem {
  id: string
  type: string
  title: string
  date: string
  shortDescription: string | null
  people: ItemPerson[]
  sources: Source[]
}

export interface Controversy {
  id: string
  name: string
  shortDescription: string | null
  illustration: string | null
  themes: Theme[]
  keyFigures: KeyFigure[]
  items: ControversyItem[]
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

function parseKeyFigure (value: unknown): KeyFigure | null {
  const record = asRecord(value)
  if (!record) {
    return null
  }

  if (record.isVisible === false) {
    return null
  }

  const figure = stringOrNull(record.figure)
  const label = stringOrNull(record.label)
  if (!figure || !label) {
    return null
  }

  const priority = typeof record.priority === 'number' ? record.priority : 0

  return {
    id: idFrom(record),
    figure,
    label,
    subLabel: stringOrNull(record.subLabel),
    priority,
  }
}

function parseKeyFigures (value: unknown): KeyFigure[] {
  const figures = Array.isArray(value)
    ? value.flatMap(item => {
        const keyFigure = parseKeyFigure(item)
        return keyFigure ? [keyFigure] : []
      })
    : []

  return [...figures].sort((a, b) => a.priority - b.priority)
}

const SOURCE_PROVIDERS: readonly SourceProvider[] = ['link', 'youtube', 'dailymotion', 'vimeo', 'tweet']

function parseSource (value: unknown): Source | null {
  const record = asRecord(value)
  if (!record) {
    return null
  }

  if (record.isVisible === false) {
    return null
  }

  const url = stringOrNull(record.url)
  if (!url) {
    return null
  }

  const providerValue = stringOrNull(record.provider)
  const provider = SOURCE_PROVIDERS.find(candidate => candidate === providerValue) ?? 'link'

  return {
    id: idFrom(record),
    url,
    provider,
    externalId: stringOrNull(record.externalId),
    title: stringOrNull(record.title),
    thumbnailUrl: stringOrNull(record.thumbnailUrl),
    authorName: stringOrNull(record.authorName),
    embedText: stringOrNull(record.embedText),
  }
}

function parseSources (value: unknown): Source[] {
  return Array.isArray(value)
    ? value.flatMap(item => {
        const source = parseSource(item)
        return source ? [source] : []
      })
    : []
}

function parseItemPerson (value: unknown): ItemPerson | null {
  const record = asRecord(value)
  if (!record) {
    return null
  }

  const firstname = stringOrNull(record.firstname)
  const lastname = stringOrNull(record.lastname)
  if (!firstname || !lastname) {
    return null
  }

  return {
    id: idFrom(record),
    firstname,
    lastname,
    photo: stringOrNull(record.photo),
    shortDescription: stringOrNull(record.shortDescription),
  }
}

function parseItemPeople (value: unknown): ItemPerson[] {
  return Array.isArray(value)
    ? value.flatMap(person => {
        const parsed = parseItemPerson(person)
        return parsed ? [parsed] : []
      })
    : []
}

function parseItem (value: unknown): ControversyItem | null {
  const record = asRecord(value)
  if (!record) {
    return null
  }

  if (record.isVisible === false) {
    return null
  }

  const title = stringOrNull(record.title)
  const date = stringOrNull(record.date)
  const type = stringOrNull(record.type)
  if (!title || !date || !type) {
    return null
  }

  return {
    id: idFrom(record),
    type,
    title,
    date,
    shortDescription: stringOrNull(record.shortDescription),
    people: parseItemPeople(record.people),
    sources: parseSources(record.sources),
  }
}

function parseItems (value: unknown): ControversyItem[] {
  const items = Array.isArray(value)
    ? value.flatMap(item => {
        const parsed = parseItem(item)
        return parsed ? [parsed] : []
      })
    : []

  return [...items].sort((a, b) => {
    const byDate = b.date.localeCompare(a.date)
    if (byDate !== 0) {
      return byDate
    }

    return b.id.localeCompare(a.id)
  })
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
    keyFigures: parseKeyFigures(record.keyFigures),
    items: parseItems(record.controversyItems),
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
