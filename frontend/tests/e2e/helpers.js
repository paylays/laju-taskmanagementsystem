import { expect } from '@playwright/test'

// A real 1x1 PNG, so the server's content sniffing and thumbnailing both run.
export const PNG_1PX = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
  'base64',
)

export async function signIn(page, email, password = 'password') {
  await page.goto('/login')
  await page.getByLabel('Email').fill(email)
  await page.getByLabel('Password').fill(password)
  await page.getByRole('button', { name: 'Sign in' }).click()
  await expect(page.getByRole('heading', { name: 'Tasks' })).toBeVisible()
}

export const lane = (page, status) => page.locator(`[data-lane=${status}]`)
