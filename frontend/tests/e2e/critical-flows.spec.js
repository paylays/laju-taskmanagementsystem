import { expect, test } from '@playwright/test'
import { PNG_1PX, lane, signIn } from './helpers.js'

test('signed-out visitors are sent to sign in, and wrong passwords are explained', async ({ page }) => {
  await page.goto('/')
  await expect(page).toHaveURL(/\/login$/)

  await page.getByLabel('Email').fill('admin@lajur.test')
  await page.getByLabel('Password').fill('not-the-password')
  await page.getByRole('button', { name: 'Sign in' }).click()

  await expect(page.getByRole('alert')).toHaveText('Email or password is incorrect.')
})

test('create a task, attach files, comment, move it, and delete it', async ({ page }) => {
  await signIn(page, 'admin@lajur.test')

  await page.getByRole('button', { name: 'New task' }).click()
  const panel = page.getByRole('dialog')
  await panel.getByRole('button', { name: 'Create task' }).click()
  await expect(panel.getByText('Give the task a title.')).toBeVisible()

  await panel.getByLabel('Title').fill('E2E: prepare sprint demo')
  await panel.getByLabel('Priority').selectOption('high')
  await panel.getByLabel('Assignee').selectOption({ label: 'Rina Pratama' })
  await panel.getByRole('button', { name: 'Create task' }).click()

  await expect(page).toHaveURL(/\/tasks\/\d+$/)
  await expect(page.getByRole('status').filter({ hasText: 'Created "E2E: prepare sprint demo"' })).toBeVisible()
  await expect(lane(page, 'todo').locator('.title', { hasText: 'E2E: prepare sprint demo' })).toBeVisible()

  // Image upload: scanned (sync queue in E2E) and thumbnailed.
  await panel.locator('input[type=file]').setInputFiles({ name: 'whiteboard.png', mimeType: 'image/png', buffer: PNG_1PX })
  const image = panel.locator('.file', { hasText: 'whiteboard.png' })
  await expect(image.locator('img.thumb')).toBeVisible()
  const download = page.waitForEvent('download')
  await image.getByRole('button', { name: 'Download' }).click()
  expect((await download).suggestedFilename()).toBe('whiteboard.png')

  // A disallowed type is rejected with the server's reason.
  await panel.locator('input[type=file]').setInputFiles({ name: 'tool.exe', mimeType: 'application/octet-stream', buffer: Buffer.from([0x4d, 0x5a, 0x90, 0x00, 0x03, 0, 0, 0, 0xff, 0xff]) })
  await expect(panel.locator('.uploads .field-error')).toContainText('are not allowed')

  // The simulated antivirus blocks a file carrying the test marker.
  await panel.locator('input[type=file]').setInputFiles({ name: 'notes.txt', mimeType: 'text/plain', buffer: Buffer.from('notes LAJUR-SIMULATED-VIRUS') })
  await expect(panel.locator('.file', { hasText: 'notes.txt' })).toContainText('Failed the virus scan')

  // Delete needs a second, explicit click.
  const infected = panel.locator('.file', { hasText: 'notes.txt' })
  await infected.getByRole('button', { name: 'Delete' }).click()
  await infected.getByRole('button', { name: 'Confirm delete' }).click()
  await expect(panel.locator('.file', { hasText: 'notes.txt' })).toHaveCount(0)

  await panel.getByLabel('Add a comment').fill('Slides are in the shared folder.')
  await panel.getByRole('button', { name: 'Post comment' }).click()
  await expect(panel.locator('.comments')).toContainText('Slides are in the shared folder.')

  await panel.getByLabel('Status').selectOption('in_progress')
  await panel.getByRole('button', { name: 'Save changes' }).click()
  await expect(page.getByRole('status').filter({ hasText: 'Changes saved.' })).toBeVisible()

  await page.keyboard.press('Escape')
  await expect(page).toHaveURL(/\/$/)
  const row = lane(page, 'in_progress').locator('li', { hasText: 'E2E: prepare sprint demo' })
  await expect(row).toContainText('1 comment, 1 file')

  await row.getByRole('button').click()
  await panel.getByRole('button', { name: 'Delete task' }).click()
  await expect(panel).toContainText('Deleting also removes 1 file and all comments.')
  await panel.getByRole('button', { name: 'Confirm delete' }).click()
  await expect(page.getByText('E2E: prepare sprint demo')).toHaveCount(0)
})

test('changes reach another signed-in user without a reload', async ({ browser }) => {
  const adminPage = await (await browser.newContext()).newPage()
  const rinaPage = await (await browser.newContext()).newPage()
  await signIn(adminPage, 'admin@lajur.test')
  await signIn(rinaPage, 'rina@lajur.test')
  await expect(rinaPage.getByText('Live updates on')).toBeVisible()

  await adminPage.getByRole('button', { name: 'New task' }).click()
  await adminPage.getByRole('dialog').getByLabel('Title').fill('E2E: live task')
  await adminPage.getByRole('dialog').getByRole('button', { name: 'Create task' }).click()
  await expect(adminPage).toHaveURL(/\/tasks\/\d+$/)

  await expect(lane(rinaPage, 'todo').getByText('E2E: live task')).toBeVisible()

  await rinaPage.goto(adminPage.url().replace(/^.*(\/tasks\/\d+)$/, '$1'))
  await expect(rinaPage.getByRole('dialog').getByText('No comments yet.')).toBeVisible()

  await adminPage.getByLabel('Add a comment').fill('Live comment from admin')
  await adminPage.getByRole('button', { name: 'Post comment' }).click()

  await expect(rinaPage.getByRole('dialog').locator('.comments')).toContainText('Live comment from admin')
})

test('bulk status change runs as a job and updates the board', async ({ page }) => {
  await signIn(page, 'admin@lajur.test')
  await expect(lane(page, 'done').locator('li').first()).toBeVisible()
  const doneCount = Number(await lane(page, 'done').locator('.count').textContent())

  const todoRows = lane(page, 'todo').locator('li')
  await todoRows.nth(0).getByRole('checkbox').check()
  await todoRows.nth(1).getByRole('checkbox').check()
  await expect(page.getByRole('region', { name: 'Bulk actions' })).toContainText('2 tasks selected')

  await page.locator('#bulk-status').selectOption('done')
  await page.getByRole('button', { name: 'Apply' }).click()

  await expect(lane(page, 'done').locator('.count')).toHaveText(String(doneCount + 2))
})

test('a member cannot edit a task that is not theirs, but can comment', async ({ page }) => {
  await signIn(page, 'sari@lajur.test')

  // Seeded: "Investigate slow search" was created by Fajar and assigned to Rina.
  await page.getByLabel('Search').fill('Investigate slow search')
  await lane(page, 'todo').getByRole('button', { name: /Investigate slow search/ }).click()

  const panel = page.getByRole('dialog')
  await expect(panel).toContainText('Only the creator, the assignee, or an admin can change this task.')
  await expect(panel.getByLabel('Title')).toBeDisabled()
  await expect(panel.getByRole('button', { name: 'Delete task' })).toHaveCount(0)
  await expect(panel.getByLabel('Add a comment')).toBeEnabled()
})

test('on a phone the lanes become tabs and nothing scrolls sideways', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 })
  await signIn(page, 'admin@lajur.test')

  await expect(lane(page, 'todo')).toBeVisible()
  await expect(lane(page, 'done')).toBeHidden()

  await page.getByRole('button', { name: /^Done/ }).click()
  await expect(lane(page, 'done')).toBeVisible()
  await expect(lane(page, 'todo')).toBeHidden()

  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)
  expect(overflow).toBe(0)
})
