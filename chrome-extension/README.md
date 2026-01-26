# GeoSource Chrome Extension

Scan any webpage for GEO (Generative Engine Optimization) score directly from your browser.

## Setup

### 1. Create Icons

You need to create PNG icons in the `icons/` folder:
- `icon16.png` - 16x16 pixels
- `icon48.png` - 48x48 pixels
- `icon128.png` - 128x128 pixels

You can use your existing logo or create new icons. The icons should be square PNG files with a transparent background.

### 2. Configure API URL

Edit `popup.js` and update the `API_BASE` constant:

```javascript
// For production
const API_BASE = 'https://geosource.ai';

// For local development
const API_BASE = 'http://localhost:8000';
```

### 3. Load in Chrome

1. Open Chrome and go to `chrome://extensions/`
2. Enable "Developer mode" (toggle in top right)
3. Click "Load unpacked"
4. Select the `chrome-extension` folder
5. The extension should appear in your toolbar

## Development

### Local Testing

1. Update `API_BASE` to `http://localhost:8000`
2. Make sure your local server is running
3. The extension will work with your local API

### Reloading Changes

After making changes:
1. Go to `chrome://extensions/`
2. Click the refresh icon on the extension card
3. Close and reopen the popup to see changes

## How It Works

1. User enters their API token (generated from Settings > API Tokens)
2. Token is stored securely in Chrome's local storage
3. When scanning, the extension sends the current tab's URL to the API
4. Results are polled and displayed in the popup
5. Full reports can be viewed on the website

## API Endpoints Used

- `GET /api/extension/verify` - Verify API token
- `POST /api/extension/scan` - Start a new scan
- `GET /api/extension/scan/{uuid}/status` - Check scan status

## Permissions

- `activeTab` - Access current tab URL
- `storage` - Store API token locally
- `host_permissions` - Make requests to geosource.ai
