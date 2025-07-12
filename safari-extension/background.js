// Twitter Likes Capture - Background Script
// Handles extension lifecycle and communication with content scripts

console.log('Twitter Likes Capture background script loaded');

// Extension configuration
const CONFIG = {
  API_BASE_URL: 'http://localhost:8000/api',
  EXTENSION_VERSION: '1.0.0'
};

// Handle extension installation
chrome.runtime.onInstalled.addListener((details) => {
  console.log('Extension installed/updated:', details.reason);
  
  if (details.reason === 'install') {
    // Set default settings
    chrome.storage.local.set({
      extensionEnabled: true,
      apiUrl: CONFIG.API_BASE_URL,
      captureScreenshots: true,
      showNotifications: true,
      lastSync: null
    });
    
    // Test backend connection
    testBackendConnection();
  }
});

// Test connection to Laravel backend
async function testBackendConnection() {
  try {
    const response = await fetch(`${CONFIG.API_BASE_URL}/posts/status`);
    if (response.ok) {
      console.log('✅ Backend connection successful');
      setBadgeText('✓', '#10B981');
    } else {
      console.log('❌ Backend returned error:', response.status);
      setBadgeText('!', '#EF4444');
    }
  } catch (error) {
    console.log('❌ Backend connection failed:', error);
    setBadgeText('X', '#EF4444');
  }
}

// Set extension badge
function setBadgeText(text, color) {
  if (chrome.action) {
    chrome.action.setBadgeText({ text });
    chrome.action.setBadgeBackgroundColor({ color });
  }
}

// Handle messages from content script
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  console.log('Background received message:', message);
  
  switch (message.type) {
    case 'TWEET_LIKED':
      handleTweetLiked(message.data);
      break;
    case 'TWEET_UNLIKED':
      handleTweetUnliked(message.data);
      break;
    case 'GET_STATUS':
      getExtensionStatus().then(sendResponse);
      return true; // Indicates async response
    case 'TEST_CONNECTION':
      testBackendConnection().then(() => sendResponse({ success: true }));
      return true;
  }
});

// Handle tweet liked event
async function handleTweetLiked(tweetData) {
  try {
    console.log('Processing liked tweet:', tweetData.tweet_id);
    
    // Get current settings
    const settings = await chrome.storage.local.get([
      'extensionEnabled',
      'apiUrl',
      'captureScreenshots'
    ]);
    
    if (!settings.extensionEnabled) {
      console.log('Extension disabled, skipping tweet');
      return;
    }
    
    // Update API URL if changed
    const apiUrl = settings.apiUrl || CONFIG.API_BASE_URL;
    
    // Send to Laravel backend
    const response = await fetch(`${apiUrl}/posts/capture`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        ...tweetData,
        extension_version: CONFIG.EXTENSION_VERSION
      })
    });
    
    if (response.ok) {
      console.log('✅ Tweet captured successfully');
      updateStats('captured');
    } else {
      console.log('❌ Failed to capture tweet:', response.status);
      updateStats('errors');
    }
    
  } catch (error) {
    console.error('Error processing liked tweet:', error);
    updateStats('errors');
  }
}

// Handle tweet unliked event
async function handleTweetUnliked(tweetData) {
  try {
    console.log('Processing unliked tweet:', tweetData.tweet_id);
    
    const settings = await chrome.storage.local.get(['apiUrl']);
    const apiUrl = settings.apiUrl || CONFIG.API_BASE_URL;
    
    const response = await fetch(`${apiUrl}/posts/unlike`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        tweet_id: tweetData.tweet_id
      })
    });
    
    if (response.ok) {
      console.log('✅ Tweet removed successfully');
      updateStats('removed');
    } else {
      console.log('❌ Failed to remove tweet:', response.status);
    }
    
  } catch (error) {
    console.error('Error processing unliked tweet:', error);
  }
}

// Update extension statistics
async function updateStats(action) {
  const stats = await chrome.storage.local.get([
    'totalCaptured',
    'totalErrors',
    'totalRemoved',
    'lastActivity'
  ]);
  
  const updates = {
    lastActivity: new Date().toISOString()
  };
  
  switch (action) {
    case 'captured':
      updates.totalCaptured = (stats.totalCaptured || 0) + 1;
      break;
    case 'errors':
      updates.totalErrors = (stats.totalErrors || 0) + 1;
      break;
    case 'removed':
      updates.totalRemoved = (stats.totalRemoved || 0) + 1;
      break;
  }
  
  await chrome.storage.local.set(updates);
}

// Get extension status for popup
async function getExtensionStatus() {
  const [settings, stats] = await Promise.all([
    chrome.storage.local.get([
      'extensionEnabled',
      'apiUrl',
      'captureScreenshots',
      'showNotifications'
    ]),
    chrome.storage.local.get([
      'totalCaptured',
      'totalErrors', 
      'totalRemoved',
      'lastActivity'
    ])
  ]);
  
  // Test current backend status
  let backendStatus = 'unknown';
  try {
    const response = await fetch(`${settings.apiUrl || CONFIG.API_BASE_URL}/posts/status`);
    backendStatus = response.ok ? 'connected' : 'error';
  } catch (error) {
    backendStatus = 'disconnected';
  }
  
  return {
    settings,
    stats: {
      totalCaptured: stats.totalCaptured || 0,
      totalErrors: stats.totalErrors || 0,
      totalRemoved: stats.totalRemoved || 0,
      lastActivity: stats.lastActivity
    },
    backendStatus,
    version: CONFIG.EXTENSION_VERSION
  };
}

// Handle tab updates to inject content script if needed
chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
  if (changeInfo.status === 'complete' && 
      (tab.url?.includes('x.com') || tab.url?.includes('twitter.com'))) {
    
    // Test connection when user visits Twitter/X
    setTimeout(testBackendConnection, 1000);
  }
});

// Periodic health check
setInterval(testBackendConnection, 5 * 60 * 1000); // Every 5 minutes

// Initialize
testBackendConnection();