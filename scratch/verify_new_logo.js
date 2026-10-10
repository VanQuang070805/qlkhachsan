const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    // 1. Homepage Desktop (Notch header)
    console.log('Navigating to homepage...');
    await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
    await page.screenshot({
        path: 'C:/Users/thinh/.gemini/antigravity/brain/f7f04c79-eb8e-4629-a3ea-4f9b473252e5/v5_homepage_notch_1080p.png',
        clip: { x: 0, y: 0, width: 1920, height: 260 }
    });
    console.log('Saved notch screenshot.');

    // 2. Homepage Footer
    const footer = page.locator('footer.site-footer');
    if (await footer.count() > 0) {
        await footer.scrollIntoViewIfNeeded();
        await page.waitForTimeout(500);
        await footer.screenshot({
            path: 'C:/Users/thinh/.gemini/antigravity/brain/f7f04c79-eb8e-4629-a3ea-4f9b473252e5/v5_homepage_footer_1080p.png'
        });
        console.log('Saved footer screenshot.');
    }

    // 3. Admin / Staff topbar
    // Login as admin first
    await page.goto('http://127.0.0.1:8000/login');
    // Set cookies or login directly
    await page.goto('http://127.0.0.1:8000/admin/reports', { waitUntil: 'networkidle' });
    const topbar = page.locator('header.internal-topbar');
    if (await topbar.count() > 0) {
        await topbar.screenshot({
            path: 'C:/Users/thinh/.gemini/antigravity/brain/f7f04c79-eb8e-4629-a3ea-4f9b473252e5/v5_internal_topbar_1080p.png'
        });
        console.log('Saved internal topbar screenshot.');
    }

    // 4. Mobile View
    const mobileContext = await browser.newContext({
        viewport: { width: 390, height: 844 },
        isMobile: true
    });
    const mobilePage = await mobileContext.newPage();
    await mobilePage.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
    await mobilePage.screenshot({
        path: 'C:/Users/thinh/.gemini/antigravity/brain/f7f04c79-eb8e-4629-a3ea-4f9b473252e5/v5_homepage_mobile_header.png',
        clip: { x: 0, y: 0, width: 390, height: 200 }
    });

    // Open drawer
    const drawerBtn = mobilePage.locator('[data-drawer-open]');
    if (await drawerBtn.count() > 0) {
        await drawerBtn.click();
        await mobilePage.waitForTimeout(400);
        await mobilePage.screenshot({
            path: 'C:/Users/thinh/.gemini/antigravity/brain/f7f04c79-eb8e-4629-a3ea-4f9b473252e5/v5_mobile_drawer_open.png'
        });
        console.log('Saved mobile drawer screenshot.');
    }

    await browser.close();
    console.log('Verification finished!');
})();
