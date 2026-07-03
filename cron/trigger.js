const puppeteer = require('puppeteer');

(async () => {
  const url = process.argv[2];
  if (!url) {
    console.error('Error: URL argument is required.');
    process.exit(1);
  }

  console.log(`Launching headless browser...`);
  const browser = await puppeteer.launch({
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });

  try {
    const page = await browser.newPage();
    console.log(`Navigating to: ${url}`);
    
    // Set a common User-Agent to look like a standard browser
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
    
    // Navigate and wait for the page load and JavaScript challenge to execute
    await page.goto(url, { waitUntil: 'networkidle2', timeout: 45000 });
    
    // Extract the response text
    const responseText = await page.evaluate(() => document.body.innerText);
    console.log('Response from server:');
    console.log(responseText);
    
    // Check if the response contains success status
    if (responseText.includes('"status":"success"')) {
      console.log('Cron triggered successfully!');
    } else {
      console.error('Cron completed with an unexpected or error response.');
      process.exit(1);
    }
  } catch (error) {
    console.error('Error during trigger execution:', error.message);
    process.exit(1);
  } finally {
    await browser.close();
  }
})();
