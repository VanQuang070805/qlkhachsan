const { spawn } = require('node:child_process');
const fs = require('node:fs');
const http = require('node:http');
const path = require('node:path');

const out = path.resolve('output/playwright/responsive-audit');
fs.mkdirSync(out, { recursive: true });

const pages = [
  ['customer-home', 'http://127.0.0.1:18080/'],
  ['customer-rooms', 'http://127.0.0.1:18080/rooms'],
  ['customer-contact', 'http://127.0.0.1:18080/contact'],
  ['customer-login', 'http://127.0.0.1:18080/login'],
  ['staff-entry', 'http://127.0.0.1:18081/'],
  ['admin-entry', 'http://127.0.0.1:18082/'],
];
const sizes = [[375, 812], [768, 1024], [1440, 900]];

function json(url) {
  return new Promise((resolve, reject) => http.get(url, response => {
    let body = '';
    response.on('data', chunk => body += chunk);
    response.on('end', () => resolve(JSON.parse(body)));
  }).on('error', reject));
}

async function main() {
  const chrome = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
    '--headless=new', '--remote-debugging-port=9441', '--no-first-run', '--no-default-browser-check',
    `--user-data-dir=${path.join(out, 'profile')}`,
  ]);
  let targets;
  for (let i = 0; i < 30 && !targets; i += 1) {
    await new Promise(resolve => setTimeout(resolve, 250));
    try { targets = await json('http://127.0.0.1:9441/json'); } catch {}
  }
  if (!targets) throw new Error('Chrome CDP did not start');
  const target = targets.find(item => item.type === 'page');
  const ws = new WebSocket(target.webSocketDebuggerUrl);
  await new Promise(resolve => ws.addEventListener('open', resolve));
  let id = 0;
  const pending = new Map();
  ws.addEventListener('message', event => {
    const data = JSON.parse(event.data);
    if (!data.id || !pending.has(data.id)) return;
    const { resolve, reject } = pending.get(data.id);
    pending.delete(data.id);
    data.error ? reject(data.error) : resolve(data.result);
  });
  const send = (method, params = {}) => new Promise((resolve, reject) => {
    const callId = ++id;
    pending.set(callId, { resolve, reject });
    ws.send(JSON.stringify({ id: callId, method, params }));
  });
  await send('Page.enable');
  await send('Runtime.enable');
  const report = [];
  for (const [name, url] of pages) {
    for (const [width, height] of sizes) {
      await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 600 });
      await send('Page.navigate', { url });
      await new Promise(resolve => setTimeout(resolve, 1800));
      const result = await send('Runtime.evaluate', { returnByValue: true, expression: `(() => {
        const doc = document.documentElement;
        const visible = el => { const s = getComputedStyle(el); const r = el.getBoundingClientRect(); return s.display !== 'none' && s.visibility !== 'hidden' && r.width > 0 && r.height > 0; };
        const overflow = [...document.querySelectorAll('body *')].filter(visible).map(el => {
          const r = el.getBoundingClientRect();
          return { tag: el.tagName.toLowerCase(), cls: String(el.className || '').slice(0, 90), left: Math.round(r.left), right: Math.round(r.right), width: Math.round(r.width) };
        }).filter(item => item.left < -2 || item.right > innerWidth + 2).slice(0, 20);
        const pico = document.querySelector('.royal-chat__launcher');
        const picoRect = pico ? pico.getBoundingClientRect() : null;
        return { url: location.href, title: document.title, viewport: innerWidth, scrollWidth: doc.scrollWidth,
          overflow, pico: picoRect ? { display: getComputedStyle(pico).display, left: Math.round(picoRect.left), top: Math.round(picoRect.top), width: Math.round(picoRect.width), height: Math.round(picoRect.height) } : null };
      })()` });
      const row = { page: name, width, ...result.result.value };
      report.push(row);
      const shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
      fs.writeFileSync(path.join(out, `${name}-${width}.png`), Buffer.from(shot.data, 'base64'));
    }
  }
  fs.writeFileSync(path.join(out, 'report.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  ws.close();
  chrome.kill();
}

main().catch(error => { console.error(error); process.exitCode = 1; });
