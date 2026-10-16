import { describe, expect, it } from 'vitest'
import { dueLabel, fileExtension, fileSize, plural, priorityLabel, statusLabel, timeAgo } from '../../src/utils/format'

describe('dueLabel', () => {
  const now = new Date(2026, 9, 16, 15, 30) // 16 Oct 2026, mid-afternoon local time

  it('names today and tomorrow', () => {
    expect(dueLabel('2026-10-16', now)).toEqual({ text: 'Due today', overdue: false })
    expect(dueLabel('2026-10-17', now)).toEqual({ text: 'Due tomorrow', overdue: false })
  })

  it('flags overdue dates with the number of days', () => {
    expect(dueLabel('2026-10-15', now)).toEqual({ text: 'Overdue by 1 day', overdue: true })
    expect(dueLabel('2026-10-06', now)).toEqual({ text: 'Overdue by 10 days', overdue: true })
  })

  it('shows the date, adding the year only when it differs', () => {
    expect(dueLabel('2026-11-03', now).text).toBe('Due 3 Nov')
    expect(dueLabel('2027-01-05', now).text).toBe('Due 5 Jan 2027')
  })

  it('returns null without a date', () => {
    expect(dueLabel(null, now)).toBeNull()
  })
})

describe('fileSize', () => {
  it('picks a readable unit', () => {
    expect(fileSize(512)).toBe('512 B')
    expect(fileSize(1536)).toBe('1.5 KB')
    expect(fileSize(200 * 1024)).toBe('200 KB')
    expect(fileSize(5.25 * 1024 * 1024)).toBe('5.3 MB')
  })
})

describe('labels', () => {
  it('maps API values to display text', () => {
    expect(statusLabel('in_progress')).toBe('In progress')
    expect(priorityLabel('high')).toBe('High')
    expect(fileExtension('Q4 plan.final.pdf')).toBe('PDF')
    expect(fileExtension('README')).toBe('FILE')
    expect(plural(1, 'file')).toBe('1 file')
    expect(plural(3, 'comment')).toBe('3 comments')
  })

  it('formats relative times', () => {
    const now = new Date('2026-10-16T12:00:00Z')
    expect(timeAgo('2026-10-16T11:59:40Z', now)).toBe('just now')
    expect(timeAgo('2026-10-16T11:45:00Z', now)).toBe('15 min ago')
    expect(timeAgo('2026-10-16T09:00:00Z', now)).toBe('3 h ago')
  })
})
