// Twitter Likes Capture - Popup Script
// Handles popup UI interactions and communication with background script

document.addEventListener('DOMContentLoaded', async () => {
  console.log('Popup loaded');
  
  // Initialize popup
  await loadExtensionStatus();
  setupEventListeners();
});

// Load current extension status and settings
async function loadExtensionStatus() {
  try {
    const status = await chrome.runtime.sendMessage({ type: 'GET_STATUS' });
    updateUI(status);
  } catch (error) {
    console.error('Failed to load extension status:', error);
    showError('Failed to load extension status');
  }
}

// Update UI with current status
function updateUI(status) {
  const { settings, stats, backendStatus, version } = status;
  
  // Update status indicator
  updateStatusIndicator(backendStatus);
  
  // Update statistics
  document.getElementById('capturedCount').textContent = stats.totalCaptured;
  document.getElementById('removedCount').textContent = stats.totalRemoved;
  document.getElementById('errorCount').textContent = stats.totalErrors;
  
  // Update settings controls
  document.getElementById('extensionEnabled').checked = settings.extensionEnabled ?? true;
  document.getElementById('captureScreenshots').checked = settings.captureScreenshots ?? true;
  document.getElementById('showNotifications').checked = settings.showNotifications ?? true;
  document.getElementById('apiUrl').value = settings.apiUrl || 'http://localhost:8000/api';
  
  // Update footer
  document.getElementById('version').textContent = version;
  if (stats.lastActivity) {
    const lastActivity = new Date(stats.lastActivity);
    document.getElementById('lastActivity').textContent = formatRelativeTime(lastActivity);
  }
}

// Update status indicator
function updateStatusIndicator(status) {
  const dot = document.getElementById('statusDot');
  const text = document.getElementById('statusText');
  
  dot.className = 'status-dot';
  
  switch (status) {
    case 'connected':
      dot.classList.add('success');
      text.textContent = 'Connected';
      break;
    case 'error':
      dot.classList.add('error');
      text.textContent = 'Backend Error';
      break;
    case 'disconnected':
      dot.classList.add('error');
      text.textContent = 'Disconnected';
      break;
    default:
      dot.classList.add('warning');
      text.textContent = 'Unknown';
  }
}

// Setup event listeners
function setupEventListeners() {
  // Settings toggles
  document.getElementById('extensionEnabled').addEventListener('change', (e) => {
    saveSetting('extensionEnabled', e.target.checked);
  });
  
  document.getElementById('captureScreenshots').addEventListener('change', (e) => {
    saveSetting('captureScreenshots', e.target.checked);
  });
  
  document.getElementById('showNotifications').addEventListener('change', (e) => {
    saveSetting('showNotifications', e.target.checked);
  });
  
  // API URL input
  document.getElementById('apiUrl').addEventListener('blur', (e) => {
    saveSetting('apiUrl', e.target.value);
  });
  
  // Buttons
  document.getElementById('testConnection').addEventListener('click', testConnection);
  document.getElementById('openDashboard').addEventListener('click', openDashboard);
  document.getElementById('exportData').addEventListener('click', exportData);
}

// Save setting to storage
async function saveSetting(key, value) {
  try {
    await chrome.storage.local.set({ [key]: value });
    showSuccess(`${key} updated`);
  } catch (error) {
    console.error('Failed to save setting:', error);
    showError('Failed to save setting');
  }
}

// Test connection to backend
async function testConnection() {
  const button = document.getElementById('testConnection');
  const originalText = button.textContent;
  
  button.textContent = 'Testing...';
  button.disabled = true;
  
  try {
    const result = await chrome.runtime.sendMessage({ type: 'TEST_CONNECTION' });
    if (result.success) {
      showSuccess('Connection successful!');
      await loadExtensionStatus(); // Refresh status
    } else {
      showError('Connection failed');
    }
  } catch (error) {
    showError('Connection test failed');
  } finally {
    button.textContent = originalText;
    button.disabled = false;
  }
}

// Open Laravel dashboard
async function openDashboard() {
  const settings = await chrome.storage.local.get(['apiUrl']);
  const apiUrl = settings.apiUrl || 'http://localhost:8000/api';
  const dashboardUrl = apiUrl.replace('/api', '');
  
  chrome.tabs.create({ url: dashboardUrl });
}

// Export data
async function exportData() {
  const button = document.getElementById('exportData');
  const originalText = button.textContent;
  
  button.textContent = 'Exporting...';
  button.disabled = true;
  
  try {
    const settings = await chrome.storage.local.get(['apiUrl']);
    const apiUrl = settings.apiUrl || 'http://localhost:8000/api';
    
    // Open export URL in new tab
    const exportUrl = `${apiUrl.replace('/api', '')}/export`;
    chrome.tabs.create({ url: exportUrl });
    
    showSuccess('Export page opened');
  } catch (error) {
    showError('Failed to open export page');
  } finally {
    button.textContent = originalText;
    button.disabled = false;
  }
}

// Show success message
function showSuccess(message) {
  showNotification(message, 'success');
}

// Show error message
function showError(message) {
  showNotification(message, 'error');
}

// Show notification
function showNotification(message, type = 'info') {
  // Create a simple notification system within the popup
  const notification = document.createElement('div');
  notification.className = `notification notification-${type}`;
  notification.textContent = message;
  notification.style.cssText = `
    position: fixed;
    top: 10px;
    left: 10px;
    right: 10px;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    text-align: center;
    z-index: 1000;
    transition: all 0.3s ease;
  `;
  
  // Set colors based on type
  const colors = {
    success: { bg: '#10B981', text: 'white' },
    error: { bg: '#EF4444', text: 'white' },
    info: { bg: '#3B82F6', text: 'white' }
  };
  
  const color = colors[type] || colors.info;
  notification.style.backgroundColor = color.bg;
  notification.style.color = color.text;
  
  document.body.appendChild(notification);
  
  // Remove after 3 seconds
  setTimeout(() => {
    notification.style.opacity = '0';
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 300);
  }, 3000);
}

// Format relative time
function formatRelativeTime(date) {
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);
  
  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;
  
  return date.toLocaleDateString();
}

// Auto-refresh status every 30 seconds
setInterval(loadExtensionStatus, 30000);