# API Contracts: Extension ↔ Laravel Backend Communication

## Overview
Communication between Safari extension and Laravel backend occurs through:
1. **HTTP API Requests** (localhost:8000) - Extension to Laravel
2. **Laravel Artisan CLI Commands** (for processing and analysis)
3. **SQLite Database** (Laravel manages all database operations)
4. **CORS Configuration** (Required for Safari extension communication)

## HTTP API Interface Contract

### Extension → Laravel (HTTP Requests)

#### POST /api/posts/capture
**Purpose**: Capture a liked post with content and screenshot
**Method**: POST
**URL**: `http://localhost:8000/api/posts/capture`
**Content-Type**: `application/json`

**Request Body**:
```json
{
  "tweet_id": "string",
  "author_username": "string",
  "author_display_name": "string", 
  "author_avatar_url": "string",
  "content_text": "string",
  "content_html": "string",
  "post_url": "string",
  "posted_at": "iso-datetime",
  "liked_at": "iso-datetime",
  "post_type": "original|retweet|quote_tweet|reply",
  "like_count": "integer",
  "retweet_count": "integer",
  "reply_count": "integer",
  "screenshot_base64": "string",
  "screenshot_width": "integer", 
  "screenshot_height": "integer",
  "thread_context": [
    {
      "tweet_id": "string",
      "content_text": "string",
      "author_username": "string",
      "relationship": "parent|root|child"
    }
  ]
}
```

**Response (Success)**:
```json
{
  "status": "success",
  "post_id": "uuid",
  "screenshot_saved": true,
  "thread_relationships_created": 2
}
```

**Response (Error)**:
```json
{
  "status": "error",
  "message": "Tweet already exists",
  "error_code": "DUPLICATE_TWEET"
}
```

#### DELETE /api/posts/unlike
**Purpose**: Delete a post when user unlikes it
**Method**: DELETE
**URL**: `http://localhost:8000/api/posts/unlike`
**Content-Type**: `application/json`

**Request Body**:
```json
{
  "tweet_id": "string"
}
```

**Response (Success)**:
```json
{
  "status": "success",
  "deleted": true,
  "screenshot_removed": true
}
```

**Response (Error)**:
```json
{
  "status": "error", 
  "message": "Post not found",
  "error_code": "POST_NOT_FOUND"
}
```

#### GET /api/posts/status
**Purpose**: Get extension status and stats
**Method**: GET
**URL**: `http://localhost:8000/api/posts/status`

**Response**:
```json
{
  "status": "success",
  "stats": {
    "total_posts": 1234,
    "unprocessed_posts": 5,
    "screenshot_count": 1200,
    "thread_relationships": 89,
    "last_capture": "2025-07-12T14:30:00Z"
  },
  "server_status": "healthy"
}
```

### Laravel → Database (Read/Process Operations)

#### Batch Processing Query
**Operation**: SELECT with processing flag
**Purpose**: Find unprocessed posts for analysis
```sql
SELECT * FROM liked_posts 
WHERE processed_at IS NULL 
ORDER BY captured_at ASC 
LIMIT 50
```

#### Thread Reconstruction Query
**Operation**: Complex JOIN
**Purpose**: Build thread relationships
```sql
SELECT lp.*, tr.parent_post_id, tr.root_post_id 
FROM liked_posts lp
LEFT JOIN thread_relationships tr ON lp.id = tr.child_post_id
WHERE lp.is_thread_post = true
```

## Laravel Artisan CLI Interface

### Core Commands

#### Process Captured Posts
```bash
php artisan posts:process [--batch-size=50] [--dry-run]
```
**Purpose**: Process raw captured data, extract metadata, build relationships
**Input**: Unprocessed database records
**Output**: Updated records with relationships and metadata
**Exit Codes**: 0 (success), 1 (processing error), 2 (database error)

#### Analyze Content
```bash
php artisan posts:analyze [--type=clustering|sentiment] [--output=json|text]
```
**Purpose**: Run AI analysis on captured content
**Input**: Processed posts from database
**Output**: Analysis results (JSON or human-readable)
**Exit Codes**: 0 (success), 1 (analysis error)

#### Export Data
```bash
php artisan posts:export [--format=json|csv|sql] [--output=file.ext]
```
**Purpose**: Export captured data for external analysis
**Input**: All liked posts data
**Output**: Structured export file
**Exit Codes**: 0 (success), 1 (export error)

#### Database Maintenance
```bash
php artisan posts:cleanup [--orphaned-screenshots] [--older-than=30days]
```
**Purpose**: Clean up orphaned files and old data
**Input**: Database state
**Output**: Cleanup report
**Exit Codes**: 0 (success), 1 (cleanup error)

### Status and Health Commands

#### System Status
```bash
php artisan posts:status [--format=json]
```
**Output Example**:
```json
{
  "database": {
    "total_posts": 1234,
    "unprocessed_posts": 5,
    "screenshot_count": 1200,
    "thread_relationships": 89
  },
  "storage": {
    "database_size_mb": 45.2,
    "screenshots_size_mb": 1250.8,
    "available_space_mb": 15600
  },
  "processing": {
    "last_batch_processed": "2025-07-12T14:30:00Z",
    "processing_errors": 0
  }
}
```

## CORS Configuration Contract

### Laravel CORS Middleware Setup
**Required for Safari Extension Communication**

#### CORS Headers Required
```php
// config/cors.php
'allowed_origins' => ['http://localhost:*', 'https://localhost:*'],
'allowed_methods' => ['GET', 'POST', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => false,
```

#### API Routes Configuration
```php
// routes/api.php  
Route::middleware(['cors'])->group(function () {
    Route::post('/posts/capture', [PostController::class, 'capture']);
    Route::delete('/posts/unlike', [PostController::class, 'unlike']);
    Route::get('/posts/status', [PostController::class, 'status']);
});
```

#### Safari Extension Manifest Permissions
```json
{
  "permissions": [
    "http://localhost:8000/*",
    "https://localhost:8000/*"
  ]
}
```

## Error Handling Contract

### Database Errors
- **Constraint Violations**: Return specific error codes for duplicate posts, invalid references
- **Connection Errors**: Graceful degradation, queue operations for retry
- **Corruption Detection**: Integrity checks and recovery procedures

### CLI Command Errors
- **Invalid Arguments**: Return usage help and exit code 2
- **Permission Errors**: Return error code 3 with clear message
- **Processing Failures**: Return error code 1 with detailed error log

### File System Errors
- **Screenshot Storage**: Fallback to text-only capture if image storage fails
- **Message Queue**: Retry mechanism for temporary file system issues
- **Cleanup Operations**: Safe deletion with verification

## Security Contract

### Data Access Permissions
- Extension has read/write access only to dedicated SQLite database
- Laravel has full database access plus file system operations
- No network communication between extension and Laravel (local only)

### Data Validation
- All input sanitized before database insertion
- File paths validated to prevent directory traversal
- Image file format validation before storage
- Tweet ID format validation to prevent injection