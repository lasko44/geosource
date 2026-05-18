import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';

puppeteer.use(StealthPlugin());

const url = process.argv[2];
const timeout = parseInt(process.argv[3] || '30000', 10);

if (!url) {
    console.error(JSON.stringify({ error: 'URL argument required' }));
    process.exit(1);
}

(async () => {
    let browser;
    try {
        browser = await puppeteer.launch({
            headless: 'new',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--no-first-run',
                '--no-zygote',
                '--single-process',
                '--disable-extensions',
            ],
        });

        const page = await browser.newPage();

        await page.setViewport({ width: 1920, height: 1080 });
        await page.setExtraHTTPHeaders({
            'Accept-Language': 'en-US,en;q=0.9',
        });

        await page.goto(url, {
            waitUntil: 'networkidle2',
            timeout,
        });

        // Wait extra for any Cloudflare challenge to resolve
        await new Promise(r => setTimeout(r, 3000));

        // Check if still on a challenge page
        const content = await page.content();
        if (content.includes('challenge-platform') || content.includes('Just a moment')) {
            // Wait longer for challenge to complete
            await new Promise(r => setTimeout(r, 8000));
        }

        const html = await page.content();

        console.log(JSON.stringify({ html }));
    } catch (err) {
        console.error(JSON.stringify({ error: err.message }));
        process.exit(1);
    } finally {
        if (browser) await browser.close();
    }
})();
