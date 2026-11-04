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
        '--remote-debugging-port=9334',
        '--no-first-run',
        '--no-default-browser-check',
        '--user-data-dir=' + path.join(ARTIFACT_DIR, 'scratch', 'chrome-profile-sec')
    ]);

    let targets = null;
    for (let i = 0; i < 15; i++) {
        try {
            await new Promise(r => setTimeout(r, 600));
            targets = await getJson('http://127.0.0.1:9334/json');
            if (targets && targets.length > 0) break;
        } catch (e) {}
    }

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

    // 1. Contact Page at 1920x1080
    await send('Emulation.setDeviceMetricsOverride', {
        width: 1920,
        height: 1080,
        deviceScaleFactor: 1,
        mobile: false
    });

    await send('Page.navigate', { url: 'http://127.0.0.1:8000/contact' });
    await new Promise(r => setTimeout(r, 2500));

    let shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'contact_hero_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved contact hero');

    await send('Runtime.evaluate', {
        expression: 'document.querySelector("#contact-details")?.scrollIntoView();'
    });
    await new Promise(r => setTimeout(r, 1000));
    shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'contact_channels_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved contact channels');

    // 2. Rooms Index Page at 1920x1080
    await send('Page.navigate', { url: 'http://127.0.0.1:8000/rooms' });
    await new Promise(r => setTimeout(r, 2500));

    shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'rooms_hero_search_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved rooms hero & search');

    await send('Runtime.evaluate', {
        expression: 'document.querySelector("#catalogue-title")?.scrollIntoView();'
    });
    await new Promise(r => setTimeout(r, 1000));
    shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'rooms_cards_grid_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved rooms cards grid');

    // 3. Rooms Mobile 390x844
    await send('Emulation.setDeviceMetricsOverride', {
        width: 390,
        height: 844,
        deviceScaleFactor: 2,
        mobile: true
    });
    await send('Page.navigate', { url: 'http://127.0.0.1:8000/rooms' });
    await new Promise(r => setTimeout(r, 2000));
    shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'rooms_mobile_390p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved rooms mobile');

    ws.close();
    chrome.kill();
    console.log('Secondary pages screenshots complete!');
}

run().catch(console.error);
