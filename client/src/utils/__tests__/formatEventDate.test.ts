import assert from 'node:assert/strict'
import { test } from 'node:test'
import { formatEventDate } from '../formatEventDate.ts'

test('formats an exact day in French', () => {
  assert.equal(formatEventDate('2019-01-12', 'day'), '12 janvier 2019')
  assert.equal(formatEventDate('2019-01-12T00:00:00+00:00', 'day'), '12 janvier 2019')
  assert.equal(formatEventDate('2019-02-01'), '1 février 2019')
  assert.equal(formatEventDate('2019-08-15', 'week'), '15 août 2019')
})

test('formats a month or a year without inventing a day', () => {
  assert.equal(formatEventDate('2025-05-01', 'month'), 'mai 2025')
  assert.equal(formatEventDate('2025-05-17T00:00:00+02:00', 'month'), 'mai 2025')
  assert.equal(formatEventDate('2025-01-01', 'year'), '2025')
})

test('leaves values that are not calendar dates unchanged', () => {
  assert.equal(formatEventDate('pas une date', 'month'), 'pas une date')
  assert.equal(formatEventDate('2019-13-01', 'day'), '2019-13-01')
})
