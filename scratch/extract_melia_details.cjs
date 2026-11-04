const fs = require('fs');

const contentPath = 'C:\\Users\\thinh\\.gemini\\antigravity\\brain\\f7f04c79-eb8e-4629-a3ea-4f9b473252e5\\.system_generated\\steps\\16041\\content.md';
const html = fs.readFileSync(contentPath, 'utf8');

// The Next.js page in content.md has streaming chunks <div hidden id="S:...">...</div>
// Let's find all divs with id starting with S: and extract their inner content
const templateMatches = html.match(/<div hidden id="S:[^"]+">([\s\S]*?)<\/div>/g) || [];
console.log('Template blocks count:', templateMatches.length);

const blockData = [];
templateMatches.forEach((blk, idx) => {
    // Extract section id
    const secIdMatch = blk.match(/<section[^>]*id="([^"]+)"/);
    const secId = secIdMatch ? secIdMatch[1] : `block_${idx}`;

    // Extract text
    const textOnly = blk.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();

    // Extract all image sources
    const imgs = [];
    const imgMatches = blk.match(/https:\/\/dam\.melia\.com\/[^"'\s\)]+/g) || [];
    imgMatches.forEach(src => {
        // take clean high res URL
        const clean = src.split('?')[0];
        if (!imgs.includes(clean)) imgs.push(clean);
    });

    blockData.push({
        secId,
        textSample: textOnly.slice(0, 400),
        imgs
    });
});

fs.writeFileSync('C:\\Users\\thinh\\.gemini\\antigravity\\brain\\f7f04c79-eb8e-4629-a3ea-4f9b473252e5\\scratch\\melia_blocks.json', JSON.stringify(blockData, null, 2));
console.log('Saved melia_blocks.json with', blockData.length, 'blocks');
blockData.forEach(b => console.log(`- [${b.secId}]: ${b.textSample.slice(0, 80)}... (Images: ${b.imgs.length})`));
