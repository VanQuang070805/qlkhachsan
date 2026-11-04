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
        '--remote-debugging-port=9320',
        '--no-first-run',
        '--no-default-browser-check',
        '--user-data-dir=' + path.join(ARTIFACT_DIR, 'scratch', 'chrome-melia')
    ]);

    let targets = null;
    for (let i = 0; i < 15; i++) {
        try {
            await new Promise(r => setTimeout(r, 600));
            targets = await getJson('http://127.0.0.1:9320/json');
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
        width: 1440,
        height: 900,
        deviceScaleFactor: 1,
        mobile: false
    });

    console.log('Navigating to Meliá page...');
    await send('Page.navigate', { url: 'https://www.melia.com/en/hotels/vietnam/nha-trang/hotel-aurea-nha-trang-6122' });
    await new Promise(r => setTimeout(r, 6000));

    // Capture hero
    let shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_hero.png'), Buffer.from(shot.data, 'base64'));
    console.log('Saved melia_hero.png');

    // Extract section titles, texts, and images from the page
    const structure = await send('Runtime.evaluate', {
        expression: `(() => {
            const sections = [];
            document.querySelectorAll('section, main > div').forEach(sec => {
                const id = sec.id || sec.getAttribute('data-slot') || sec.className;
                const h = Array.from(sec.querySelectorAll('h1, h2, h3, h4, p')).map(el => el.innerText.trim()).filter(t => t.length > 0);
                const imgs = Array.from(sec.querySelectorAll('img')).map(img => ({
                    src: img.src,
                    alt: img.alt
                })).filter(img => img.src && !img.src.includes('data:'));
                if (h.length > 0 || imgs.length > 0) {
                    sections.push({ id, headings: h.slice(0, 10), imgs: imgs.slice(0, 5) });
                }
            });
            return sections;
        })()`,
        returnByValue: true
    });

    fs.writeFileSync(path.join(ARTIFACT_DIR, 'scratch', 'melia_structure.json'), JSON.stringify(structure.value, null, 2));
    console.log('Structure extracted, sections count:', structure.value ? structure.value.length : 0);

    // Scroll down and take 2 more screenshots
    await send('Runtime.evaluate', { expression: 'window.scrollBy(0, 1000);' });
    await new Promise(r => setTimeout(r, 1500));
    shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_mid.png'), Buffer.from(shot.data, 'base64'));

    await send('Runtime.evaluate', { expression: 'window.scrollBy(0, 1500);' });
    await new Promise(r => setTimeout(r, 1500));
    shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(ARTIFACT_DIR, 'melia_rooms_sec.png'), Buffer.from(shot.data, 'base64'));

    ws.close();
    chrome.kill();
    console.log('Done inspecting Melia!');
}

run().catch(console.error);
