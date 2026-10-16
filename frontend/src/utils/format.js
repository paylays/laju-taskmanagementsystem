export const STATUSES = [
  { value: 'todo', label: 'To do' },
  { value: 'in_progress', label: 'In progress' },
  { value: 'done', label: 'Done' },
]

export const PRIORITIES = [
  { value: 'high', label: 'High' },
  { value: 'medium', label: 'Medium' },
  { value: 'low', label: 'Low' },
]

export const statusLabel = (value) => STATUSES.find((s) => s.value === value)?.label ?? value
export const priorityLabel = (value) => PRIORITIES.find((p) => p.value === value)?.label ?? value

export function fileSize(bytes) {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(bytes < 10 * 1024 ? 1 : 0)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

export function fileExtension(name) {
  const match = /\.([a-z0-9]{1,5})$/i.exec(name ?? '')
  return match ? match[1].toUpperCase() : 'FILE'
}

// Dates arrive as YYYY-MM-DD; parse as local midnight so the day never shifts by timezone.
function localDate(isoDate) {
  const [y, m, d] = isoDate.split('-').map(Number)
  return new Date(y, m - 1, d)
}

/**
 * "Due today", "Due tomorrow", "Overdue by 3 days", or "Due 12 Nov".
 * `overdue` lets the caller flag it without parsing the text.
 */
export function dueLabel(isoDate, now = new Date()) {
  if (!isoDate) return null

  const due = localDate(isoDate)
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const days = Math.round((due - today) / 86_400_000)

  if (days === 0) return { text: 'Due today', overdue: false }
  if (days === 1) return { text: 'Due tomorrow', overdue: false }
  if (days < 0) return { text: `Overdue by ${-days} ${-days === 1 ? 'day' : 'days'}`, overdue: true }

  const sameYear = due.getFullYear() === today.getFullYear()
  return {
    text: `Due ${due.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', ...(sameYear ? {} : { year: 'numeric' }) })}`,
    overdue: false,
  }
}

export function timeAgo(iso, now = new Date()) {
  const seconds = Math.round((now - new Date(iso)) / 1000)
  if (seconds < 45) return 'just now'
  const minutes = Math.round(seconds / 60)
  if (minutes < 60) return `${minutes} min ago`
  const hours = Math.round(minutes / 60)
  if (hours < 24) return `${hours} h ago`
  return new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
}

export function plural(count, word) {
  return `${count} ${word}${count === 1 ? '' : 's'}`
}
