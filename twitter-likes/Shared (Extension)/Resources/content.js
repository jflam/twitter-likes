// Twitter Likes Capture - Content Script
// Detects like/unlike actions and extracts tweet data

console.log('🚀 TWITTER LIKES CAPTURE - CONTENT.JS LOADED');
console.log('📌 VERSION: 1.0.2');
console.log('📅 LOADED AT:', new Date().toISOString());
console.log('🔗 API BASE URL: https://twitter-likes.test/api');

// Configuration
const CONFIG = {
  API_BASE_URL: 'https://twitter-likes.test/api', // Use Valet URL if available
  FALLBACK_URL: 'https://localhost:8000/api',      // Fallback for mkcert
  EXTENSION_VERSION: '1.0.2',
  DEBUG: true,
  VERBOSE_LOGGING: true,  // Set to false to disable verbose API logging
  LOG_REQUESTS: true,     // Log outbound API requests
  LOG_RESPONSES: true     // Log API responses
};

// Helper function for logging
function debugLog(message, data = null) {
  if (CONFIG.DEBUG) {
    console.log(`[TwitterLikes] ${message}`, data || '');
  }
}

// Verbose logging functions
function logRequest(url, options, context = '') {
  if (CONFIG.VERBOSE_LOGGING && CONFIG.LOG_REQUESTS) {
    console.group(`🔄 API REQUEST ${context}`);
    console.log('URL:', url);
    console.log('Method:', options.method || 'GET');
    console.log('Headers:', options.headers || {});
    if (options.body) {
      console.log('Body:', options.body);
      try {
        console.log('Parsed Body:', JSON.parse(options.body));
      } catch (e) {
        console.log('Body (raw):', options.body);
      }
    }
    console.log('Timestamp:', new Date().toISOString());
    console.groupEnd();
  }
}

function logResponse(response, data, context = '') {
  if (CONFIG.VERBOSE_LOGGING && CONFIG.LOG_RESPONSES) {
    console.group(`📥 API RESPONSE ${context}`);
    console.log('Status:', response.status, response.statusText);
    console.log('OK:', response.ok);
    console.log('URL:', response.url);
    console.log('Headers:', Object.fromEntries(response.headers.entries()));
    console.log('Response Data:', data);
    console.log('Timestamp:', new Date().toISOString());
    console.groupEnd();
  }
}

function logError(error, context = '') {
  if (CONFIG.VERBOSE_LOGGING) {
    console.group(`❌ API ERROR ${context}`);
    console.error('Error:', error);
    console.log('Error Message:', error.message);
    console.log('Error Stack:', error.stack);
    console.log('Timestamp:', new Date().toISOString());
    console.groupEnd();
  }
}

// Extract comprehensive tweet data from DOM
function extractTweetData(likeButton) {
  const tweetContainer = likeButton.closest('[data-testid="tweet"]');
  if (!tweetContainer) {
    debugLog('No tweet container found');
    return null;
  }

  try {
    // Helper function for engagement counts
    const getCount = (testId) => {
      const button = tweetContainer.querySelector(`[data-testid="${testId}"]`);
      const countText = button?.querySelector('span:last-child span')?.textContent;
      return countText ? parseInt(countText.replace(/[^\d]/g, '')) || 0 : 0;
    };

    // Extract author information
    const displayName = tweetContainer.querySelector('[data-testid="User-Name"] span')?.textContent;
    const usernameLink = tweetContainer.querySelector('[data-testid="User-Name"] a[href^="/"]');
    const username = usernameLink?.getAttribute('href')?.substring(1);
    const avatarImg = tweetContainer.querySelector('[data-testid="Tweet-User-Avatar"] img');

    // Extract content
    const contentText = tweetContainer.querySelector('[data-testid="tweetText"]')?.textContent;
    const contentHtml = tweetContainer.querySelector('[data-testid="tweetText"]')?.innerHTML;

    // Extract URL and timestamp
    const timeElement = tweetContainer.querySelector('time[datetime]');
    const tweetUrl = timeElement?.closest('a')?.getAttribute('href');
    const tweetId = tweetUrl?.match(/\/status\/(\d+)/)?.[1];

    // Validate required fields
    if (!tweetId || !username || !contentText) {
      debugLog('Missing required fields', { tweetId, username, contentText });
      return null;
    }

    const tweetData = {
      // Required fields
      tweet_id: tweetId,
      author_username: username,
      author_display_name: displayName || username,
      author_avatar_url: avatarImg?.getAttribute('src'),
      content_text: contentText,
      content_html: contentHtml,
      post_url: tweetUrl ? `https://x.com${tweetUrl}` : null,
      posted_at: timeElement?.getAttribute('datetime'),
      liked_at: new Date().toISOString(),
      
      // Post type and engagement
      post_type: detectPostType(tweetContainer),
      reply_count: getCount('reply'),
      retweet_count: getCount('retweet'),
      like_count: getCount('like') || getCount('unlike'),
      
      // View count (special handling)
      view_count: extractViewCount(tweetContainer)
    };

    debugLog('Extracted tweet data', tweetData);
    return tweetData;

  } catch (error) {
    debugLog('Error extracting tweet data', error);
    return null;
  }
}

