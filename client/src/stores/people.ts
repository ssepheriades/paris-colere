import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface LegalEntitySummary {
  id: string
  name: string
  shortDescription: string | null
  type: string | null
  logo: string | null
}

export interface PersonMandate {
  id: string
  startDate: string
  endDate: string | null
  position: string | null
  legalEntity: LegalEntitySummary
}

export interface Person {
  id: string
  firstname: string
  lastname: string
  photo: string | null
  personLegalEntities: PersonMandate[]
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

function parseLegalEntity (value: unknown): LegalEntitySummary | null {
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
    type: stringOrNull(record.type),
    logo: stringOrNull(record.logo),
  }
}

function parseMandate (value: unknown): PersonMandate | null {
  const record = asRecord(value)
  if (!record) {
    return null
  }

  const legalEntity = parseLegalEntity(record.legalEntity)
  const startDate = stringOrNull(record.startDate)
  if (!legalEntity || !startDate) {
    return null
  }

  return {
    id: idFrom(record),
    startDate,
    endDate: stringOrNull(record.endDate),
    position: stringOrNull(record.position),
    legalEntity,
  }
}

function parsePerson (value: unknown): Person | null {
  const record = asRecord(value)
  if (!record) {
    return null
  }

  const firstname = stringOrNull(record.firstname)
  const lastname = stringOrNull(record.lastname)
  if (!firstname || !lastname) {
    return null
  }

  const mandates = Array.isArray(record.personLegalEntities)
    ? record.personLegalEntities.flatMap(item => {
        const mandate = parseMandate(item)
        return mandate ? [mandate] : []
      })
    : []

  return {
    id: idFrom(record),
    firstname,
    lastname,
    photo: stringOrNull(record.photo),
    personLegalEntities: mandates,
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

export function currentMandates (person: Person): PersonMandate[] {
  return person.personLegalEntities.filter(mandate => mandate.endDate === null)
}

export function personName (person: Pick<Person, 'firstname' | 'lastname'>): string {
  return `${person.firstname} ${person.lastname}`
}

export function initials (person: Pick<Person, 'firstname' | 'lastname'>): string {
  return `${person.firstname.charAt(0)}${person.lastname.charAt(0)}`.toUpperCase()
}

export function formatDate (value: string): string {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date)
}

export function formatRange (mandate: PersonMandate): string {
  const start = formatDate(mandate.startDate)
  if (!mandate.endDate) {
    return `Depuis le ${start}`
  }

  return `${start} — ${formatDate(mandate.endDate)}`
}

export const usePeopleStore = defineStore('people', () => {
  const people = ref<Person[]>([])
  const person = ref<Person | null>(null)
  const status = ref<LoadStatus>('idle')
  const detail = ref<string | null>(null)
  const page = ref(1)
  const total = ref(0)

  async function loadPage (nextPage = 1) {
    status.value = 'loading'
    detail.value = null
    page.value = nextPage

    try {
      const response = await fetch(`/api/people?page=${nextPage}`, {
        headers: { Accept: 'application/ld+json' },
      })

      if (!response.ok) {
        status.value = 'error'
        detail.value = 'La liste des personnes est indisponible.'
        people.value = []
        total.value = 0
        return
      }

      const body = asRecord(await response.json())
      if (!body) {
        status.value = 'error'
        detail.value = 'Réponse illisible.'
        people.value = []
        return
      }

      people.value = collectionMembers(body).flatMap(item => {
        const parsed = parsePerson(item)
        return parsed ? [parsed] : []
      })
      total.value = readTotal(body)
      status.value = 'ok'
    } catch (error) {
      status.value = 'error'
      people.value = []
      total.value = 0
      detail.value = error instanceof Error ? error.message : 'Connexion impossible.'
    }
  }

  async function loadOne (id: string) {
    status.value = 'loading'
    detail.value = null
    person.value = null

    try {
      const response = await fetch(`/api/people/${id}`, {
        headers: { Accept: 'application/ld+json' },
      })

      if (response.status === 404) {
        status.value = 'error'
        detail.value = 'Cette personne est introuvable.'
        return
      }

      if (!response.ok) {
        status.value = 'error'
        detail.value = 'La fiche est indisponible.'
        return
      }

      const parsed = parsePerson(await response.json())
      if (!parsed) {
        status.value = 'error'
        detail.value = 'Réponse illisible.'
        return
      }

      person.value = parsed
      status.value = 'ok'
    } catch (error) {
      status.value = 'error'
      detail.value = error instanceof Error ? error.message : 'Connexion impossible.'
    }
  }

  return {
    people,
    person,
    status,
    detail,
    page,
    total,
    pageSize: PAGE_SIZE,
    loadPage,
    loadOne,
  }
})
