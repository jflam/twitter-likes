-- Twitter Likes Extension Database Schema Contract
-- SQLite 3.8+ compatible schema
-- This schema serves as the contract between Safari Extension and Laravel Backend

-- Enable foreign key constraints
PRAGMA foreign_keys = ON;

-- Main table storing captured tweet content and metadata
CREATE TABLE liked_posts (
    id TEXT PRIMARY KEY, -- UUID
    tweet_id TEXT NOT NULL UNIQUE,
    author_username TEXT NOT NULL,
    author_display_name TEXT NOT NULL,
    author_avatar_url TEXT,
    content_text TEXT NOT NULL,
    content_html TEXT,
    language_code TEXT,
    post_url TEXT NOT NULL,
    posted_at TIMESTAMP NOT NULL,
    liked_at TIMESTAMP NOT NULL,
    captured_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    post_type TEXT NOT NULL CHECK (post_type IN ('original', 'retweet', 'quote_tweet', 'reply')),
    reply_count INTEGER DEFAULT 0,
    retweet_count INTEGER DEFAULT 0,
    like_count INTEGER DEFAULT 0,
    view_count INTEGER,
    is_thread_post BOOLEAN DEFAULT FALSE,
    thread_position INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Validation constraints
    CHECK (liked_at >= posted_at),
    CHECK (captured_at >= liked_at),
    CHECK (reply_count >= 0),
    CHECK (retweet_count >= 0),
    CHECK (like_count >= 0),
    CHECK (thread_position IS NULL OR thread_position >= 1)
);

-- Stores visual captures of posts with layout preserved
CREATE TABLE post_screenshots (
    id TEXT PRIMARY KEY, -- UUID
    liked_post_id TEXT NOT NULL,
    image_path TEXT NOT NULL,
    image_format TEXT NOT NULL CHECK (image_format IN ('png', 'jpg', 'jpeg', 'webp')),
    image_size_bytes INTEGER NOT NULL,
    screenshot_width INTEGER NOT NULL,
    screenshot_height INTEGER NOT NULL,
    capture_method TEXT NOT NULL CHECK (capture_method IN ('full_post', 'visible_area', 'thread_context')),
    quality_score REAL CHECK (quality_score >= 0 AND quality_score <= 1),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key relationship
    FOREIGN KEY (liked_post_id) REFERENCES liked_posts(id) ON DELETE CASCADE,
    
    -- Constraints
    CHECK (image_size_bytes > 0),
    CHECK (screenshot_width > 0),
    CHECK (screenshot_height > 0)
);

-- Tracks parent-child relationships between posts in conversations
CREATE TABLE thread_relationships (
    id TEXT PRIMARY KEY, -- UUID
    child_post_id TEXT NOT NULL,
    parent_post_id TEXT,
    root_post_id TEXT NOT NULL,
    depth_level INTEGER NOT NULL DEFAULT 0,
    relationship_type TEXT NOT NULL CHECK (relationship_type IN ('reply', 'quote', 'thread_continuation')),
    discovered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key relationships
    FOREIGN KEY (child_post_id) REFERENCES liked_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_post_id) REFERENCES liked_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (root_post_id) REFERENCES liked_posts(id) ON DELETE CASCADE,
    
    -- Constraints
    CHECK (depth_level >= 0),
    CHECK (child_post_id != parent_post_id),
    CHECK (child_post_id != root_post_id),
    
    -- Root posts cannot have parents
    CHECK ((depth_level = 0 AND parent_post_id IS NULL) OR (depth_level > 0 AND parent_post_id IS NOT NULL))
);

-- Metadata about capture operations for debugging and analytics
CREATE TABLE capture_sessions (
    id TEXT PRIMARY KEY, -- UUID
    browser_session_id TEXT NOT NULL,
    capture_started_at TIMESTAMP NOT NULL,
    capture_completed_at TIMESTAMP,
    posts_captured INTEGER DEFAULT 0,
    screenshots_captured INTEGER DEFAULT 0,
    errors_encountered TEXT, -- JSON format
    x_com_page_url TEXT NOT NULL,
    extension_version TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraints
    CHECK (posts_captured >= 0),
    CHECK (screenshots_captured >= 0),
    CHECK (capture_completed_at IS NULL OR capture_completed_at >= capture_started_at)
);

-- Performance indexes
CREATE INDEX idx_liked_posts_tweet_id ON liked_posts(tweet_id);
CREATE INDEX idx_liked_posts_liked_at ON liked_posts(liked_at);
CREATE INDEX idx_liked_posts_author_username ON liked_posts(author_username);
CREATE INDEX idx_liked_posts_author_liked_at ON liked_posts(author_username, liked_at);
CREATE INDEX idx_liked_posts_post_type_liked_at ON liked_posts(post_type, liked_at);
CREATE INDEX idx_liked_posts_thread ON liked_posts(is_thread_post, thread_position);

CREATE INDEX idx_post_screenshots_liked_post_id ON post_screenshots(liked_post_id);

CREATE INDEX idx_thread_relationships_child_post_id ON thread_relationships(child_post_id);
CREATE INDEX idx_thread_relationships_parent_post_id ON thread_relationships(parent_post_id);
CREATE INDEX idx_thread_relationships_root_post_id ON thread_relationships(root_post_id);

CREATE INDEX idx_capture_sessions_browser_session_id ON capture_sessions(browser_session_id);
CREATE INDEX idx_capture_sessions_started_at ON capture_sessions(capture_started_at);

-- Unique constraints
CREATE UNIQUE INDEX idx_post_screenshots_unique_per_post ON post_screenshots(liked_post_id);
CREATE UNIQUE INDEX idx_thread_relationships_unique_child_parent ON thread_relationships(child_post_id, parent_post_id);