function detectPostType(tweetContainer) {
  try {
    // Check for quote tweet structure (contains embedded tweet)
    if (tweetContainer.querySelectorAll('[data-testid="tweetText"]').length > 1) {
      return 'quote_tweet';
    }
    
    // Check for reply indicators
    if (tweetContainer.textContent.includes('Replying to')) {
      return 'reply';
    }

    // Check for retweet indicators
    if (tweetContainer.querySelector('[data-testid="socialContext"]')?.textContent?.includes('retweeted')) {
      return 'retweet';
    }
    
    return 'original';
  } catch (error) {
    debugLog('Error detecting post type', error);
    return 'original';
  }
}

function extractViewCount(tweetContainer) {
  try {
    const viewsText = Array.from(tweetContainer.querySelectorAll('span'))
      .find(span => span.textContent.includes('Views'))?.previousElementSibling?.textContent;
    return viewsText ? parseInt(viewsText.replace(/[^\d]/g, '')) || 0 : null;
  } catch (error) {
    return null;
  }
}

// Capture screenshot of the tweet
async function captureScreenshot(tweetContainer) {
  try {
    // Use html2canvas if available, otherwise return null
    if (typeof html2canvas !== 'undefined') {
      const canvas = await html2canvas(tweetContainer, {
        useCORS: true,
        allowTaint: true,
        scale: 1,
        width: tweetContainer.offsetWidth,
        height: tweetContainer.offsetHeight
      });
      
      return {
        screenshot_base64: canvas.toDataURL('image/png').split(',')[1],
        screenshot_width: canvas.width,
        screenshot_height: canvas.height
      };
    }
    return {};
  } catch (error) {
    debugLog('Error capturing screenshot', error);
    return {};
  }
}

// Send tweet data to Laravel API with fallback
async function sendToLaravelAPI(tweetData) {
  try {
    // Try message passing first (for Chrome compatibility)
    if (typeof browser !== 'undefined' && browser.runtime) {
      try {
        debugLog('Attempting background script message passing for capture');
        const response = await browser.runtime.sendMessage({
          action: 'capturePost',
          data: tweetData
        });
        if (response && response.success) {
          debugLog('Tweet captured successfully via background script', response.data);
          showNotification('Tweet captured successfully! 🐦', 'success');
          return;
        }
      } catch (e) {
        debugLog('Background script messaging failed, using direct fetch', e);
        logError(e, 'Background Script Message Passing');
      }
    }
    
    // Fallback to direct fetch for Safari
    const apiUrl = CONFIG.API_BASE_URL || CONFIG.FALLBACK_URL;
    const requestUrl = `${apiUrl}/posts/capture`;
    const requestOptions = {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Pragma': 'no-cache'
      },
      body: JSON.stringify(tweetData),
      cache: 'no-store'  // Safari-specific cache bypass
    };

    logRequest(requestUrl, requestOptions, 'CAPTURE POST');
    
    const response = await fetch(requestUrl, requestOptions);
    const result = await response.json();
    
    logResponse(response, result, 'CAPTURE POST');
    
    if (response.ok) {
      debugLog('Tweet captured successfully via direct fetch', result);
      showNotification('Tweet captured successfully! 🐦', 'success');
    } else {
      debugLog('API error', result);
      if (result.error_code === 'DUPLICATE_TWEET') {
        showNotification('Tweet already captured', 'info');
      } else {
        showNotification('Failed to capture tweet', 'error');
      }
    }
  } catch (error) {
    debugLog('Network error', error);
    logError(error, 'CAPTURE POST');
    showNotification('Unable to connect to backend', 'error');
  }
}

