# Manual Testing Guide: Twitter Likes Capture Extension

## Setup Instructions

### Prerequisites
- macOS 11+ with Safari 14+
- PHP 8.1+ installed
- Composer installed
- SQLite 3.8+ (usually included with macOS)

### Environment Setup

#### 1. Laravel Backend Setup
```bash
# Navigate to project directory
cd specs/001-twitter-likes-extension/laravel-backend/

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run database migrations
php artisan migrate

# Verify CLI commands work
php artisan posts:status
php artisan posts:process --help
```

**Expected Result**: All commands execute without errors, status shows empty database.

#### 2. Safari Extension Installation
```bash
# Build extension (if build step required)
cd ../safari-extension/

# Open Safari and go to Preferences > Advanced
# Enable "Show Develop menu in menu bar"
# Develop menu > Show Extension Builder
# Click + and select extension directory
# Follow Safari's extension installation process
```

**Expected Result**: Extension appears in Safari's extension list and can be enabled.

#### 3. Database Connection Verification
```bash
# Check database file created and accessible
ls -la laravel-backend/database/database.sqlite

# Verify Laravel can read/write
php artisan migrate:status

# Test direct SQLite access (simulating extension)
sqlite3 laravel-backend/database/database.sqlite ".tables"
```

**Expected Result**: Database file exists, migrations completed, tables visible.

## User Story Validation

### US-001: Automatic Post Capture on Like

#### Happy Path Test
**Setup**: 
- Safari open with extension enabled
- Navigate to x.com and log in
- Open Laravel backend terminal for monitoring

**Steps**:
1. Find a tweet with text and images
2. Click the like button (heart icon)
3. Observe extension behavior
4. Check database via Laravel CLI
5. Verify screenshot capture

**Expected Results**:
- Extension detects like click within 1 second
- User sees visual confirmation (popup/notification)
- Post content captured including author, text, timestamp
- Screenshot saved in designated directory
- Database record created with correct data

**Verification Commands**:
```bash
php artisan posts:status
# Should show: total_posts: 1, unprocessed_posts: 1

php artisan posts:export --format=json --output=test-export.json
# Review exported data for completeness

# Check screenshot file exists
ls -la storage/screenshots/
```

#### Edge Case Test: Unlike Operation
**Setup**: Use same tweet from happy path test

**Steps**:
1. Click unlike button (remove heart)
2. Check extension feedback
3. Verify database state
4. Check screenshot cleanup

**Expected Results**:
- Extension detects unlike operation
- Database record deleted immediately
- Screenshot file remains (cleanup via Laravel)
- Status commands no longer show the post

**Verification Commands**:
```bash
php artisan posts:status
# Should show: total_posts: 0

# Verify screenshot orphaned but not deleted yet
ls -la storage/screenshots/
# File should still exist

php artisan posts:cleanup --orphaned-screenshots
# Should remove orphaned screenshot
```

### US-002: Visual Layout Preservation

#### Complex Layout Test
**Setup**: 
- Find tweets with various media types
- Clear previous test data

**Steps**:
1. Like a tweet with multiple images
2. Like a tweet with a poll
3. Like a tweet with embedded video
4. Like a quote tweet
5. Verify screenshots for each

**Expected Results**:
- All screenshots capture full post visual layout
- Images preserved with original quality
- Poll options visible in screenshot
- Video thumbnail/player interface captured
- Quote tweet shows both quote text and original

**Verification Commands**:
```bash
php artisan posts:status
# Should show 4 posts captured

# Export all posts and review screenshots
php artisan posts:export --format=json --output=layout-test.json
# Open each screenshot file to verify visual quality
```

#### Edge Case: Dynamic Content
**Setup**: Find tweet with animated GIF or video

**Steps**:
1. Like tweet while video/GIF is playing
2. Check screenshot timing
3. Verify content still captured if animation fails

