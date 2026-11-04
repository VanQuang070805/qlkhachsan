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
        '--remote-debugging-port=9328',
        '--no-first-run',
        '--no-default-browser-check',
        '--user-data-dir=' + path.join(ARTIFACT_DIR, 'scratch', 'chrome-profile-melia')
    ]);

    let targets = null;
    for (let i = 0; i < 15; i++) {
        try {
            await new Promise(r => setTimeout(r, 600));
            targets = await getJson('http://127.0.0.1:9328/json');
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

    // 1. Desktop 1080p Hero
    await send('Emulation.setDeviceMetricsOverride', {
        width: 1920,
        height: 1080,
        deviceScaleFactor: 1,
        mobile: false
    });

    await send('Page.navigate', { url: 'http://127.0.0.1:8000/' });
    await new Promise(r => setTimeout(r, 2500));

    let shot = await send('Page.captureScreenshot', {
        format: 'png',
        clip: { x: 0, y: 0, width: 1920, height: 1080, scale: 1 }
    });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_clone_hero_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved melia_clone_hero_1080p.png');

    // 2. Desktop 1080p Mid Sections (Overview & Services)
    await send('Runtime.evaluate', { expression: 'window.scrollTo(0, 950);' });
    await new Promise(r => setTimeout(r, 1200));
    shot = await send('Page.captureScreenshot', {
        format: 'png',
        clip: { x: 0, y: 0, width: 1920, height: 1080, scale: 1 }
    });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_clone_services_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved melia_clone_services_1080p.png');

    // 3. Desktop 1080p Rooms & Dining
    await send('Runtime.evaluate', { expression: 'window.scrollTo(0, 2100);' });
    await new Promise(r => setTimeout(r, 1200));
    shot = await send('Page.captureScreenshot', {
        format: 'png',
        clip: { x: 0, y: 0, width: 1920, height: 1080, scale: 1 }
    });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_clone_rooms_dining_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved melia_clone_rooms_dining_1080p.png');

    // 4. Desktop 1080p Activities & Packages & FAQs
    await send('Runtime.evaluate', { expression: 'window.scrollTo(0, 3600);' });
    await new Promise(r => setTimeout(r, 1200));
    shot = await send('Page.captureScreenshot', {
        format: 'png',
        clip: { x: 0, y: 0, width: 1920, height: 1080, scale: 1 }
    });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_clone_activities_faqs_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved melia_clone_activities_faqs_1080p.png');

    // 5. Mobile (390x844) Hero
    await send('Emulation.setDeviceMetricsOverride', {
        width: 390,
        height: 844,
        deviceScaleFactor: 2,
        mobile: true
    });
    await send('Page.navigate', { url: 'http://127.0.0.1:8000/' });
    await new Promise(r => setTimeout(r, 2000));
    shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_clone_mobile_hero.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved melia_clone_mobile_hero.png');

    ws.close();
    chrome.kill();
    console.log('All verification screenshots complete!');
}

run().catch(console.error);
