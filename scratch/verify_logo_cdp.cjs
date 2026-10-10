const { spawn, execSync } = require('child_process');
const http = require('http');
const fs = require('fs');
const path = require('path');

const ARTIFACT_DIR = 'C:\\Users\\thinh\\.gemini\\antigravity\\brain\\f7f04c79-eb8e-4629-a3ea-4f9b473252e5';

let sessionCookieValue = null;
try {
    const output = execSync('php "C:\\Users\\thinh\\.gemini\\antigravity\\brain\\f7f04c79-eb8e-4629-a3ea-4f9b473252e5\\scratch\\test_login_post.php"').toString();
    const cookieMatch = output.match(/Cookie: posh-boutique-session = ([^\r\n]+)/);
    if (cookieMatch) sessionCookieValue = decodeURIComponent(cookieMatch[1].trim());
} catch (e) {
    console.log('Login cookie fetch error:', e.message);
}

function getJson(url) {
    return new Promise((resolve, reject) => {
        http.get(url, res => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve(JSON.parse(data)));
        }).on('error', reject);
    });
}

async function run() {
    const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        '--headless=new',
        '--remote-debugging-port=9314',
        '--no-first-run',
        '--no-default-browser-check',
        '--user-data-dir=' + path.join(ARTIFACT_DIR, 'scratch', 'chrome-profile-logo3')
    ]);

    let targets = null;
    for (let i = 0; i < 15; i++) {
        try {
            await new Promise(r => setTimeout(r, 600));
            targets = await getJson('http://127.0.0.1:9314/json');
            if (targets && targets.length > 0) break;
        } catch (e) {}
    }

    if (!targets) throw new Error('Chrome failed to start');

    const pageTarget = targets.find(t => t.type === 'page') || targets[0];
    const wsUrl = pageTarget.webSocketDebuggerUrl;

    const ws = new WebSocket(wsUrl);
    await new Promise(resolve => ws.addEventListener('open', resolve));

    let msgId = 1;
    function send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = msgId++;
            const handler = evt => {
                const parsed = JSON.parse(evt.data);
                if (parsed.id === id) {
                    ws.removeEventListener('message', handler);
                    if (parsed.error) reject(parsed.error);
                    else resolve(parsed.result);
                }
            };
            ws.addEventListener('message', handler);
            ws.send(JSON.stringify({ id, method, params }));
        });
    }

    await send('Network.enable');
    await send('Page.enable');

    // 1. Desktop Notch Header
    await send('Emulation.setDeviceMetricsOverride', {
        width: 1920,
        height: 1080,
        deviceScaleFactor: 1,
        mobile: false
    });

    await send('Page.navigate', { url: 'http://127.0.0.1:8000/' });
    await new Promise(r => setTimeout(r, 2000));

    let shot = await send('Page.captureScreenshot', {
        format: 'png',
        clip: { x: 0, y: 0, width: 1920, height: 220, scale: 1 }
    });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'v5_homepage_notch_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved notch 1080p');

    // 2. Footer
    await send('Runtime.evaluate', { expression: 'window.scrollTo(0, document.body.scrollHeight);' });
    await new Promise(r => setTimeout(r, 1200));
    shot = await send('Page.captureScreenshot', {
        format: 'png',
        clip: { x: 0, y: 550, width: 1920, height: 530, scale: 1 }
    });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'v5_homepage_footer_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved footer 1080p');

    // 3. Admin Topbar
    if (sessionCookieValue) {
        await send('Network.setCookie', {
            name: 'posh-boutique-session',
            value: sessionCookieValue,
            domain: '127.0.0.1',
            path: '/'
        });
        await send('Page.navigate', { url: 'http://127.0.0.1:8000/admin/reports' });
        await new Promise(r => setTimeout(r, 2000));
        shot = await send('Page.captureScreenshot', {
            format: 'png',
            clip: { x: 0, y: 0, width: 1920, height: 120, scale: 1 }
        });
        fs.writeFileSync(path.join(ARTIFACT_DIR, 'v5_admin_topbar_1080p.png'), Buffer.from(shot.data, 'base64'));
        console.log('Saved admin topbar 1080p');
    }

    // 4. Mobile View (Drawer)
    await send('Emulation.setDeviceMetricsOverride', {
        width: 390,
        height: 844,
        deviceScaleFactor: 2,
        mobile: true
    });
    await send('Page.navigate', { url: 'http://127.0.0.1:8000/' });
    await new Promise(r => setTimeout(r, 2000));

    // Open mobile drawer
    await send('Runtime.evaluate', {
        expression: 'const btn = document.querySelector("[data-drawer-open]"); if (btn) btn.click();'
    });
    await new Promise(r => setTimeout(r, 1000));

    shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'v5_mobile_drawer.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved mobile drawer');

    ws.close();
    chrome.kill();
    console.log('All screenshots captured successfully!');
}

run().catch(console.error);
