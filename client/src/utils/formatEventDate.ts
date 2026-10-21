export type DatePrecision = 'day' | 'month' | 'year'

const MONTHS = [
  'janvier',
  'février',
  'mars',
  'avril',
  'mai',
  'juin',
  'juillet',
  'août',
  'septembre',
  'octobre',
  'novembre',
  'décembre',
] as const

/**
 * French calendar label for an event date.
 * `day` → "12 janvier 2019", `month` → "mai 2025", `year` → "2025".
 * Any other precision is shown as a full day so older payloads stay readable.
 */
export function formatEventDate (value: string, precision: string | null = 'day'): string {
  const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value)
  if (!match) {
    return value
  }

  const year = match[1]
  const month = MONTHS[Number(match[2]) - 1]
  const day = Number(match[3])
  if (!year || !month || day < 1) {
    return value
  }

  if (precision === 'year') {
    return year
  }

  if (precision === 'month') {
    return `${month} ${year}`
  }

  return `${day} ${month} ${year}`
}
