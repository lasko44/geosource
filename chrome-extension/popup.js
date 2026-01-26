// Configuration - change this to your production URL
const API_BASE = 'https://geosource.ai';
// For local development, use: const API_BASE = 'http://localhost:8000';

// DOM Elements
const loginSection = document.getElementById('login-section');
const scanSection = document.getElementById('scan-section');
const apiTokenInput = document.getElementById('api-token');
const saveTokenBtn = document.getElementById('save-token-btn');
const scanBtn = document.getElementById('scan-btn');
const urlDisplay = document.getElementById('url-display');
const loadingEl = document.getElementById('loading');
const resultEl = document.getElementById('result');
const resultContent = document.getElementById('result-content');
const recentList = document.getElementById('recent-list');
const errorEl = document.getElementById('error');
const errorMessage = document.getElementById('error-message');
const disconnectBtn = document.getElementById('disconnect-btn');
const getTokenLink = document.getElementById('get-token-link');
const dashboardLink = document.getElementById('dashboard-link');

// Initialize
document.addEventListener('DOMContentLoaded', init);

async function init() {
  // Set up links
  getTokenLink.href = `${API_BASE}/settings/api-tokens`;
  getTokenLink.addEventListener('click', (e) => {
    e.preventDefault();
    chrome.tabs.create({ url: `${API_BASE}/settings/api-tokens` });
  });

  dashboardLink.addEventListener('click', (e) => {
    e.preventDefault();
    chrome.tabs.create({ url: `${API_BASE}/dashboard` });
  });

  // Check for saved token
  const { apiToken } = await chrome.storage.local.get('apiToken');

  if (apiToken) {
    // Verify token is still valid
    const isValid = await verifyToken(apiToken);
    if (isValid) {
      showScanSection();
      await loadCurrentTab();
      await loadRecentScans();
    } else {
      // Token expired or invalid
      await chrome.storage.local.remove('apiToken');
      showLoginSection();
    }
  } else {
    showLoginSection();
  }

  // Event listeners
  saveTokenBtn.addEventListener('click', saveToken);
  scanBtn.addEventListener('click', scanCurrentPage);
  disconnectBtn.addEventListener('click', disconnect);

  // Allow Enter key to save token
  apiTokenInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      saveToken();
    }
  });
}

function showLoginSection() {
  loginSection.style.display = 'block';
  scanSection.style.display = 'none';
}

function showScanSection() {
  loginSection.style.display = 'none';
  scanSection.style.display = 'block';
}

function showError(message) {
  errorEl.style.display = 'block';
  errorMessage.textContent = message;
  setTimeout(() => {
    errorEl.style.display = 'none';
  }, 5000);
}

function hideError() {
  errorEl.style.display = 'none';
}

async function verifyToken(token) {
  try {
    const response = await fetch(`${API_BASE}/api/extension/verify`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    });
    return response.ok;
  } catch (error) {
    console.error('Token verification failed:', error);
    return false;
  }
}

async function saveToken() {
  const token = apiTokenInput.value.trim();

  if (!token) {
    showError('Please enter your API token');
    return;
  }

  saveTokenBtn.disabled = true;
  saveTokenBtn.textContent = 'Connecting...';

  try {
    const isValid = await verifyToken(token);

    if (isValid) {
      await chrome.storage.local.set({ apiToken: token });
      showScanSection();
      await loadCurrentTab();
      await loadRecentScans();
      hideError();
    } else {
      showError('Invalid API token. Please check and try again.');
    }
  } catch (error) {
    showError('Connection failed. Please try again.');
  } finally {
    saveTokenBtn.disabled = false;
    saveTokenBtn.textContent = 'Connect Account';
  }
}

async function disconnect() {
  await chrome.storage.local.remove(['apiToken', 'recentScans']);
  apiTokenInput.value = '';
  showLoginSection();
}

async function loadCurrentTab() {
  try {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    if (tab && tab.url) {
      // Only show scannable URLs
      if (tab.url.startsWith('http://') || tab.url.startsWith('https://')) {
        urlDisplay.textContent = truncateUrl(tab.url, 45);
        urlDisplay.title = tab.url;
        scanBtn.disabled = false;
      } else {
        urlDisplay.textContent = 'Cannot scan this page';
        scanBtn.disabled = true;
      }
    }
  } catch (error) {
    console.error('Failed to get current tab:', error);
  }
}

