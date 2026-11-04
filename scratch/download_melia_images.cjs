const fs = require('fs');
const path = require('path');

const targetDir = path.join(__dirname, '..', 'public', 'images', 'melia');
if (!fs.existsSync(targetDir)) {
    fs.mkdirSync(targetDir, { recursive: true });
}

const imagesToDownload = [
    {
        name: 'hero-balcony.jpg',
        url: 'https://dam.melia.com/melia/file/rArPvtpTjvfhkgxrDnhC.jpg?im=RegionOfInterestCrop=(1665,2000),regionOfInterest=(2150,1433)',
        fallback: 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=2000&q=85'
    },
    {
        name: 'catamaran-ocean.jpg',
        url: 'https://dam.melia.com/melia/file/kd7YvhAX9XQD59NCcw5i.jpg?im=RegionOfInterestCrop=(1499,2000),regionOfInterest=(2241,1678.5)',
        fallback: 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=1600&q=85'
    },
    {
        name: 'wellness-meditation.jpg',
        url: 'https://dam.melia.com/melia/file/cpVv7pQ36CuFR2zMescY.jpg?im=RegionOfInterestCrop=(1500,2000),regionOfInterest=(1434,2150)',
        fallback: 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=1600&q=85'
    },
    {
        name: 'infinity-pool.jpg',
        url: 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1600&q=85'
    },
    {
        name: 'spa-wellness.jpg',
        url: 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=1600&q=85'
    },
    {
        name: 'dining-ciao-chao.jpg',
        url: 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=1600&q=85'
    },
    {
        name: 'sunset-beach-bar.jpg',
        url: 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1600&q=85'
    },
    {
        name: 'the-level-lounge.jpg',
        url: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1600&q=85'
    },
    {
        name: 'ocean-suite-bed.jpg',
        url: 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1600&q=85'
    },
    {
        name: 'island-yacht.jpg',
        url: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1600&q=85'
    }
];

async function downloadAll() {
    for (const item of imagesToDownload) {
        const dest = path.join(targetDir, item.name);
        try {
            console.log(`Downloading ${item.name} from ${item.url}...`);
            let res = await fetch(item.url, { headers: { 'User-Agent': 'Mozilla/5.0' } });
            if (!res.ok && item.fallback) {
                console.log(`Fallback for ${item.name}...`);
                res = await fetch(item.fallback, { headers: { 'User-Agent': 'Mozilla/5.0' } });
            }
            if (res.ok) {
                const buffer = Buffer.from(await res.arrayBuffer());
                fs.writeFileSync(dest, buffer);
                console.log(`Saved ${item.name} (${buffer.length} bytes)`);
            } else {
                console.log(`Failed ${item.name}: status ${res.status}`);
            }
        } catch (e) {
            console.log(`Error downloading ${item.name}: ${e.message}`);
            if (item.fallback) {
                try {
                    const res = await fetch(item.fallback);
                    const buffer = Buffer.from(await res.arrayBuffer());
                    fs.writeFileSync(dest, buffer);
                    console.log(`Saved fallback for ${item.name}`);
                } catch (err) {
                    console.log(`Failed fallback too for ${item.name}`);
                }
            }
        }
    }
}

downloadAll().then(() => console.log('All image downloads completed!'));