// Handle unlike action with fallback
async function handleUnlikeAction(tweetContainer) {
  try {
    const timeElement = tweetContainer.querySelector('time[datetime]');
    const tweetUrl = timeElement?.closest('a')?.getAttribute('href');
    const tweetId = tweetUrl?.match(/\/status\/(\d+)/)?.[1];

    if (!tweetId) {
      debugLog('No tweet ID found for unlike action');
      return;
    }

    debugLog('Processing unlike action', { tweetId });

    // Try message passing first
    if (typeof browser !== 'undefined' && browser.runtime) {
      try {
        debugLog('Attempting background script message passing for unlike');
        const response = await browser.runtime.sendMessage({
          action: 'unlikePost',
          data: { tweet_id: tweetId }
        });
        if (response && response.success) {
          debugLog('Tweet unliked successfully via background script', response.data);
          showNotification('Tweet removed from collection', 'info');
          return;
        }
      } catch (e) {
        debugLog('Background script messaging failed for unlike, using direct fetch', e);
        logError(e, 'Background Script Message Passing - Unlike');
      }
    }

    // Fallback to direct fetch
    const apiUrl = CONFIG.API_BASE_URL || CONFIG.FALLBACK_URL;
    const requestUrl = `${apiUrl}/posts/unlike`;
    const requestOptions = {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Pragma': 'no-cache'
      },
      body: JSON.stringify({ tweet_id: tweetId }),
      cache: 'no-store'  // Safari-specific cache bypass
    };

    logRequest(requestUrl, requestOptions, 'UNLIKE POST');

    const response = await fetch(requestUrl, requestOptions);
    const result = await response.json();
    
    logResponse(response, result, 'UNLIKE POST');
    
    if (response.ok) {
      debugLog('Tweet unliked successfully via direct fetch', result);
      showNotification('Tweet removed from collection', 'info');
    } else {
      debugLog('Unlike error', result);
    }
  } catch (error) {
    debugLog('Unlike network error', error);
    logError(error, 'UNLIKE POST');
  }
}

