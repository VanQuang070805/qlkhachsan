const { spawn, spawnSync } = require('node:child_process');
const fs = require('node:fs');
const http = require('node:http');
const path = require('node:path');

const outputDir = path.resolve('output/playwright/antislop-interactions');
fs.mkdirSync(outputDir, { recursive: true });

const password = 'E2e-Antislop-2026!';
const accounts = {
  customer: { email: 'e2e.antislop.customer@example.test', username: 'e2e_antislop_customer', role: 'customer' },
  staff: { email: 'e2e.antislop.staff@example.test', username: 'e2e_antislop_staff', role: 'receptionist' },
  admin: { email: 'e2e.antislop.admin@example.test', username: 'e2e_antislop_admin', role: 'admin' },
};

function php(code) {
  const result = spawnSync('php', ['artisan', 'tinker', '--execute', code], { cwd: process.cwd(), encoding: 'utf8' });
  if (result.status !== 0) throw new Error(result.stderr || result.stdout || 'Tinker command failed');
}

function prepareAccounts() {
  for (const account of Object.values(accounts)) {
    php(`App\\Models\\User::updateOrCreate(['email' => '${account.email}'], ['username' => '${account.username}', 'password' => Illuminate\\Support\\Facades\\Hash::make('${password}'), 'fullname' => 'E2E Antislop', 'role' => '${account.role}', 'verified' => true]);`);
  }
}

function cleanupAccounts() {
  const emails = Object.values(accounts).map(account => `'${account.email}'`).join(',');
  php(`Illuminate\\Support\\Facades\\DB::table('users')->whereIn('email', [${emails}])->delete();`);
}

function getJson(url) {
  return new Promise((resolve, reject) => http.get(url, response => {
    let body = '';
    response.on('data', chunk => body += chunk);
    response.on('end', () => resolve(JSON.parse(body)));
  }).on('error', reject));
}

