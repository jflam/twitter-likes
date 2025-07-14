#!/bin/bash

echo "🧹 Force Clean and Rebuild Twitter Likes Extension"
echo "================================================"

# Check if Xcode is properly configured
if ! xcodebuild -version &>/dev/null; then
    echo "❌ Error: Xcode command line tools not properly configured"
    echo ""
    echo "Please run:"
    echo "  sudo xcode-select -s /Applications/Xcode.app/Contents/Developer"
    echo ""
    echo "Or build manually in Xcode:"
    echo "  1. Open twitter-likes/twitter-likes.xcodeproj"
    echo "  2. Product → Clean Build Folder (⇧⌘K)"
    echo "  3. Product → Build (⌘B)"
    echo ""
    exit 1
fi

# Kill Safari if running
echo "📌 Closing Safari..."
osascript -e 'quit app "Safari"' 2>/dev/null || true
sleep 2

# Clean Xcode build
echo "🔨 Cleaning Xcode build..."
cd /Users/jflam/src/twitter-likes/twitter-likes
xcodebuild clean -project twitter-likes.xcodeproj -quiet

# Remove Safari extension caches
echo "🗑️  Removing Safari extension caches..."
rm -rf ~/Library/Safari/Extensions/twitter-likes* 2>/dev/null || true
rm -rf ~/Library/Containers/com.apple.Safari/Data/Library/Safari/Extensions/*twitter* 2>/dev/null || true
rm -rf ~/Library/Containers/com.apple.Safari/Data/Library/Safari/ExtensionUpdates/*twitter* 2>/dev/null || true

# Clear Safari web extension data
echo "🧹 Clearing Safari web extension data..."
rm -rf ~/Library/Safari/WebExtensions/*twitter* 2>/dev/null || true
rm -rf ~/Library/Containers/com.apple.Safari/Data/Library/Safari/WebExtensions/*twitter* 2>/dev/null || true

# Build fresh
echo "🏗️  Building extension fresh..."
xcodebuild -project twitter-likes.xcodeproj -scheme "twitter-likes (macOS)" -quiet

echo ""
echo "✅ Clean rebuild complete!"
echo ""
echo "Next steps:"
echo "1. Open Safari"
echo "2. Go to Safari → Settings → Extensions"
echo "3. Enable Twitter Likes Capture"
echo "4. Check console for 'VERSION: 1.0.2'"
echo ""
echo "The console should show:"
echo "  🚀 TWITTER LIKES CAPTURE - CONTENT.JS LOADED"
echo "  📌 VERSION: 1.0.2"
echo ""
echo "ℹ️  Note: All JavaScript files should be in 'Shared (Extension)/Resources/'"
echo "    The macOS and iOS folders should only contain platform-specific files."