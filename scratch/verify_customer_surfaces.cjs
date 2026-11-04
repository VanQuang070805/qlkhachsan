const { spawn } = require('child_process');
const http = require('http');
const fs = require('fs');
const path = require('path');

const ARTIFACT_DIR = 'C:\\Users\\thinh\\.gemini\\antigravity\\brain\\f7f04c79-eb8e-4629-a3ea-4f9b473252e5';

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
        '--remote-debugging-port=9336',
        '--no-first-run',
        '--no-default-browser-check',
        '--user-data-dir=' + path.join(ARTIFACT_DIR, 'scratch', 'chrome-profile-cust')
    ]);

    let targets = null;
    for (let i = 0; i < 15; i++) {
        try {
            await new Promise(r => setTimeout(r, 600));
            targets = await getJson('http://127.0.0.1:9336/json');
            if (targets && targets.length > 0) break;
        } catch (e) {}
    }

    const pageTarget = targets.find(t => t.type === 'page') || targets[0];
    const ws = new WebSocket(pageTarget.webSocketDebuggerUrl);
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
    await send('Emulation.setDeviceMetricsOverride', {
        width: 1920,
        height: 1080,
        deviceScaleFactor: 1,
        mobile: false
    });

    // 1. Room Detail Hero & Badges
    await send('Page.navigate', { url: 'http://127.0.0.1:8000/rooms/1' });
    await new Promise(r => setTimeout(r, 2000));
    let snap = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'room_detail_melia_1080p.png'), Buffer.from(snap.data, 'base64'));
    console.log('Saved room detail 1080p');

    // 1b. Room Detail Scrolled to Specs & Booking Form
    await send('Runtime.evaluate', { expression: 'window.scrollTo(0, 750);' });
    await new Promise(r => setTimeout(r, 800));
    snap = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'room_detail_specs_1080p.png'), Buffer.from(snap.data, 'base64'));
    console.log('Saved room detail specs 1080p');

    // 2. Login Page
    await send('Page.navigate', { url: 'http://127.0.0.1:8000/login' });
    await new Promise(r => setTimeout(r, 1800));
    snap = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'login_melia_1080p.png'), Buffer.from(snap.data, 'base64'));
    console.log('Saved login 1080p');

    // 3. Register Page
    await send('Page.navigate', { url: 'http://127.0.0.1:8000/register' });
    await new Promise(r => setTimeout(r, 1800));
    snap = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'register_melia_1080p.png'), Buffer.from(snap.data, 'base64'));
    console.log('Saved register 1080p');

    // 4. Forgot Password Page
    await send('Page.navigate', { url: 'http://127.0.0.1:8000/forgot-password' });
    await new Promise(r => setTimeout(r, 1800));
    snap = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'forgot_melia_1080p.png'), Buffer.from(snap.data, 'base64'));
    console.log('Saved forgot password 1080p');

    chrome.kill();
    console.log('All customer verification screenshots captured successfully!');
    process.exit(0);
}
run();
