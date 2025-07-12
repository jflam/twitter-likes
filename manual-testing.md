# Manual Testing Guide: Twitter Likes Extension

**Feature**: Twitter Likes Capture Extension  
**Version**: 1.0.0  
**Last Updated**: 2025-07-12  

## Pre-Testing Setup

### Prerequisites
- ✅ Safari 14+ on macOS 11+
- ✅ Laravel backend running on localhost:8000
- ✅ SQLite database configured and migrated
- ✅ Extension loaded in Safari Developer mode

### Backend Preparation
```bash
# 1. Start Laravel backend
cd laravel-backend
php artisan serve --port=8000

# 2. Verify database is ready
php artisan migrate:fresh
php artisan posts:status

# 3. Test API endpoints
curl http://localhost:8000/api/posts/status
```

### Extension Installation
1. Open Safari → Develop → Show Extension Builder
2. Add extension from `safari-extension` folder
3. Enable extension in Safari → Preferences → Extensions
4. Verify extension icon appears in toolbar

---

## Test Scenarios

### Test 1: Basic Like Capture ✅

**Objective**: Verify extension captures liked tweets correctly

**Steps**:
1. Navigate to https://x.com or https://twitter.com
2. Find any tweet in your timeline
3. Click the heart (like) button
4. Observe extension behavior

**Expected Results**:
- ✅ Extension detects like action
- ✅ Green notification appears: "Tweet captured successfully! 🐦"
- ✅ Laravel backend receives API call
- ✅ Tweet data stored in database

**Validation**:
```bash
# Check database
php artisan posts:status

# Verify specific tweet
php artisan tinker
>>> App\Models\LikedPost::latest()->first()
```

---

### Test 2: Unlike Removes Data ✅

**Objective**: Verify unliking removes captured data

**Steps**:
1. Like a tweet (should capture successfully from Test 1)
2. Click the same heart button to unlike
3. Observe extension behavior

**Expected Results**:
- ✅ Extension detects unlike action
- ✅ Blue notification appears: "Tweet removed from collection"
- ✅ Data deleted from database
- ✅ Screenshot file removed

**Validation**:
```bash
# Verify tweet is removed
php artisan posts:status
# Count should decrease by 1
```

---

### Test 3: Duplicate Prevention ✅

**Objective**: Verify extension handles already-liked tweets

**Steps**:
1. Like a tweet (first time)
2. Unlike the tweet
3. Like the same tweet again
4. Observe extension behavior

**Expected Results**:
- ✅ First like: "Tweet captured successfully! 🐦"
- ✅ Unlike: "Tweet removed from collection"
- ✅ Second like: "Tweet captured successfully! 🐦"
- ✅ No duplicate database entries

---

### Test 4: Extension Popup Interface ✅

**Objective**: Verify popup shows correct status and controls

**Steps**:
1. Click extension icon in Safari toolbar
2. Review popup interface
3. Test toggle controls
4. Test connection button

**Expected Results**:
- ✅ Status indicator shows "Connected" (green dot)
- ✅ Statistics show correct counts
- ✅ Extension enabled toggle works
- ✅ "Test Connection" button shows success
- ✅ Settings persist when popup closes

---

### Test 5: Different Tweet Types ✅

**Objective**: Verify extension works with various tweet formats

**Test Cases**:

#### 5A: Original Tweet
- ✅ Text-only tweet
- ✅ Tweet with media (images/videos)
- ✅ Tweet with hashtags and mentions

#### 5B: Retweets
- ✅ Simple retweet
- ✅ Retweet with comment (quote tweet)

#### 5C: Replies
- ✅ Reply to another tweet
- ✅ Reply in a thread

**Validation**: Check `post_type` field in database matches tweet type

---

### Test 6: High-Volume Usage ✅

**Objective**: Test extension performance with rapid likes

**Steps**:
1. Rapidly like 10 tweets in succession
2. Monitor extension notifications
3. Check for any errors or delays

**Expected Results**:
- ✅ All likes processed successfully
- ✅ No duplicate captures
- ✅ No performance degradation
- ✅ Backend handles concurrent requests

---

### Test 7: Offline/Backend Down Scenarios ✅

**Objective**: Verify graceful error handling

**Steps**:
1. Stop Laravel backend (`Ctrl+C`)
2. Like a tweet while backend is down
3. Observe extension behavior
4. Restart backend and test again

**Expected Results**:
- ✅ Extension shows error notification: "Unable to connect to backend"
- ✅ Extension icon shows red "X" badge
- ✅ No browser console errors
- ✅ Extension recovers when backend restarts

---

### Test 8: CLI Commands Testing ✅

**Objective**: Verify Laravel Artisan commands work correctly

