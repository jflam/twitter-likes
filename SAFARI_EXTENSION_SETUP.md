# Safari Extension Setup Guide

## Prerequisites
- macOS 11+ (Big Sur or later)
- Safari 14+ 
- Xcode Command Line Tools installed
- Developer account (for signing, though not required for local testing)

## Step 1: Enable Safari Developer Features

1. **Enable Safari Developer Menu:**
   - Open Safari
   - Go to Safari → Preferences → Advanced
   - Check "Show Develop menu in menu bar"

2. **Enable Extension Development:**
   - In Safari, go to Develop → Allow Unsigned Extensions
   - This enables loading of unsigned/development extensions

## Step 2: Load Extension in Safari Extension Builder

### Method A: Using Safari Extension Builder (Recommended)

1. **Open Safari Extension Builder:**
   ```bash
   # From terminal
   open -a "Safari"
   # Then in Safari: Develop → Show Extension Builder
   ```

2. **Add Your Extension:**
   - Click the "+" button in Extension Builder
   - Navigate to: `/Users/jflam/src/twitter-likes/safari-extension/`
   - Select the entire `safari-extension` folder
   - Click "Select Folder"

3. **Configure Extension:**
   - Extension name: "Twitter Likes Capture"
   - Bundle identifier: `com.yourname.twitter-likes-capture` (will be auto-generated)
   - Click "Create Extension"

### Method B: Using Xcode (Alternative)

1. **Create Safari Extension Project:**
   ```bash
   cd /Users/jflam/src/twitter-likes
   # Open Xcode and create new project → macOS → Safari Extension App
   ```

2. **Replace Generated Files:**
   - Copy files from `safari-extension/` to the generated project's extension folder
   - Update project settings to match our manifest.json

## Step 3: Enable Extension in Safari

1. **Open Safari Preferences:**
   - Safari → Preferences → Extensions
   - Find "Twitter Likes Capture" in the list
   - Enable the extension by checking the box

2. **Grant Permissions:**
   - The extension will request permissions for:
     - `x.com` and `twitter.com` (content script access)
     - `localhost:8000` (API communication)
   - Click "Allow" for all permissions

## Step 4: Verify Extension is Loaded

1. **Check Toolbar:**
   - Look for the extension icon in Safari's toolbar
   - If no icon appears, the extension is still active

2. **Test Extension Detection:**
   - Open Safari's Web Inspector: Develop → Show Web Inspector
   - Navigate to https://x.com or https://twitter.com
   - In Console, type: `console.log('Extension test')`
   - You should see the content script is loaded

3. **Check Extension Popup:**
   - Click the extension icon in the toolbar (if visible)
   - Or right-click on Safari toolbar → Customize Toolbar
   - The popup should open showing extension status

## Step 5: Development Console Access

1. **Content Script Debugging:**
   - On x.com/twitter.com, open Web Inspector
   - Console tab will show content script logs
   - Network tab will show API calls to localhost:8000

2. **Background Script Debugging:**
   - In Safari: Develop → Web Extension Background Pages
   - Select "Twitter Likes Capture"
   - This opens a dedicated console for background.js

3. **Popup Debugging:**
   - Right-click on extension popup
   - Select "Inspect Element"
   - This opens Web Inspector for popup.html

## Step 6: Start Laravel Backend

```bash
cd /Users/jflam/src/twitter-likes/laravel-backend-full
php artisan serve --port=8000
```

## Troubleshooting

### Extension Not Loading
- Check manifest.json syntax with JSON validator
- Verify all referenced files exist
- Check Safari's Develop → Show Extension Builder for errors

### Permission Issues
- Ensure "Allow Unsigned Extensions" is enabled
- Check host_permissions in manifest.json match target sites
- Verify CORS headers in Laravel backend

### Content Script Not Injecting
- Check matches pattern in manifest.json
- Verify run_at timing (document_end)
- Check Web Inspector console for errors

### API Communication Failing
- Verify Laravel server is running on port 8000
- Test API endpoints manually: `curl http://localhost:8000/api/posts/status`
- Check CORS middleware is configured correctly

## File Structure Verification

Ensure your extension folder structure matches:
```
safari-extension/
├── manifest.json          ✓ Updated (icons removed)
├── background.js          ✓ Service worker
├── content.js             ✓ Content script for Twitter
└── popup/
    ├── popup.html         ✓ Extension popup UI
    ├── popup.css          ✓ Popup styling
    └── popup.js           ✓ Popup functionality
```

## Next Steps

Once the extension is loaded and enabled:
1. Navigate to https://x.com or https://twitter.com
2. Open Web Inspector to monitor console output
3. Try liking a tweet to test the capture functionality
4. Check Laravel backend receives the API calls
5. Proceed with manual testing scenarios from `manual-testing.md`