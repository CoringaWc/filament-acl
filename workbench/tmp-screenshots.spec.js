import { test, expect } from '@playwright/test'

test.setTimeout(60_000)

test('capture permissions edit page', async ({ page }) => {
    await page.setViewportSize({ width: 1600, height: 1000 })

    await page.goto('http://127.0.0.1:8001/admin/login', {
        waitUntil: 'domcontentloaded',
    })

    const email = page
        .locator('input[type="email"], input[name="email"], #form\\.email')
        .first()
    const password = page
        .locator(
            'input[type="password"], input[name="password"], #form\\.password',
        )
        .first()

    await expect(email).toBeVisible()
    await expect(password).toBeVisible()

    await email.fill('moderator@filament-acl.test')
    await password.fill('password')

    await page.locator('button[type="submit"]').first().click()
    await page.waitForURL(/\/admin(\/)?$/, { timeout: 20_000 }).catch(() => {})

    await page.goto(
        'http://127.0.0.1:8001/admin/permissions/filament-acl-permissions/2/edit',
        { waitUntil: 'domcontentloaded' },
    )
    await page.waitForTimeout(3000)
    await page.screenshot({
        path: 'docs/images/permissions-edit.png',
        fullPage: true,
    })
})