**Commands to Test**:

```bash
# Status command
php artisan posts:status
php artisan posts:status --format=json

# Processing command
php artisan posts:process --dry-run
php artisan posts:process --batch-size=5

# Export command
php artisan posts:export --format=json
php artisan posts:export --format=csv

# Cleanup command
php artisan posts:cleanup --dry-run
php artisan posts:cleanup --orphaned-screenshots
```

**Expected Results**:
- ✅ All commands execute without errors
- ✅ Help text available with `--help` flag
- ✅ Output format matches expected structure
- ✅ Dry-run mode doesn't modify data

---

### Test 9: Data Integrity Validation ✅

**Objective**: Verify captured data accuracy

**Steps**:
1. Like a tweet with known characteristics
2. Manually verify captured data matches

**Data Points to Verify**:
- ✅ Tweet ID matches URL
- ✅ Author username/display name correct
- ✅ Content text matches exactly
- ✅ Engagement counts accurate (likes, retweets, replies)
- ✅ Timestamp close to like time
- ✅ URL properly formatted

---

### Test 10: Thread Detection ✅

**Objective**: Verify thread relationship detection

**Steps**:
1. Find a tweet that's part of a thread
2. Like the tweet
3. Check if thread context is captured

**Expected Results**:
- ✅ `is_thread_post` flag set correctly
- ✅ Thread relationships created if applicable
- ✅ Thread position detected

---

## Browser Compatibility Testing

### Safari Specific Features
- ✅ Extension loads in Safari 14+
- ✅ Content script injection works
- ✅ Background script maintains state
- ✅ Popup interface renders correctly
- ✅ Local storage permissions work

### Cross-Platform Elements
- ✅ HTTP API calls succeed
- ✅ CORS headers configured correctly
- ✅ JSON parsing handles all data types
- ✅ Error handling works consistently

---

## Performance Benchmarks

### Response Time Targets
- ✅ Like detection: < 100ms
- ✅ API call completion: < 500ms
- ✅ Notification display: < 200ms
- ✅ Database write: < 100ms

### Memory Usage
- ✅ Extension memory footprint: < 10MB
- ✅ No memory leaks during extended use
- ✅ Background script efficient

---

## Security Validation

### Data Handling
- ✅ Only localhost API calls allowed
- ✅ No sensitive data logged to console
- ✅ Input sanitization working
- ✅ SQL injection prevention active

### Permissions
- ✅ Extension requests minimal permissions
- ✅ No unnecessary host permissions
- ✅ User data stays local

---

## Regression Testing Checklist

After any code changes, verify:
- ✅ Basic like/unlike still works
- ✅ Extension popup loads correctly
- ✅ CLI commands execute properly
- ✅ Database migrations run successfully
- ✅ API endpoints return expected formats

---

## Known Issues & Workarounds

### Current Limitations
1. **Screenshot Capture**: Not fully implemented - uses placeholder
2. **Rate Limiting**: No explicit rate limiting on rapid likes
3. **Thread Detection**: Basic implementation, may miss complex threads

### Workarounds
1. Screenshots can be disabled in extension settings
2. Users advised to avoid rapid-fire liking
3. Thread detection improvement planned for v1.1

---

## Test Report Template

```
Date: [DATE]
Tester: [NAME]
Version: [VERSION]
Environment: [SAFARI VERSION] on [MACOS VERSION]

Test Results:
✅ Basic Like Capture: PASS/FAIL
✅ Unlike Removes Data: PASS/FAIL
✅ Duplicate Prevention: PASS/FAIL
✅ Extension Popup: PASS/FAIL
✅ Different Tweet Types: PASS/FAIL
✅ High-Volume Usage: PASS/FAIL
✅ Offline Scenarios: PASS/FAIL
✅ CLI Commands: PASS/FAIL
✅ Data Integrity: PASS/FAIL
✅ Thread Detection: PASS/FAIL

Overall Status: PASS/FAIL
Notes: [Any additional observations]
```

---

## Troubleshooting Guide

### Common Issues

**Extension not detecting likes**:
1. Check Safari developer console for errors
2. Verify content script loaded on x.com
3. Test with simple tweets first

**Backend connection failed**:
1. Verify Laravel server running on port 8000
2. Check CORS configuration
3. Test API endpoints manually with curl

**Database errors**:
1. Run `php artisan migrate:fresh`
2. Check SQLite file permissions
3. Verify database path in `.env`

**Popup not loading**:
1. Check extension permissions in Safari
2. Reload extension in Extension Builder
3. Clear Safari cache and try again

---

This manual testing guide ensures comprehensive validation of all extension features and provides a reliable framework for ongoing quality assurance.