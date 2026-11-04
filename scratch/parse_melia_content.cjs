const fs = require('fs');

const contentPath = 'C:\\Users\\thinh\\.gemini\\antigravity\\brain\\f7f04c79-eb8e-4629-a3ea-4f9b473252e5\\.system_generated\\steps\\16041\\content.md';
const html = fs.readFileSync(contentPath, 'utf8');

// Find all dam.melia.com image URLs
const imgRegex = /https:\/\/dam\.melia\.com\/[^"'\s\)]+/g;
const imgMatches = Array.from(new Set(html.match(imgRegex) || []));
console.log('Total Melia images found:', imgMatches.length);
imgMatches.slice(0, 15).forEach((url, i) => console.log(`Img ${i}: ${url}`));

// Find all section IDs and headings
const sectionRegex = /<section[^>]*id="([^"]+)"[^>]*>([\s\S]*?)<\/section>/g;
let match;
const sections = [];
while ((match = sectionRegex.exec(html)) !== null) {
    const id = match[1];
    const secHtml = match[2];
    const headings = secHtml.match(/<h[1-6][^>]*>([\s\S]*?)<\/h[1-6]>/g) || [];
    const cleanHeadings = headings.map(h => h.replace(/<[^>]+>/g, '').trim());
    sections.push({ id, cleanHeadings });
}

console.log('\nSections found:');
console.log(JSON.stringify(sections, null, 2));

// Also let's search for "New opening", "Meliá Aurea Nha Trang", "Rooms", "Dining", "Spa", "Location"
const searchTerms = ['Aurea', 'room', 'suite', 'restaurant', 'bar', 'spa', 'pool', 'experience', 'services', 'activities'];
searchTerms.forEach(term => {
    const r = new RegExp(`.{0,50}${term}.{0,50}`, 'gi');
    const m = html.match(r);
    console.log(`\nMentions of "${term}": ${m ? m.length : 0}`);
});
