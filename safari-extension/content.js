// Twitter Likes Capture - Content Script
// Detects like/unlike actions and extracts tweet data

console.log('Twitter Likes Capture extension loaded');

// Configuration
const CONFIG = {
  API_BASE_URL: 'http://localhost:8000/api',
  EXTENSION_VERSION: '1.0.0',
  DEBUG: true
};

// Helper function for logging
function debugLog(message, data = null) {
  if (CONFIG.DEBUG) {
    console.log(`[TwitterLikes] ${message}`, data || '');
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

// Send tweet data to Laravel API
async function sendToLaravelAPI(tweetData) {
  try {
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
      debugLog('Tweet captured successfully', result);
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
    showNotification('Unable to connect to backend', 'error');
  }
}

// Handle unlike action
async function handleUnlikeAction(tweetContainer) {
  try {
    const timeElement = tweetContainer.querySelector('time[datetime]');
    const tweetUrl = timeElement?.closest('a')?.getAttribute('href');
    const tweetId = tweetUrl?.match(/\/status\/(\d+)/)?.[1];

    if (!tweetId) {
      debugLog('No tweet ID found for unlike action');
      return;
    }

    const response = await fetch(`${CONFIG.API_BASE_URL}/posts/unlike`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ tweet_id: tweetId })
    });

    const result = await response.json();
    
    if (response.ok) {
      debugLog('Tweet unliked successfully', result);
      showNotification('Tweet removed from collection', 'info');
    } else {
      debugLog('Unlike error', result);
    }
  } catch (error) {
    debugLog('Unlike network error', error);
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

// Main event listener for like/unlike detection
document.addEventListener('click', async function(e) {
  const likeButton = e.target.closest('[data-testid="like"]');
  const unlikeButton = e.target.closest('[data-testid="unlike"]');
  
  if (likeButton) {
    debugLog('Like action detected');
    
    // Small delay to allow UI to update
    setTimeout(async () => {
      const tweetData = extractTweetData(likeButton);
      if (tweetData) {
        // Add screenshot if possible
        const screenshotData = await captureScreenshot(likeButton.closest('[data-testid="tweet"]'));
        Object.assign(tweetData, screenshotData);
        
        await sendToLaravelAPI(tweetData);
      }
    }, 100);
    
  } else if (unlikeButton) {
    debugLog('Unlike action detected');
    
    // Small delay to ensure this is really an unlike
    setTimeout(async () => {
      await handleUnlikeAction(unlikeButton.closest('[data-testid="tweet"]'));
    }, 100);
  }
});

// Initialize extension
debugLog('Content script initialized');

// Test connection to backend on load
fetch(`${CONFIG.API_BASE_URL}/posts/status`)
  .then(response => response.json())
  .then(data => {
    debugLog('Backend connection successful', data);
  })
  .catch(error => {
    debugLog('Backend connection failed', error);
  });