**Expected Results**:
- Screenshot captures current frame at moment of like
- No errors if animation cannot be captured
- Text content always preserved regardless of media issues

### US-003: Thread Context Capture

#### Thread Navigation Test
**Setup**: 
- Find a long Twitter thread (10+ tweets)
- Navigate to thread view showing full conversation

**Steps**:
1. Navigate to full thread view (click on a reply to see context)
2. Like a reply that's 3-4 levels deep in thread
3. Verify thread context captured
4. Check relationship data

**Expected Results**:
- Root post identified and captured
- Immediate parent post captured
- Thread relationships stored in database
- Screenshot includes visible thread context
- All captured posts linked with proper relationships

**Verification Commands**:
```bash
php artisan posts:process
# Process thread relationships

# Check thread structure
php artisan posts:export --format=json --output=thread-test.json
# Review JSON for thread_relationships data

# Query database directly for relationships
sqlite3 database/database.sqlite "
SELECT 
  lp.content_text, 
  tr.depth_level, 
  parent.content_text as parent_text
FROM liked_posts lp
LEFT JOIN thread_relationships tr ON lp.id = tr.child_post_id
LEFT JOIN liked_posts parent ON tr.parent_post_id = parent.id
ORDER BY tr.depth_level;
"
```

#### Edge Case: Deleted Parent Posts
**Setup**: Find a thread where some replies reference deleted posts

**Steps**:
1. Like a reply where parent tweet is [deleted] or unavailable
2. Check error handling
3. Verify available context still captured

**Expected Results**:
- Extension captures available thread context
- Missing parent marked appropriately
- No errors prevent capture of available data
- User informed of limitations

### US-004: Privacy and Local Storage

#### Local-Only Verification Test
**Setup**: 
- Network monitoring tool (Activity Monitor or similar)
- Fresh browser session

**Steps**:
1. Enable network monitoring
2. Like several tweets
3. Monitor network traffic
4. Verify no external data transmission

**Expected Results**:
- No network requests beyond normal x.com usage
- All data stays on local machine
- Database file only accessible locally
- Screenshots stored in local directory only

**Verification Commands**:
```bash
# Check file permissions (should be user-only)
ls -la database/database.sqlite
ls -la storage/screenshots/

# Verify no external connections in Laravel logs
tail -f storage/logs/laravel.log
# Should show no external API calls or data transmission
```

### US-005: Automatic Content Clustering

#### Clustering Preparation Test
**Setup**: 
- Like 10+ tweets on diverse topics (technology, sports, news, etc.)
- Ensure varied content for clustering

**Steps**:
1. Capture diverse set of tweets
2. Run Laravel processing
3. Verify data prepared for AI analysis
4. Check content structure

**Expected Results**:
- All text content extracted and stored
- Content ready for external AI clustering
- No built-in clustering performed (out of scope)
- Data structure supports future AI analysis

**Verification Commands**:
```bash
php artisan posts:process
# Process all captured content

php artisan posts:export --format=json --output=clustering-data.json
# Verify JSON structure suitable for AI analysis

# Check content diversity
sqlite3 database/database.sqlite "
SELECT 
  COUNT(*) as total_posts,
  COUNT(DISTINCT author_username) as unique_authors,
  AVG(LENGTH(content_text)) as avg_content_length
FROM liked_posts;
"
```

## Error Scenario Testing

### Extension Error Handling

#### Test: X.com UI Changes
**Setup**: 
- Use browser developer tools to modify DOM structure
- Simulate x.com layout changes

**Steps**:
1. Use dev tools to hide/modify like button elements
2. Attempt to like posts with modified DOM
3. Check extension error handling
4. Verify graceful degradation

**Expected Results**:
- Extension continues operating if possible
- Clear error messages for users
- Partial capture better than complete failure
- Logs provide debugging information

#### Test: Database Access Failure
**Setup**: 
- Temporarily make database file read-only
- Attempt post capture

