# Data Model: Twitter Likes Database Schema

## Entity Definitions

### LikedPost (Primary Entity)
Main table storing captured tweet content and metadata.

**Fields:**
- `id` (Primary Key, UUID)
- `tweet_id` (String, X.com post identifier)
- `author_username` (String, @username)
- `author_display_name` (String, display name)
- `author_avatar_url` (String, profile image URL)
- `content_text` (Text, full post text)
- `content_html` (Text, formatted HTML if needed)
- `language_code` (String, detected language)
- `post_url` (String, full URL to original post)
- `posted_at` (Timestamp, when original post was created)
- `liked_at` (Timestamp, when user liked the post)
- `captured_at` (Timestamp, when extension captured data)
- `post_type` (Enum: 'original', 'retweet', 'quote_tweet', 'reply')
- `reply_count` (Integer)
- `retweet_count` (Integer)
- `like_count` (Integer)
- `view_count` (Integer, if available)
- `is_thread_post` (Boolean)
- `thread_position` (Integer, position in thread if applicable)

### PostScreenshot
Stores visual captures of posts with layout preserved.

**Fields:**
- `id` (Primary Key, UUID)
- `liked_post_id` (Foreign Key → LikedPost.id)
- `image_path` (String, local file path to screenshot)
- `image_format` (String, 'png', 'jpg', etc.)
- `image_size_bytes` (Integer)
- `screenshot_width` (Integer, pixels)
- `screenshot_height` (Integer, pixels)
- `capture_method` (String, 'full_post', 'visible_area', 'thread_context')
- `quality_score` (Float, 0-1 rating of capture quality)
- `created_at` (Timestamp)

### ThreadRelationship
Tracks parent-child relationships between posts in conversations.

**Fields:**
- `id` (Primary Key, UUID)
- `child_post_id` (Foreign Key → LikedPost.id)
- `parent_post_id` (Foreign Key → LikedPost.id)
- `root_post_id` (Foreign Key → LikedPost.id)
- `depth_level` (Integer, how deep in thread)
- `relationship_type` (Enum: 'reply', 'quote', 'thread_continuation')
- `discovered_at` (Timestamp)

### CaptureSession
Metadata about capture operations for debugging and analytics.

**Fields:**
- `id` (Primary Key, UUID)
- `browser_session_id` (String, unique session identifier)
- `capture_started_at` (Timestamp)
- `capture_completed_at` (Timestamp)
- `posts_captured` (Integer, count of posts in session)
- `screenshots_captured` (Integer)
- `errors_encountered` (JSON, error details)
- `x_com_page_url` (String, URL where capture occurred)
- `extension_version` (String)

## Relationships

```
LikedPost (1) ←→ (0..1) PostScreenshot
LikedPost (1) ←→ (0..*) ThreadRelationship (as child)
LikedPost (1) ←→ (0..*) ThreadRelationship (as parent)
LikedPost (1) ←→ (0..*) ThreadRelationship (as root)
CaptureSession (1) ←→ (0..*) LikedPost
```

## Indexes for Performance

**Primary Indexes:**
- `liked_posts.tweet_id` (unique constraint)
- `liked_posts.liked_at` (time-based queries)
- `liked_posts.author_username` (user filtering)
- `thread_relationships.child_post_id`
- `thread_relationships.root_post_id`

**Composite Indexes:**
- `(author_username, liked_at)` for user timeline queries
- `(post_type, liked_at)` for filtering by type
- `(is_thread_post, thread_position)` for thread reconstruction

## Validation Rules

**LikedPost:**
- `tweet_id` must be unique
- `author_username` must start with valid character set
- `post_url` must be valid X.com URL format
- `posted_at` cannot be in future
- `liked_at` cannot be before `posted_at`

**ThreadRelationship:**
- Cannot create circular references
- `depth_level` must be >= 0
- Root post cannot have parent

## Data Integrity Constraints

1. **Cascade Deletes**: When LikedPost deleted → delete associated PostScreenshot and ThreadRelationships
2. **Reference Integrity**: All foreign keys must reference existing records
3. **Unique Constraints**: One screenshot per post maximum
4. **Business Rules**: 
   - Unlike operations delete entire post record
   - Thread relationships maintain referential integrity
   - Screenshot files must exist on filesystem when database record exists