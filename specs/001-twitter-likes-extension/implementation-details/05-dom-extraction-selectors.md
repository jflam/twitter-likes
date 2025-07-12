# DOM Extraction Selectors for Twitter/X Content

## Overview
This document provides the exact DOM selectors needed to extract tweet content, author information, and metadata from X.com's current HTML structure (validated 2025-07-12).

## Primary Tweet Container
**Main Container**: `[data-testid="tweet"]`
```html
<article data-testid="tweet" role="article">
  <!-- All tweet content is contained within this element -->
</article>
```

## Author Information Extraction

### Display Name
**Selector**: `[data-testid="User-Name"] span`
**HTML Pattern**:
```html
<div data-testid="User-Name">
  <div class="css-175oi2r r-1awozwy r-18u37iz r-1wbh5a2 r-dnmrzs">
    <div class="css-175oi2r r-1wbh5a2 r-dnmrzs">
      <a href="/dhh">
        <span>DHH</span> <!-- Display Name -->
      </a>
    </div>
  </div>
</div>
```

### Username (@handle)
**Selector**: `[data-testid="User-Name"] a[href^="/"]`
**Extract**: `href` attribute (remove leading slash)
**HTML Pattern**:
```html
<a href="/dhh">  <!-- Username: dhh -->
  <span>@dhh</span>
</a>
```

### Avatar URL
**Selector**: `[data-testid="Tweet-User-Avatar"] img`
**Extract**: `src` attribute
**HTML Pattern**:
```html
<div data-testid="Tweet-User-Avatar">
  <img src="https://pbs.twimg.com/profile_images/1746980162607140864/fG9Fj4K__x96.jpg" />
</div>
```

### Verified Badge Detection
**Selector**: `[data-testid="icon-verified"]`
**Detection**: Element exists = verified account
**HTML Pattern**:
```html
<svg data-testid="icon-verified" aria-label="Verified account">
  <!-- Verification checkmark -->
</svg>
```

## Tweet Content Extraction

### Main Tweet Text
**Selector**: `[data-testid="tweetText"]`
**Extract**: `textContent` for plain text, `innerHTML` for formatted
**HTML Pattern**:
```html
<div data-testid="tweetText">
  <span>We gotta do OmacomCon at some point. Leveling up on Linux, ricing, bash, and Arch is such a fun journey that it should be shared with friends!</span>
</div>
```

### Tweet URL and ID
**Selector**: `[data-testid="tweetText"] ~ * time[datetime]`
**Extract**: Closest `<a>` element's `href` attribute
**HTML Pattern**:
```html
<a href="/dhh/status/1944066613197910157">
  <time datetime="2025-07-12T16:09:34.000Z">12:09 PM · Jul 12, 2025</time>
</a>
```
**Tweet ID Extraction**: Extract from URL pattern `/status/([0-9]+)`

### Timestamp
**Selector**: `time[datetime]`
**Extract**: `datetime` attribute (ISO format)
**HTML Pattern**:
```html
<time datetime="2025-07-12T16:09:34.000Z">
  12:09 PM · Jul 12, 2025
</time>
```

## Engagement Metrics Extraction

### Reply Count
**Selector**: `[data-testid="reply"] span:last-child span`
**HTML Pattern**:
```html
<button data-testid="reply" aria-label="12 Replies. Reply">
  <span>12</span> <!-- Reply count -->
</button>
```

### Retweet Count  
**Selector**: `[data-testid="retweet"] span:last-child span`
**HTML Pattern**:
```html
<button data-testid="retweet" aria-label="9 reposts. Repost">
  <span>9</span> <!-- Retweet count -->
</button>
```

### Like Count
**Selector**: `[data-testid="like"] span:last-child span`
**HTML Pattern**:
```html
<button data-testid="like" aria-label="229 Likes. Like">
  <span>229</span> <!-- Like count -->
</button>
```

### View Count
**Selector**: Look for "Views" text and extract adjacent number
**HTML Pattern**:
```html
<span>14.3K</span> <span>Views</span>
```

### Bookmark Count
**Selector**: `[data-testid="bookmark"] span:last-child span`
**HTML Pattern**:
```html
<button data-testid="bookmark" aria-label="7 Bookmarks. Bookmark">
  <span>7</span> <!-- Bookmark count -->
</button>
```

## Like Button State Detection

