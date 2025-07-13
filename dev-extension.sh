#!/bin/bash

# Safari Extension Development Mode
# Watches for changes and auto-rebuilds the extension

echo "🔥 Twitter Likes Extension - Development Mode"
echo "============================================"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# Paths
PROJECT_DIR="/Users/jflam/src/twitter-likes"
EXTENSION_DIR="$PROJECT_DIR/twitter-likes/macOS (Extension)"
BUILD_SCRIPT="$PROJECT_DIR/build-extension.sh"

# Check for fswatch
if ! command -v fswatch &> /dev/null; then
    echo -e "${YELLOW}fswatch not found. Installing...${NC}"
    if command -v brew &> /dev/null; then
        brew install fswatch
        if [ $? -ne 0 ]; then
            echo -e "${RED}Failed to install fswatch. Please install manually.${NC}"
            exit 1
        fi
    else
        echo -e "${RED}Homebrew not found. Please install fswatch manually:${NC}"
        echo "brew install fswatch"
        exit 1
    fi
fi

# Initial build
echo -e "${BLUE}Running initial build...${NC}"
if [ -f "$BUILD_SCRIPT" ]; then
    "$BUILD_SCRIPT"
else
    echo -e "${RED}Build script not found at: $BUILD_SCRIPT${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}👀 Watching for changes in extension files...${NC}"
echo -e "${YELLOW}Press Ctrl+C to stop${NC}"
echo ""

# Function to handle file changes
handle_change() {
    echo -e "${BLUE}📝 Change detected! Updating...${NC}"
    
    # Copy changed files immediately (for quick testing without full rebuild)
    SHARED_EXT="$PROJECT_DIR/twitter-likes/Shared (Extension)/Resources"
    mkdir -p "$SHARED_EXT/popup"
    
    # Copy all extension files
    cp -f "$EXTENSION_DIR/manifest.json" "$SHARED_EXT/" 2>/dev/null || true
    cp -f "$EXTENSION_DIR/background.js" "$SHARED_EXT/" 2>/dev/null || true
    cp -f "$EXTENSION_DIR/content.js" "$SHARED_EXT/" 2>/dev/null || true
    cp -f "$EXTENSION_DIR/debug-like-detection.js" "$SHARED_EXT/" 2>/dev/null || true
    cp -rf "$EXTENSION_DIR/popup/" "$SHARED_EXT/" 2>/dev/null || true
    
    echo -e "${GREEN}✅ Files synced!${NC}"
    echo ""
    echo "Quick reload options:"
    echo "1. In Safari: Disable and re-enable the extension"
    echo "2. Or refresh the x.com page (Cmd+R)"
    echo "3. For full rebuild: Stop this script (Ctrl+C) and run ./build-extension.sh"
    echo ""
    echo -e "${GREEN}👀 Still watching for changes...${NC}"
    echo "---"
}

# Set up trap to handle Ctrl+C gracefully
trap 'echo -e "\n${YELLOW}Stopped watching files.${NC}"; exit 0' INT TERM

# Watch for changes in extension files
# Using a more robust approach that won't exit
while true; do
    # Watch and wait for changes
    fswatch -1 "$EXTENSION_DIR"/*.js "$EXTENSION_DIR"/manifest.json "$EXTENSION_DIR"/popup/* 2>/dev/null || true
    
    # If fswatch exits (file changed), handle the change
    handle_change
    
    # Small delay to prevent rapid re-triggers
    sleep 1
done