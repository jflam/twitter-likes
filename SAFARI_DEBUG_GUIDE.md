# Safari Extension Debugging Guide

## Quick Setup

1. **Enable Developer Mode**
   - Safari → Settings → Advanced → ✅ "Show features for web developers"

2. **Open Web Inspector**
   - Navigate to x.com
   - Develop → x.com → Twitter Likes Capture

## Recommended Breakpoints

### In `debug-like-detection.js`:
```javascript
// Line 36 - Catches ALL clicks
document.addEventListener('click', function(e) {
    clickCount++; // 🔴 SET BREAKPOINT HERE
```

```javascript
// Line 53 - When like/unlike button detected
if (likeButton || unlikeButton) {
    console.group('🎯 LIKE/UNLIKE BUTTON CLICK DETECTED'); // 🔴 SET BREAKPOINT HERE
```

### In `content.js`:
```javascript
// Line 367 - Main click handler
if (likeButton) {
    debugLog('Like action detected - capturing tweet data'); // 🔴 SET BREAKPOINT HERE
```

```javascript
// Line 373 - Tweet data extraction
const tweetData = extractTweetData(likeButton); // 🔴 SET BREAKPOINT HERE
```

```javascript
// Line 230 - Before API call
logRequest(requestUrl, requestOptions, 'CAPTURE POST'); // 🔴 SET BREAKPOINT HERE
```

## Debugging Workflow

### 1. **Initial Check - Console**
```javascript
// Run these in console to verify extension loaded:
testLikeDetection()  // Should show button count
TwitterLikesDebug.showConfig()  // Should show configuration
```

### 2. **Step-by-Step Click Debugging**
1. Set breakpoint at line 36 in `debug-like-detection.js`
2. Click any element on x.com
3. When breakpoint hits, inspect:
   - `e.target` - What was clicked
   - `e.target.getAttribute('data-testid')` - Check for testid
   - `e.target.closest('[data-testid="like"]')` - Check if it finds like button

### 3. **Variables to Watch**
In debugger, add these to Watch Expressions:
- `e.target`
- `likeButton`
- `unlikeButton`
- `tweetData`
- `window.location.href`

### 4. **Common Issues to Check**

#### Extension Not Loading:
```javascript
// In console, check if these exist:
typeof testLikeDetection  // Should be "function"
typeof TwitterLikesDebug  // Should be "object"
```

#### Selectors Not Working:
```javascript
// In console, manually test selectors:
document.querySelectorAll('[data-testid="like"]').length
document.querySelectorAll('[data-testid="tweet"]').length
```

#### Events Not Firing:
```javascript
// Add this to console to test:
document.addEventListener('click', e => console.log('TEST CLICK:', e.target), true);
```

## Advanced Debugging

### 1. **Network Inspection**
- In Web Inspector → Network tab
- Look for requests to `twitter-likes.test`
- Check for CORS errors

### 2. **Extension Errors**
- Develop → Web Extension Background Content → Twitter Likes Capture
- Check Console for background script errors

### 3. **Conditional Breakpoints**
Right-click a breakpoint and add condition:
```javascript
// Only break when clicking a like button
e.target.closest('[data-testid="like"]') !== null
```

### 4. **Log Points**
Instead of breakpoints, right-click line number → "Add Log Point":
- Logs without stopping execution
- Good for high-frequency events

## Quick Debug Commands

```javascript
// Check what's on the page
document.querySelectorAll('[data-testid]').forEach(el => 
  console.log(el.getAttribute('data-testid'), el)
);

// Monitor all clicks
monitorEvents(document.body, 'click');

// Stop monitoring
unmonitorEvents(document.body, 'click');

// Check extension state
chrome.runtime.getManifest()
```

## Troubleshooting

**"Extension not appearing in Develop menu"**
- Disable/re-enable extension in Safari Settings
- Restart Safari

**"Breakpoints not hitting"**
- Ensure you're in the right context (content vs background)
- Check that the file is actually loaded
- Try setting breakpoint after page fully loads

**"Can't see console logs"**
- Check you're in the right inspector (page vs extension)
- Verify console log level isn't filtering messages