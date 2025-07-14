#!/bin/bash

echo "🔧 Twitter Likes Extension File Editor"
echo "====================================="
echo ""
echo "All extension JavaScript files are in:"
echo "  📁 Shared (Extension)/Resources/"
echo ""
echo "Available files to edit:"
echo "  1) content.js - Main content script"
echo "  2) background.js - Background service worker"
echo "  3) popup.js - Extension popup UI"
echo "  4) manifest.json - Extension manifest"
echo ""

# Change to the correct directory
cd "twitter-likes/Shared (Extension)/Resources/"

# Show current version
echo "Current version info:"
grep -E "VERSION.*[0-9]" content.js manifest.json | head -5

echo ""
echo "💡 Remember: After editing, run ./force-clean-rebuild.sh"