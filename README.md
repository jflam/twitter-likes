# Twitter Likes Capture Extension

A Safari browser extension that automatically captures and saves Twitter/X posts when you like them, enabling personal analysis and memory management.

## Overview

This extension monitors your Twitter/X activity and captures posts when you interact with the like button. It saves post content, metadata, and screenshots to a local Laravel backend for later retrieval and analysis.

## Project Structure

- **twitter-likes/**: Safari extension source code (current version)
  - Cross-platform Safari extension for macOS and iOS
  - Includes popup interface and content scripts
  - Version 1.0.1 with debug capabilities

- **laravel-backend-full/**: Full Laravel application backend
  - Complete Laravel 12 installation with all framework features
  - Includes authentication, caching, queuing, and other Laravel services
  - Use this for production deployments

- **laravel-backend/**: Minimal Laravel backend
  - Lightweight version with only essential components
  - Custom composer.json focusing on specific Illuminate packages
  - Use this for development or when you need a smaller footprint

- **safari-extension/**: Legacy extension code (deprecated)
  - Earlier version (1.0.0) of the extension
  - Migrated to twitter-likes/ directory
  - Kept for reference only

## Features

- **Automatic Capture**: Detects when you like a post and captures it immediately
- **Content Preservation**: Saves post text, author information, timestamps, and metadata
- **Screenshot Capture**: Takes screenshots of posts for visual reference
- **Thread Detection**: Identifies and maintains relationships between posts in threads
- **Batch Processing**: Supports capturing multiple posts in a session
- **Local Storage**: All data stored locally on your machine for privacy

## Requirements

- macOS 11.0+ or iOS 14.0+
- Safari browser
- PHP 8.2+
- Composer
- SQLite or MySQL/PostgreSQL

## Installation

### Backend Setup

1. Choose your backend:
   - For production: Use `laravel-backend-full`
   - For development: Use `laravel-backend`

2. Install dependencies:
   ```bash
   cd laravel-backend-full  # or laravel-backend
   composer install
   ```

3. Configure environment:
   ```bash
   cp .env.example .env  # for laravel-backend-full
   php artisan key:generate
   ```

4. Run migrations:
   ```bash
   php artisan migrate
   ```

5. Start the server:
   ```bash
   php artisan serve
   ```

### Extension Setup

1. Open the Xcode project:
   ```bash
   open twitter-likes/twitter-likes.xcodeproj
   ```

2. Build and run the extension for your target platform (macOS or iOS)

3. Enable the extension in Safari Preferences → Extensions

## Usage

1. Navigate to Twitter/X in Safari
2. Click the extension icon to view status
3. Like posts normally - they will be automatically captured
4. View captured posts through the Laravel backend interface

## Development

### Quick Reload Scripts
- `quick-reload.sh`: Reload extension without full rebuild
- `force-reload-extension.sh`: Force reload with cache clearing
- `dev-extension.sh`: Development mode with hot reloading
- `build-extension.sh`: Production build

### API Endpoints

- `POST /api/posts/capture`: Submit captured post data
- `POST /api/posts/{id}/screenshot`: Upload post screenshot
- `GET /api/posts`: Retrieve captured posts
- `GET /api/posts/{id}`: Get specific post details

### Console Commands

- `php artisan posts:status`: View capture statistics
- `php artisan posts:process`: Process pending posts
- `php artisan posts:export`: Export posts to various formats
- `php artisan posts:cleanup`: Remove old or duplicate posts

## Architecture

The extension uses a three-part architecture:

1. **Content Script** (`content.js`): Monitors DOM changes and detects like interactions
2. **Background Script** (`background.js`): Manages API communication and screenshot capture
3. **Laravel Backend**: Stores and processes captured data

## Privacy & Security

- All data is stored locally on your machine
- No external services or cloud storage used
- API communication limited to localhost
- No tracking or analytics included

## Troubleshooting

See `SAFARI_DEBUG_GUIDE.md` for detailed debugging instructions.

Common issues:
- Extension not loading: Check Safari extension preferences
- API connection failed: Ensure Laravel backend is running on port 8000
- Posts not capturing: Enable debug mode with `debug-like-detection.js`

## License

This project is private and proprietary. All rights reserved.