**Steps**:
1. Make database read-only: `chmod 444 database/database.sqlite`
2. Like a tweet
3. Check extension error handling
4. Restore permissions: `chmod 644 database/database.sqlite`
5. Verify recovery

**Expected Results**:
- Extension reports database error clearly
- User informed of capture failure
- No data corruption occurs
- Normal operation resumes when fixed

### Laravel Backend Error Handling

#### Test: Processing Large Datasets
**Setup**: 
- Capture 50+ posts quickly
- Test processing performance

**Steps**:
1. Like many posts in rapid succession
2. Run `php artisan posts:process`
3. Monitor performance and memory usage
4. Verify all posts processed correctly

**Expected Results**:
- Processing completes within reasonable time (< 5 minutes for 50 posts)
- Memory usage stays under 256MB
- All posts processed without errors
- Database remains consistent

#### Test: Corrupted Data Recovery
**Setup**: 
- Manually insert invalid data into database
- Test Laravel error handling

**Steps**:
1. Insert invalid tweet data: `sqlite3 database/database.sqlite "INSERT INTO liked_posts (id, tweet_id) VALUES ('invalid', 'test');"`
2. Run `php artisan posts:process`
3. Check error handling
4. Verify good data unaffected

**Expected Results**:
- Invalid records identified and logged
- Processing continues for valid records
- Clear error messages for debugging
- Database integrity maintained

## Performance Validation

### Capture Speed Test
**Objective**: Verify 3-second capture requirement

**Steps**:
1. Open browser with timer/stopwatch
2. Like a complex tweet (with images and thread context)
3. Measure time from click to completion notification
4. Repeat for 10 different posts
5. Calculate average time

**Acceptance Criteria**: 
- 90% of captures complete within 3 seconds
- No capture takes longer than 5 seconds
- User experience remains smooth during capture

### Success Rate Test
**Objective**: Verify 99% capture success rate

**Steps**:
1. Like 100 diverse posts over extended session
2. Monitor capture successes and failures
3. Categorize failure types
4. Calculate success rate

**Acceptance Criteria**:
- At least 99 out of 100 captures succeed
- Failures are due to genuine technical limitations (deleted posts, network issues)
- No failures due to extension bugs

### Storage Efficiency Test
**Objective**: Verify reasonable storage usage

**Steps**:
1. Capture 1000 posts with screenshots
2. Measure total storage usage
3. Calculate average storage per post
4. Project storage needs for heavy users

**Expected Results**:
- Average post uses < 500KB (text + screenshot)
- Database scales efficiently with more posts
- Storage growth is predictable and reasonable

## Cleanup and Reset

### Test Data Cleanup
```bash
# Reset database
php artisan migrate:fresh

# Clear screenshots
rm -rf storage/screenshots/*

# Clear export files
rm -f *.json *.csv

# Verify clean state
php artisan posts:status
# Should show all zeros
```

### Extension Reset
1. Disable extension in Safari
2. Clear extension storage (if applicable)
3. Re-enable extension
4. Verify fresh start behavior

## Documentation and Support

### Installation Documentation Test
**Objective**: Verify setup instructions are complete and accurate

**Steps**:
1. Follow setup instructions exactly on fresh system
2. Document any missing steps or unclear instructions
3. Time the complete setup process
4. Note any prerequisite software needed

**Acceptance Criteria**:
- Complete setup possible from documentation alone
- Setup time under 30 minutes for technical user
- All prerequisites clearly listed
- No undocumented dependencies

### Troubleshooting Guide Test
**Objective**: Verify troubleshooting procedures work

**Steps**:
1. Simulate common problems (permission errors, database locks, etc.)
2. Follow troubleshooting guide procedures
3. Verify problems resolve correctly
4. Document any missing troubleshooting scenarios

**Acceptance Criteria**:
- Common problems have documented solutions
- Troubleshooting steps are clear and effective
- Error messages match troubleshooting guide
- Escalation path defined for unsolved issues