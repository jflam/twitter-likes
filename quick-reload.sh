#!/bin/bash

# Quick Safari Extension Reload
# Copies files and provides reload instructions without full rebuild

set -e

echo "⚡ Quick Safari Extension Reload"
echo "==============================="

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Paths
PROJECT_DIR="/Users/jflam/src/twitter-likes"
EXTENSION_SOURCE="$PROJECT_DIR/twitter-likes/macOS (Extension)"
SHARED_EXTENSION="$PROJECT_DIR/twitter-likes/Shared (Extension)/Resources"

# Copy all extension files
echo -e "${YELLOW}📁 Copying extension files...${NC}"
mkdir -p "$SHARED_EXTENSION/popup"

cp -f "$EXTENSION_SOURCE/manifest.json" "$SHARED_EXTENSION/" 2>/dev/null || true
cp -f "$EXTENSION_SOURCE/background.js" "$SHARED_EXTENSION/" 2>/dev/null || true
cp -f "$EXTENSION_SOURCE/content.js" "$SHARED_EXTENSION/" 2>/dev/null || true
cp -f "$EXTENSION_SOURCE/debug-like-detection.js" "$SHARED_EXTENSION/" 2>/dev/null || true
cp -rf "$EXTENSION_SOURCE/popup/" "$SHARED_EXTENSION/popup/" 2>/dev/null || true

echo -e "${GREEN}✅ Files copied!${NC}"
echo ""
echo "To reload the extension:"
echo "1. Open Safari > Settings > Extensions"
echo "2. Uncheck 'Twitter Likes Capture'"
echo "3. Check 'Twitter Likes Capture' again"
echo "4. Refresh x.com"
echo ""
echo -e "${YELLOW}💡 Or use Cmd+R in Safari Developer Console${NC}"