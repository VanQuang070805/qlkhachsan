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
        '--remote-debugging-port=9332',
        '--no-first-run',
        '--no-default-browser-check',
        '--user-data-dir=' + path.join(ARTIFACT_DIR, 'scratch', 'chrome-profile-melia2')
    ]);

    let targets = null;
    for (let i = 0; i < 15; i++) {
        try {
            await new Promise(r => setTimeout(r, 600));
            targets = await getJson('http://127.0.0.1:9332/json');
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

    await send('Emulation.setDeviceMetricsOverride', {
        width: 1920,
        height: 1080,
        deviceScaleFactor: 1,
        mobile: false
    });

    await send('Page.navigate', { url: 'http://127.0.0.1:8000/' });
    await new Promise(r => setTimeout(r, 2500));

    // Section 0: Hero
    let shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_clone_hero_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved hero');

    // Section 1: Overview
    await send('Runtime.evaluate', {
        expression: 'document.querySelector("#hotel-overview").scrollIntoView();'
    });
    await new Promise(r => setTimeout(r, 1000));
    shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_sec_overview_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved overview');

    // Section 2: Services
    await send('Runtime.evaluate', {
        expression: 'document.querySelector("#hotel-highlighted-services").scrollIntoView();'
    });
    await new Promise(r => setTimeout(r, 1000));
    shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_sec_services_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved services');

    // Section 3: Rooms
    await send('Runtime.evaluate', {
        expression: 'document.querySelector("#hotel-accommodation-rooms").scrollIntoView();'
    });
    await new Promise(r => setTimeout(r, 1000));
    shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_sec_rooms_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved rooms');

    // Section 4: Dining
    await send('Runtime.evaluate', {
        expression: 'document.querySelector("#hotel-restaurants").scrollIntoView();'
    });
    await new Promise(r => setTimeout(r, 1000));
    shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_sec_dining_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved dining');

    // Section 5: Activities
    await send('Runtime.evaluate', {
        expression: 'document.querySelector("#hotel-activities").scrollIntoView();'
    });
    await new Promise(r => setTimeout(r, 1000));
    shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_sec_activities_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved activities');

    // Section 6: Packages & FAQs
    await send('Runtime.evaluate', {
        expression: 'document.querySelector("#hotel-faqs").scrollIntoView();'
    });
    await new Promise(r => setTimeout(r, 1000));
    shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_sec_faqs_1080p.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved faqs');

    ws.close();
    chrome.kill();
    console.log('Section screenshots complete!');
}

run().catch(console.error);
