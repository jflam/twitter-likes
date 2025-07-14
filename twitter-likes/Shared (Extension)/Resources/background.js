// Twitter Likes Capture - Background Script
// Handles extension lifecycle and communication with content scripts

console.log('Twitter Likes Capture background script loaded');

// Extension configuration
const CONFIG = {
  API_BASE_URL: 'https://twitter-likes.test/api',
  EXTENSION_VERSION: '1.0.2'
};

console.log('🚀 BACKGROUND.JS LOADED - VERSION:', CONFIG.EXTENSION_VERSION);
console.log('📅 LOADED AT:', new Date().toISOString());
console.log('🔗 API URL:', CONFIG.API_BASE_URL);

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
  
  switch (message.action) {
    case 'capturePost':
      handleCapturePost(message.data).then(sendResponse);
      return true; // Indicates async response
    case 'unlikePost':
      handleUnlikePost(message.data).then(sendResponse);
      return true; // Indicates async response
    case 'testConnection':
      handleTestConnection().then(sendResponse);
      return true; // Indicates async response
    case 'getStatus':
      getExtensionStatus().then(sendResponse);
      return true; // Indicates async response
  }
});

// Handle capture post request
async function handleCapturePost(tweetData) {
  try {
    console.log('Processing capture request for tweet:', tweetData.tweet_id);
    
    const response = await fetch(`${CONFIG.API_BASE_URL}/posts/capture`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(tweetData)
    });

    const result = await response.json();
    
    if (response.ok) {
      console.log('✅ Tweet captured successfully');
      setBadgeText('✓', '#10B981');
      return { success: true, data: result };
    } else {
      console.log('❌ API error:', result);
      setBadgeText('!', '#EF4444');
      return { success: false, error: result.message, error_code: result.error_code };
    }
  } catch (error) {
    console.log('❌ Network error:', error);
    setBadgeText('X', '#EF4444');
    return { success: false, error: error.message };
  }
}

// Handle unlike post request
async function handleUnlikePost(data) {
  try {
    console.log('Processing unlike request for tweet:', data.tweet_id);
    
    const response = await fetch(`${CONFIG.API_BASE_URL}/posts/unlike`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(data)
    });

    const result = await response.json();
    
    if (response.ok) {
      console.log('✅ Tweet unliked successfully');
      return { success: true, data: result };
    } else {
      console.log('❌ Unlike error:', result);
      return { success: false, error: result.message };
    }
  } catch (error) {
    console.log('❌ Unlike network error:', error);
    return { success: false, error: error.message };
  }
}

// Handle test connection request
async function handleTestConnection() {
  try {
    const response = await fetch(`${CONFIG.API_BASE_URL}/posts/status`);
    const result = await response.json();
    
    if (response.ok) {
      console.log('✅ Backend connection successful');
      setBadgeText('✓', '#10B981');
      return { success: true, data: result };
    } else {
      console.log('❌ Backend returned error:', response.status);
      setBadgeText('!', '#EF4444');
      return { success: false, error: 'Backend error' };
    }
  } catch (error) {
    console.log('❌ Backend connection failed:', error);
    setBadgeText('X', '#EF4444');
    return { success: false, error: error.message };
  }
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