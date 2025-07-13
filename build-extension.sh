#!/bin/bash

# Safari Extension Build & Deploy Script
# Automatically rebuilds and deploys the Twitter Likes Safari extension

set -e  # Exit on error

echo "🚀 Building Twitter Likes Safari Extension..."

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Project paths
PROJECT_DIR="/Users/jflam/src/twitter-likes"
XCODE_PROJECT="$PROJECT_DIR/twitter-likes/twitter-likes.xcodeproj"
EXTENSION_SOURCE="$PROJECT_DIR/twitter-likes/macOS (Extension)"
SHARED_EXTENSION="$PROJECT_DIR/twitter-likes/Shared (Extension)/Resources"

# Check if Xcode project exists
if [ ! -d "$XCODE_PROJECT" ]; then
    echo -e "${RED}❌ Xcode project not found at: $XCODE_PROJECT${NC}"
    exit 1
fi

# Step 1: Copy extension files to Shared resources (Xcode uses both locations)
echo -e "${YELLOW}📁 Syncing extension files...${NC}"
mkdir -p "$SHARED_EXTENSION/popup"

# Copy all extension files
cp -f "$EXTENSION_SOURCE/manifest.json" "$SHARED_EXTENSION/" 2>/dev/null || true
cp -f "$EXTENSION_SOURCE/background.js" "$SHARED_EXTENSION/" 2>/dev/null || true
cp -f "$EXTENSION_SOURCE/content.js" "$SHARED_EXTENSION/" 2>/dev/null || true
cp -f "$EXTENSION_SOURCE/debug-like-detection.js" "$SHARED_EXTENSION/" 2>/dev/null || true
cp -rf "$EXTENSION_SOURCE/popup/" "$SHARED_EXTENSION/popup/" 2>/dev/null || true

echo -e "${GREEN}✅ Files synced${NC}"

# Step 2: Build the Xcode project
echo -e "${YELLOW}🔨 Building Xcode project...${NC}"

# Clean build folder
xcodebuild clean -project "$XCODE_PROJECT" -scheme "twitter-likes (macOS)" -configuration Debug >/dev/null 2>&1 || true

# Build the project
BUILD_OUTPUT=$(xcodebuild build \
    -project "$XCODE_PROJECT" \
    -scheme "twitter-likes (macOS)" \
    -configuration Debug \
    -derivedDataPath "$PROJECT_DIR/build" \
    2>&1)

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Build successful${NC}"
else
    echo -e "${RED}❌ Build failed${NC}"
    echo "$BUILD_OUTPUT"
    exit 1
fi

# Step 3: Find the built app
APP_PATH=$(find "$PROJECT_DIR/build" -name "twitter-likes.app" -type d | head -1)

if [ -z "$APP_PATH" ]; then
    echo -e "${RED}❌ Built app not found${NC}"
    exit 1
fi

echo -e "${GREEN}✅ App built at: $APP_PATH${NC}"

# Step 4: Kill Safari to ensure clean extension reload
echo -e "${YELLOW}🔄 Restarting Safari...${NC}"
osascript -e 'quit app "Safari"' 2>/dev/null || true
sleep 2

# Step 5: Open the app (which prompts to enable extension)
echo -e "${YELLOW}🚀 Opening extension app...${NC}"
open "$APP_PATH"

# Wait a moment
sleep 3

# Step 6: Open Safari
echo -e "${YELLOW}🌐 Opening Safari...${NC}"
open -a Safari

# Step 7: Provide instructions
echo -e "${GREEN}✅ Build complete!${NC}"
echo ""
echo "Next steps:"
echo "1. Safari should open with the extension app"
echo "2. Click 'Quit and Open Safari Extensions Preferences'"
echo "3. Enable 'Twitter Likes Capture' extension"
echo "4. Navigate to x.com to test"
echo ""
echo -e "${YELLOW}💡 Tip: Check Safari Developer menu > Web Inspector > Extension to see console logs${NC}"

# Optional: Open x.com automatically
read -p "Open x.com now? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    sleep 2
    open "https://x.com"
fi