async function main() {
  prepareAccounts();
  const chrome = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
    '--headless=new', '--remote-debugging-port=9442', '--no-first-run', '--no-default-browser-check',
    `--user-data-dir=${path.join(outputDir, 'profile')}`,
  ]);
  const evidence = [];
  const consoleErrors = [];
  let socket;

  try {
    let targets;
    for (let attempt = 0; attempt < 40 && !targets; attempt += 1) {
      await new Promise(resolve => setTimeout(resolve, 250));
      try { targets = await getJson('http://127.0.0.1:9442/json'); } catch {}
    }
    if (!targets) throw new Error('Chrome CDP did not start');
    const target = targets.find(item => item.type === 'page');
    socket = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise(resolve => socket.addEventListener('open', resolve));

    let callId = 0;
    const pending = new Map();
    socket.addEventListener('message', event => {
      const message = JSON.parse(event.data);
      if (message.method === 'Runtime.exceptionThrown') {
        const details = message.params.exceptionDetails;
        consoleErrors.push({
          text: details.text,
          description: details.exception?.description,
          url: details.url,
          line: details.lineNumber,
          column: details.columnNumber,
          stack: details.stackTrace?.callFrames,
        });
      }
      if (message.method === 'Log.entryAdded' && message.params.entry.level === 'error') consoleErrors.push(message.params.entry.text);
      if (!message.id || !pending.has(message.id)) return;
      const callbacks = pending.get(message.id);
      pending.delete(message.id);
      message.error ? callbacks.reject(message.error) : callbacks.resolve(message.result);
    });
    const send = (method, params = {}) => new Promise((resolve, reject) => {
      const id = ++callId;
      pending.set(id, { resolve, reject });
      socket.send(JSON.stringify({ id, method, params }));
    });
    const evaluate = async expression => (await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true })).result.value;
    const waitFor = async (expression, label, timeout = 12000) => {
      const started = Date.now();
      while (Date.now() - started < timeout) {
        if (await evaluate(`Boolean(${expression})`)) return;
        await new Promise(resolve => setTimeout(resolve, 200));
      }
      throw new Error(`Timeout: ${label}`);
    };
    const navigate = async (url, width = 375, height = 812) => {
      await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 600 });
      await send('Page.navigate', { url });
      await waitFor(`document.readyState === 'complete'`, `load ${url}`, 20000);
      await new Promise(resolve => setTimeout(resolve, 1200));
    };
    const click = async selector => {
      const point = await evaluate(`(() => {
        const el = document.querySelector(${JSON.stringify(selector)});
        if (!el) return null;
        el.scrollIntoView({ block: 'center', inline: 'center' });
        const rect = el.getBoundingClientRect();
        return { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 };
      })()`);
      if (!point) throw new Error(`Missing control: ${selector}`);
      await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: point.x, y: point.y });
      await send('Input.dispatchMouseEvent', { type: 'mousePressed', x: point.x, y: point.y, button: 'left', clickCount: 1 });
      await send('Input.dispatchMouseEvent', { type: 'mouseReleased', x: point.x, y: point.y, button: 'left', clickCount: 1 });
    };
    const fill = async (selector, value) => {
      const found = await evaluate(`(() => { const el = document.querySelector(${JSON.stringify(selector)}); if (!el) return false; el.value = ${JSON.stringify(value)}; el.dispatchEvent(new Event('input', { bubbles: true })); el.dispatchEvent(new Event('change', { bubbles: true })); return true; })()`);
      if (!found) throw new Error(`Missing field: ${selector}`);
    };
    const assert = (condition, label) => {
      if (!condition) throw new Error(`Assertion failed: ${label}`);
      evidence.push(label);
    };
    const screenshot = async name => {
      const shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
      fs.writeFileSync(path.join(outputDir, `${name}.png`), Buffer.from(shot.data, 'base64'));
    };
    const clearSession = async () => {
      await send('Network.clearBrowserCookies');
      await evaluate(`localStorage.clear(); sessionStorage.clear();`);
    };

    await send('Page.enable');
    await send('Runtime.enable');
    await send('Log.enable');
    await send('Network.enable');

    // Customer public mobile controls.
    await navigate('http://127.0.0.1:18080/', 375, 812);
    await click('[data-drawer-open]');
    await waitFor(`document.querySelector('#mobile-nav-drawer')?.classList.contains('is-open')`, 'mobile drawer opens');
    assert(await evaluate(`document.activeElement === document.querySelector('[data-drawer-close]')`), 'Customer mobile drawer opens and moves focus to Close');
    await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape' });
    await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape' });
    await waitFor(`!document.querySelector('#mobile-nav-drawer')?.classList.contains('is-open')`, 'mobile drawer closes');
    evidence.push('Customer mobile drawer closes with Escape');

    assert(await evaluate(`(() => { const el = document.querySelector('[data-drawer-open]'); el.focus(); const s = getComputedStyle(el); return s.outlineStyle !== 'none' && parseFloat(s.outlineWidth) >= 2; })()`), 'Customer keyboard focus ring is visibly rendered');
    await click('.ios-tab-item--chat');
    await waitFor(`getComputedStyle(document.querySelector('#royal-chat-panel')).display !== 'none'`, 'chat opens');
    assert(await evaluate(`Boolean(document.querySelector('.royal-chat__pico--header'))`), 'Pico remains visible inside the mobile chat panel');
    await click('.royal-chat__close-btn');
    await waitFor(`getComputedStyle(document.querySelector('#royal-chat-panel')).display === 'none'`, 'chat closes');
    evidence.push('Chat opens from dock and closes from its Close control');

    const faqExists = await evaluate(`Boolean(document.querySelector('.melia-faq-btn'))`);
    if (faqExists) {
      const faqSelector = '.melia-faq-item:not(.is-open) .melia-faq-btn';
      const targetFaqId = await evaluate(`document.querySelector(${JSON.stringify(faqSelector)})?.id`);
      await evaluate(`document.querySelector(${JSON.stringify(faqSelector)}).click()`);
      assert(await evaluate(`document.querySelector(${JSON.stringify(`#${targetFaqId}`)}).getAttribute('aria-expanded') === 'true'`), 'FAQ expands from its real button');
      await evaluate(`document.querySelector(${JSON.stringify(`#${targetFaqId}`)}).click()`);
      assert(await evaluate(`document.querySelector(${JSON.stringify(`#${targetFaqId}`)}).getAttribute('aria-expanded') === 'false'`), 'FAQ collapses from the same button');
    }
    assert(await evaluate(`document.documentElement.scrollWidth <= innerWidth`), 'Customer home has no document-level horizontal overflow at 375px');
    await screenshot('customer-mobile-controls');

    // Customer authenticated portal.
    await clearSession();
    await navigate('http://127.0.0.1:18080/login', 375, 812);
    await fill('#email', accounts.customer.email);
    await fill('#password', password);
    await click('form[action$="/login"] button[type="submit"]');
    await waitFor(`location.pathname !== '/login'`, 'customer login redirect', 20000);
    await waitFor(`document.readyState === 'complete'`, 'customer destination load', 20000);
    evidence.push('Customer login submits and reaches an authenticated destination');
    await navigate('http://127.0.0.1:18080/account', 375, 812);
    assert(await evaluate(`location.pathname === '/account' && Boolean(document.querySelector('form'))`), 'Customer account page renders with its editable form');

    // Staff portal controls.
    await clearSession();
    await navigate('http://127.0.0.1:18081/staff/login', 375, 812);
    await fill('#username', accounts.staff.username);
    await fill('#password', password);
    await click('button[type="submit"]');
    await waitFor(`location.pathname.includes('/staff/') && location.pathname !== '/staff/login'`, 'staff login redirect', 20000);
    await waitFor(`document.readyState === 'complete' && Boolean(document.querySelector('#sidebarMobileToggle'))`, 'staff destination load', 20000);
    evidence.push('Staff login submits and reaches the room operations portal');
    await click('#sidebarMobileToggle');
    await waitFor(`document.querySelector('#workspaceSidebar')?.classList.contains('is-open')`, 'staff sidebar opens');
    assert(await evaluate(`document.body.classList.contains('sidebar-mobile-open')`), 'Staff mobile sidebar opens without hiding its navigation');
    await click('#sidebarBackdrop');
    await waitFor(`!document.querySelector('#workspaceSidebar')?.classList.contains('is-open')`, 'staff sidebar closes');
    evidence.push('Staff sidebar closes from its backdrop');
    if (await evaluate(`Boolean(document.querySelector('[data-status-val="available"]'))`)) {
      await click('[data-status-val="available"]');
      assert(await evaluate(`document.querySelector('#filter-status')?.value === 'available'`), 'Staff room-status filter updates its real filter state');
      await click('.btn-reset-filters');
      assert(await evaluate(`document.querySelector('#filter-status')?.value === 'Tất cả'`), 'Staff filter Reset restores all statuses');
    }
    assert(await evaluate(`document.documentElement.scrollWidth <= innerWidth`), 'Staff portal has no document-level horizontal overflow at 375px');
    await screenshot('staff-mobile-controls');

    // Admin portal controls and data filter.
    await clearSession();
    await navigate('http://127.0.0.1:18082/admin/login', 1440, 900);
    await fill('#username', accounts.admin.username);
    await fill('#password', password);
    await click('button[type="submit"]');
    await waitFor(`location.pathname === '/admin/reports'`, 'admin login redirect', 20000);
    await waitFor(`document.readyState === 'complete' && Boolean(document.querySelector('input[name="start_date"]'))`, 'admin dashboard load', 20000);
    evidence.push('Admin login submits and reaches the database-backed dashboard');
    await fill('input[name="start_date"]', '2025-01-01');
    await fill('input[name="end_date"]', '2025-12-31');
    await click('.ds-filter-apply');
    await waitFor(`location.search.includes('start_date=2025-01-01') && location.search.includes('end_date=2025-12-31')`, 'admin filter applies', 20000);
    evidence.push('Admin report date filter submits and persists both database query parameters');
    await click('[data-command-open]');
    await waitFor(`!document.querySelector('#commandPalette')?.hidden`, 'command palette opens');
    await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape' });
    await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape' });
    await waitFor(`document.querySelector('#commandPalette')?.hidden`, 'command palette closes');
    evidence.push('Admin command palette opens and closes with Escape');
    assert(await evaluate(`document.documentElement.scrollWidth <= innerWidth`), 'Admin dashboard has no document-level horizontal overflow at 1440px');
    await screenshot('admin-dashboard-controls');

    assert(consoleErrors.length === 0, 'No uncaught browser exceptions or console errors occurred during the click-through');
    fs.writeFileSync(path.join(outputDir, 'report.json'), JSON.stringify({ passed: true, evidence, consoleErrors }, null, 2));
    process.stdout.write(JSON.stringify({ passed: true, evidence, consoleErrors }, null, 2));
  } catch (error) {
    try {
      const debug = socket
        ? await new Promise(resolve => {
            const id = 999999;
            const onMessage = event => {
              const message = JSON.parse(event.data);
              if (message.id !== id) return;
              socket.removeEventListener('message', onMessage);
              resolve(message.result?.result?.value || {});
            };
            socket.addEventListener('message', onMessage);
            socket.send(JSON.stringify({
              id,
              method: 'Runtime.evaluate',
              params: {
                expression: `(() => { const toggle = document.querySelector('#sidebarMobileToggle'); const r = toggle?.getBoundingClientRect(); return { url: location.href, viewport: [innerWidth, innerHeight], body: document.body.className, sidebar: document.querySelector('#workspaceSidebar')?.className, toggle: Boolean(toggle), sidebarBound: toggle?.dataset.sidebarBound, expanded: toggle?.getAttribute('aria-expanded'), rect: r ? [r.x,r.y,r.width,r.height] : null, display: toggle ? getComputedStyle(toggle).display : null, hit: r ? document.elementFromPoint(r.x + r.width/2, r.y + r.height/2)?.outerHTML?.slice(0,240) : null, html: toggle?.outerHTML }; })()`,
                returnByValue: true,
              },
            }));
            setTimeout(() => resolve({}), 1000);
          })
        : {};
      error.message += ` | debug=${JSON.stringify(debug)} | console=${JSON.stringify(consoleErrors)}`;
    } catch {}
    throw error;
  } finally {
    try { socket?.close(); } catch {}
    chrome.kill();
    cleanupAccounts();
  }
}

main().catch(error => {
  fs.writeFileSync(path.join(outputDir, 'report.json'), JSON.stringify({ passed: false, error: error.message }, null, 2));
  console.error(error);
  process.exitCode = 1;
});