// Show user notification
function showNotification(message, type = 'info') {
  // Create notification element
  const notification = document.createElement('div');
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    padding: 12px 20px;
    border-radius: 8px;
    color: white;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    max-width: 300px;
  `;

  // Set color based on type
  const colors = {
    success: '#10B981',
    error: '#EF4444', 
    info: '#3B82F6'
  };
  notification.style.backgroundColor = colors[type] || colors.info;
  
  notification.textContent = message;
  document.body.appendChild(notification);

  // Remove after 3 seconds
  setTimeout(() => {
    notification.style.opacity = '0';
    notification.style.transform = 'translateY(-10px)';
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 300);
  }, 3000);
}

// Track buttons we've already attached listeners to
const processedButtons = new WeakSet();

// Main handler for like/unlike button clicks
async function handleButtonClick(e) {
  const button = e.currentTarget;
  const isLike = button.getAttribute('data-testid') === 'like';
  const isUnlike = button.getAttribute('data-testid') === 'unlike';
  
  if (isLike) {
    debugLog('Like action detected - capturing tweet data');
    
    // Small delay to allow UI to update
    setTimeout(async () => {
      console.log('🔄 Starting tweet data extraction...');
      const tweetData = extractTweetData(button);
      console.log('🔍 Tweet data extraction result:', tweetData);
      
      if (tweetData) {
        console.log('✅ Tweet data extracted successfully, capturing screenshot...');
        // Add screenshot if possible
        const screenshotData = await captureScreenshot(button.closest('[data-testid="tweet"]'));
        Object.assign(tweetData, screenshotData);
        console.log('📸 Screenshot captured, sending to API...');
        
        await sendToLaravelAPI(tweetData);
      } else {
        console.warn('❌ Failed to extract tweet data from like button');
        console.log('🔍 Debugging - likeButton element:', button);
        console.log('🔍 Debugging - tweet container:', button.closest('[data-testid="tweet"]'));
      }
    }, 100);
    
  } else if (isUnlike) {
    debugLog('Unlike action detected - processing removal');
    
    // Small delay to ensure this is really an unlike
    setTimeout(async () => {
      await handleUnlikeAction(button.closest('[data-testid="tweet"]'));
    }, 100);
  }
}

// Attach listeners directly to like/unlike buttons
function attachButtonListeners() {
  const buttons = document.querySelectorAll('[data-testid="like"], [data-testid="unlike"]');
  let newButtonsCount = 0;
  
  buttons.forEach(button => {
    if (!processedButtons.has(button)) {
      processedButtons.add(button);
      newButtonsCount++;
      
      // Add click listener directly to button (capture phase first)
      button.addEventListener('click', handleButtonClick, true);
    }
  });
  
  if (newButtonsCount > 0) {
    debugLog(`Attached listeners to ${newButtonsCount} new like/unlike buttons`);
  }
}

// Set up MutationObserver to watch for new buttons
const observer = new MutationObserver((mutations) => {
  // Debounce to avoid excessive processing
  clearTimeout(observer.timeout);
  observer.timeout = setTimeout(attachButtonListeners, 100);
});

// Start observing
observer.observe(document.body, {
  childList: true,
  subtree: true
});

// Initial scan
setTimeout(attachButtonListeners, 1000);

// Initialize extension
debugLog('Content script initialized');

// Test connection to backend on load - fallback to direct fetch for Safari
async function testConnection() {
  try {
    // Try to send message to background script first
    if (typeof browser !== 'undefined' && browser.runtime) {
      try {
        debugLog('Testing connection via Safari browser.runtime');
        const response = await browser.runtime.sendMessage({ action: 'testConnection' });
        if (response && response.success) {
          debugLog('Backend connection successful via background script', response.data);
          return;
        }
      } catch (e) {
        debugLog('Safari message passing failed', e);
        logError(e, 'Safari Message Passing - Test Connection');
      }
    } else if (typeof chrome !== 'undefined' && chrome.runtime) {
      try {
        debugLog('Testing connection via Chrome chrome.runtime');
        const response = await chrome.runtime.sendMessage({ action: 'testConnection' });
        if (response && response.success) {
          debugLog('Backend connection successful via background script', response.data);
          return;
        }
      } catch (e) {
        debugLog('Chrome message passing failed', e);
        logError(e, 'Chrome Message Passing - Test Connection');
      }
    }
    
    // Fallback to direct fetch if messaging fails
    debugLog('Message passing failed, trying direct fetch');
    const apiUrl = CONFIG.API_BASE_URL || CONFIG.FALLBACK_URL;
    const requestUrl = `${apiUrl}/posts/status`;
    const requestOptions = {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Pragma': 'no-cache'
      },
      cache: 'no-store'  // Safari-specific cache bypass
    };

    logRequest(requestUrl, requestOptions, 'TEST CONNECTION');
    
    const response = await fetch(requestUrl, requestOptions);
    const data = await response.json();
    
    logResponse(response, data, 'TEST CONNECTION');
    
    debugLog('Backend connection successful via direct fetch', data);
    
  } catch (error) {
    debugLog('Backend connection failed', error);
    logError(error, 'TEST CONNECTION');
  }
}

// Initialize connection test
testConnection();

// CRITICAL TEST: Expose version info globally
window.TWITTER_LIKES_VERSION = {
  content: '2.0',
  timestamp: new Date().toISOString(),
  hasDebugModule: typeof testLikeDetection !== 'undefined'
};
console.log('Extension version info available at window.TWITTER_LIKES_VERSION');

// Debug helpers - expose globally for console access
window.TwitterLikesDebug = {
  toggleVerboseLogging: () => {
    CONFIG.VERBOSE_LOGGING = !CONFIG.VERBOSE_LOGGING;
    console.log(`🔧 Verbose logging ${CONFIG.VERBOSE_LOGGING ? 'ENABLED' : 'DISABLED'}`);
  },
  toggleRequestLogging: () => {
    CONFIG.LOG_REQUESTS = !CONFIG.LOG_REQUESTS;
    console.log(`🔧 Request logging ${CONFIG.LOG_REQUESTS ? 'ENABLED' : 'DISABLED'}`);
  },
  toggleResponseLogging: () => {
    CONFIG.LOG_RESPONSES = !CONFIG.LOG_RESPONSES;
    console.log(`🔧 Response logging ${CONFIG.LOG_RESPONSES ? 'ENABLED' : 'DISABLED'}`);
  },
  showConfig: () => {
    console.log('🔧 Current Configuration:', CONFIG);
  },
  testConnection: () => {
    console.log('🔧 Testing connection...');
    testConnection();
  }
};

console.log('🔧 Debug helpers available at window.TwitterLikesDebug');
console.log('🔧 Use TwitterLikesDebug.toggleVerboseLogging() to toggle verbose logging');
console.log('🔧 Use TwitterLikesDebug.showConfig() to see current configuration');