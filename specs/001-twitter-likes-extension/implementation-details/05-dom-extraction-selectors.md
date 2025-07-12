# DOM Extraction Selectors

## Essential Selectors for Implementation

### Tweet Container
```javascript
const tweetContainer = likeButton.closest('[data-testid="tweet"]');
```

### Author Information
```javascript
// Display name
const displayName = tweetContainer.querySelector('[data-testid="User-Name"] span')?.textContent;

// Username (remove @ and leading slash)
const usernameLink = tweetContainer.querySelector('[data-testid="User-Name"] a[href^="/"]');
const username = usernameLink?.getAttribute('href')?.substring(1);

// Avatar URL
const avatarImg = tweetContainer.querySelector('[data-testid="Tweet-User-Avatar"] img');
const avatarUrl = avatarImg?.getAttribute('src');

// Verified badge
const isVerified = !!tweetContainer.querySelector('[data-testid="icon-verified"]');
```

### Tweet Content
```javascript
// Tweet text
const contentText = tweetContainer.querySelector('[data-testid="tweetText"]')?.textContent;

// Tweet URL and ID  
const timeElement = tweetContainer.querySelector('time[datetime]');
const tweetUrl = timeElement?.closest('a')?.getAttribute('href');
const tweetId = tweetUrl?.match(/\/status\/(\d+)/)?.[1];

// Timestamp
const timestamp = timeElement?.getAttribute('datetime');
```

### Engagement Metrics
```javascript
// Extract counts from buttons
const getCount = (testId) => {
  const button = tweetContainer.querySelector(`[data-testid="${testId}"]`);
  const countText = button?.querySelector('span:last-child span')?.textContent;
  return countText ? parseInt(countText.replace(/[^\d]/g, '')) || 0 : 0;
};

const replyCount = getCount('reply');
const retweetCount = getCount('retweet');  
const likeCount = getCount('like') || getCount('unlike');
const bookmarkCount = getCount('bookmark');

// View count (special handling)
const viewsText = Array.from(tweetContainer.querySelectorAll('span'))
  .find(span => span.textContent.includes('Views'))?.previousElementSibling?.textContent;
const viewCount = viewsText ? parseInt(viewsText.replace(/[^\d]/g, '')) || 0 : 0;
```

## Complete Extraction Function

```javascript
function extractTweetData(likeButton) {
  const tweetContainer = likeButton.closest('[data-testid="tweet"]');
  if (!tweetContainer) return null;

  // Helper function for engagement counts
  const getCount = (testId) => {
    const button = tweetContainer.querySelector(`[data-testid="${testId}"]`);
    const countText = button?.querySelector('span:last-child span')?.textContent;
    return countText ? parseInt(countText.replace(/[^\d]/g, '')) || 0 : 0;
  };

  // Extract all required data
  const displayName = tweetContainer.querySelector('[data-testid="User-Name"] span')?.textContent;
  const usernameLink = tweetContainer.querySelector('[data-testid="User-Name"] a[href^="/"]');
  const username = usernameLink?.getAttribute('href')?.substring(1);
  const avatarImg = tweetContainer.querySelector('[data-testid="Tweet-User-Avatar"] img');
  const contentText = tweetContainer.querySelector('[data-testid="tweetText"]')?.textContent;
  const timeElement = tweetContainer.querySelector('time[datetime]');
  const tweetUrl = timeElement?.closest('a')?.getAttribute('href');
  const tweetId = tweetUrl?.match(/\/status\/(\d+)/)?.[1];

  return {
    // Required fields
    tweet_id: tweetId,
    author_username: username,
    author_display_name: displayName,
    author_avatar_url: avatarImg?.getAttribute('src'),
    content_text: contentText,
    content_html: tweetContainer.querySelector('[data-testid="tweetText"]')?.innerHTML,
    post_url: tweetUrl ? `https://x.com${tweetUrl}` : null,
    posted_at: timeElement?.getAttribute('datetime'),
    liked_at: new Date().toISOString(),
    
    // Engagement metrics
    reply_count: getCount('reply'),
    retweet_count: getCount('retweet'),
    like_count: getCount('like') || getCount('unlike'),
    bookmark_count: getCount('bookmark'),
    
    // Post type detection
    post_type: detectPostType(tweetContainer),
    is_verified: !!tweetContainer.querySelector('[data-testid="icon-verified"]')
  };
}

function detectPostType(tweetContainer) {
  // Simple post type detection logic
  const tweetText = tweetContainer.querySelector('[data-testid="tweetText"]');
  if (!tweetText) return 'unknown';
  
  // Check for quote tweet structure (contains embedded tweet)
  if (tweetContainer.querySelectorAll('[data-testid="tweetText"]').length > 1) {
    return 'quote_tweet';
  }
  
  // Check for reply indicators
  if (tweetContainer.textContent.includes('Replying to')) {
    return 'reply';
  }
  
  return 'original';
}
```

## Error Handling
```javascript
// Validation before sending to API
function validateTweetData(data) {
  const required = ['tweet_id', 'author_username', 'content_text'];
  return required.every(field => data[field] && data[field].trim().length > 0);
}
```

## Usage in Content Script
```javascript
document.addEventListener('click', function(e) {
  const likeButton = e.target.closest('[data-testid="like"]');
  if (likeButton) {
    const tweetData = extractTweetData(likeButton);
    if (validateTweetData(tweetData)) {
      // Send to Laravel API
      sendToLaravelAPI(tweetData);
    }
  }
});
```