function truncateUrl(url, maxLength) {
  if (url.length <= maxLength) return url;
  return url.substring(0, maxLength - 3) + '...';
}

async function scanCurrentPage() {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

  if (!tab || !tab.url) {
    showError('Cannot get current page URL');
    return;
  }

  const url = tab.url;

  // Validate URL
  if (!url.startsWith('http://') && !url.startsWith('https://')) {
    showError('Can only scan http:// or https:// URLs');
    return;
  }

  // Show loading
  scanBtn.style.display = 'none';
  loadingEl.style.display = 'flex';
  resultEl.style.display = 'none';
  hideError();

  try {
    const { apiToken } = await chrome.storage.local.get('apiToken');

    // Start the scan
    const response = await fetch(`${API_BASE}/api/extension/scan`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${apiToken}`,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ url }),
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || data.error || 'Scan failed');
    }

    // Poll for completion
    if (data.uuid) {
      await pollForResults(data.uuid, apiToken);
    } else {
      throw new Error('No scan ID returned');
    }
  } catch (error) {
    console.error('Scan failed:', error);
    showError(error.message || 'Scan failed. Please try again.');
    loadingEl.style.display = 'none';
    scanBtn.style.display = 'flex';
  }
}

async function pollForResults(uuid, token, attempts = 0) {
  const maxAttempts = 60; // 60 seconds max

  if (attempts >= maxAttempts) {
    showError('Scan is taking longer than expected. Check the dashboard for results.');
    loadingEl.style.display = 'none';
    scanBtn.style.display = 'flex';
    return;
  }

  try {
    const response = await fetch(`${API_BASE}/api/extension/scan/${uuid}/status`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    });

    const data = await response.json();

    if (data.status === 'completed') {
      showResult(data);
      await addToRecentScans(data);
      loadingEl.style.display = 'none';
      scanBtn.style.display = 'flex';
    } else if (data.status === 'failed') {
      showError(data.error || 'Scan failed');
      loadingEl.style.display = 'none';
      scanBtn.style.display = 'flex';
    } else {
      // Still processing, poll again
      setTimeout(() => pollForResults(uuid, token, attempts + 1), 1000);
    }
  } catch (error) {
    console.error('Status check failed:', error);
    setTimeout(() => pollForResults(uuid, token, attempts + 1), 1000);
  }
}

function showResult(scan) {
  resultEl.style.display = 'block';

  const gradeClass = `grade-${(scan.grade || 'f').toLowerCase()}`;

  resultContent.innerHTML = `
    <div class="result-success">
      <div class="result-score">${scan.score?.toFixed(1) || '0'}</div>
      <div class="result-grade ${gradeClass}">${scan.grade || '?'}</div>
      <a href="${API_BASE}/scans/${scan.uuid}" class="result-link" target="_blank">
        View Full Report &rarr;
      </a>
    </div>
  `;
}

async function addToRecentScans(scan) {
  const { recentScans = [] } = await chrome.storage.local.get('recentScans');

  // Add to beginning, remove duplicates, keep last 5
  const newRecent = [
    { uuid: scan.uuid, url: scan.url, score: scan.score, grade: scan.grade },
    ...recentScans.filter(s => s.uuid !== scan.uuid),
  ].slice(0, 5);

  await chrome.storage.local.set({ recentScans: newRecent });
  await loadRecentScans();
}

async function loadRecentScans() {
  const { recentScans = [] } = await chrome.storage.local.get('recentScans');

  if (recentScans.length === 0) {
    recentList.innerHTML = '<div class="recent-empty">No recent scans</div>';
    return;
  }

  recentList.innerHTML = recentScans.map(scan => `
    <a href="${API_BASE}/scans/${scan.uuid}" class="recent-item" target="_blank">
      <span class="recent-item-url" title="${scan.url}">${truncateUrl(scan.url, 35)}</span>
      <span class="recent-item-score">${scan.score?.toFixed(1) || '?'}</span>
    </a>
  `).join('');
}