### Unliked State (Target for Detection)
**Selector**: `[data-testid="like"]`
**Aria-label**: Contains "Like" (not "Liked")
**HTML Pattern**:
```html
<button data-testid="like" aria-label="229 Likes. Like">
  <!-- Hollow heart SVG -->
</button>
```

### Liked State (After Click)
**Selector**: `[data-testid="unlike"]`
**Aria-label**: Contains "Liked"
**HTML Pattern**:
```html
<button data-testid="unlike" aria-label="230 Likes. Liked">
  <!-- Filled heart SVG -->
</button>
```

## Post Type Detection

### Original Tweet
**Detection**: No retweet indicators, no quote tweet structure
**Pattern**: Direct content in `[data-testid="tweetText"]`

### Reply Tweet
**Detection**: Look for reply context or thread indicators
**Selector**: Check for thread structure above tweet content

### Retweet
**Detection**: Look for "retweeted" or retweet attribution
**Pattern**: Author info differs from retweeter info

### Quote Tweet
**Detection**: Contains embedded tweet structure
**Pattern**: Multiple `[data-testid="tweetText"]` elements

## Thread Detection

### Thread Indicators
**Selector**: Multiple `[data-testid="tweet"]` elements in sequence
**Pattern**: URL contains `/status/` indicating thread view
**Detection**: `window.location.href.includes('/status/')`

### Thread Position
**Method**: Count position within thread container
**Pattern**: Analyze DOM order of tweet containers

## Media Content Detection

### Images
**Selector**: `[data-testid="tweetPhoto"]` or `img` within tweet container
**Extract**: `src` attributes for image URLs

### Videos
**Selector**: `[data-testid="videoComponent"]` or `video` elements
**Extract**: Video metadata and thumbnail URLs

### Links
**Selector**: `a[href]` within `[data-testid="tweetText"]`
**Extract**: `href` attributes for external links

## Extraction Function Structure

```javascript
function extractTweetData(tweetElement) {
  const tweetContainer = tweetElement.closest('[data-testid="tweet"]');
  
  return {
    // Author Info
    author_username: extractUsername(tweetContainer),
    author_display_name: extractDisplayName(tweetContainer),
    author_avatar_url: extractAvatarUrl(tweetContainer),
    is_verified: hasVerifiedBadge(tweetContainer),
    
    // Content
    content_text: extractTweetText(tweetContainer),
    content_html: extractTweetHTML(tweetContainer),
    
    // Metadata
    tweet_id: extractTweetId(tweetContainer),
    post_url: extractPostUrl(tweetContainer),
    posted_at: extractTimestamp(tweetContainer),
    
    // Engagement
    reply_count: extractReplyCount(tweetContainer),
    retweet_count: extractRetweetCount(tweetContainer),
    like_count: extractLikeCount(tweetContainer),
    view_count: extractViewCount(tweetContainer),
    bookmark_count: extractBookmarkCount(tweetContainer),
    
    // Type Classification
    post_type: classifyPostType(tweetContainer),
    is_thread_post: isPartOfThread(tweetContainer),
    
    // Media
    media_urls: extractMediaUrls(tweetContainer),
    external_links: extractExternalLinks(tweetContainer)
  };
}
```

## Error Handling & Fallbacks

### Missing Elements
- **Strategy**: Return `null` or default values for missing data
- **Required Fields**: Author username, tweet text, timestamp
- **Optional Fields**: Engagement counts, media, verification status

### Selector Changes
- **Primary Strategy**: Use `data-testid` attributes (most stable)
- **Fallback Strategy**: Use `aria-label` attributes and text content
- **Last Resort**: CSS class patterns (least stable)

### Data Validation
- **Tweet ID**: Must be numeric string
- **Timestamps**: Must be valid ISO datetime
- **URLs**: Must be valid HTTP/HTTPS URLs
- **Counts**: Must be non-negative integers

## Testing Validation

The selectors documented above have been validated against:
- ✅ Regular tweets
- ✅ Verified account tweets  
- ✅ Tweets with engagement metrics
- ✅ Current X.com DOM structure (2025-07-12)

## Notes

- **Stability**: `data-testid` attributes are most reliable for long-term stability
- **Performance**: Cache DOM queries within extraction function
- **Updates**: Monitor for X.com DOM changes that may break selectors
- **Robustness**: Implement multiple fallback strategies for critical data