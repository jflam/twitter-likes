#!/bin/bash

# Force Safari Extension Reload - Nuclear Option
# Clears caches and forces complete reload

echo "🔥 Force Reloading Safari Extension..."
echo "===================================="

# Kill Safari completely
echo "1. Closing Safari..."
osascript -e 'quit app "Safari"'
sleep 2

# Clear Safari Extension caches
echo "2. Clearing extension caches..."
rm -rf ~/Library/Safari/ExtensionCache/* 2>/dev/null || true
rm -rf ~/Library/Caches/com.apple.Safari/WebKitCache/* 2>/dev/null || true

# Remove build artifacts
echo "3. Cleaning build artifacts..."
rm -rf /Users/jflam/src/twitter-likes/build 2>/dev/null || true

# Full rebuild
echo "4. Running full rebuild..."
/Users/jflam/src/twitter-likes/build-extension.sh

echo ""
echo "✅ Force reload complete!"
echo ""
echo "When Safari opens:"
echo "1. Go to Settings → Extensions"
echo "2. Twitter Likes Capture should be there"
echo "3. Make sure it's enabled"
echo "4. Navigate to x.com"
echo "5. Open Developer → x.com - Twitter Likes Capture"