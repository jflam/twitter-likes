# Contract Tests: Extension ↔ Laravel Integration

## Database Contract Tests

### LikedPost Table Contract Tests

#### Test: Valid Post Insertion
```
GIVEN: Valid post data with all required fields
WHEN: Extension inserts post via direct SQLite access
THEN: 
  - Record inserted successfully
  - All fields preserved correctly
  - Timestamps in UTC format
  - ID generated as valid UUID
```

#### Test: Duplicate Tweet ID Handling
```
GIVEN: Post with tweet_id already exists in database
WHEN: Extension attempts to insert same tweet_id
THEN: 
  - Operation fails with constraint violation
  - Original record remains unchanged
  - Error logged appropriately
```

#### Test: Unlike Operation (Delete Cascade)
```
GIVEN: Post exists with associated screenshot and thread relationships
WHEN: Extension executes DELETE by tweet_id
THEN:
  - Post record deleted
  - Associated screenshot record deleted (cascade)
  - Thread relationship records deleted (cascade)
  - Screenshot file remains (cleanup via Laravel)
```

#### Test: Thread Post Insertion
```
GIVEN: Reply post with thread context data
WHEN: Extension inserts with is_thread_post=true
THEN:
  - Post inserted with thread metadata
  - Thread relationships can be established
  - Parent/child references valid
```

### PostScreenshot Table Contract Tests

#### Test: Screenshot with Post Reference
```
GIVEN: Valid screenshot data with existing post ID
WHEN: Extension inserts screenshot record
THEN:
  - Screenshot linked to correct post
  - Image metadata stored correctly
  - File path validated and accessible
```

#### Test: Orphaned Screenshot Prevention
```
GIVEN: Screenshot data with non-existent post ID
WHEN: Extension attempts screenshot insertion
THEN:
  - Foreign key constraint prevents insertion
  - Error returned to extension
  - No orphaned screenshot created
```

## Laravel CLI Contract Tests

### posts:process Command Tests

#### Test: Basic Processing Success
```
GIVEN: Unprocessed posts in database
WHEN: php artisan posts:process executed
THEN:
  - Exit code 0
  - processed_at timestamp updated
  - Thread relationships created where applicable
  - Status output shows processing count
```

#### Test: Empty Queue Handling
```
GIVEN: No unprocessed posts in database
WHEN: php artisan posts:process executed
THEN:
  - Exit code 0
  - Message indicates no posts to process
  - No database changes made
```

#### Test: Processing Error Handling
```
GIVEN: Corrupted post data in database
WHEN: php artisan posts:process executed
THEN:
  - Exit code 1
  - Error details logged
  - Good records processed successfully
  - Failed records marked with error status
```

#### Test: Batch Size Parameter
```
GIVEN: 100 unprocessed posts in database
WHEN: php artisan posts:process --batch-size=25
THEN:
  - Exactly 25 posts processed
  - Remaining 75 posts still unprocessed
  - Exit code 0
```

#### Test: Dry Run Mode
```
GIVEN: Unprocessed posts in database
WHEN: php artisan posts:process --dry-run
THEN:
  - Exit code 0
  - Report shows what would be processed
  - No actual database changes made
  - processed_at timestamps unchanged
```

### posts:status Command Tests

#### Test: JSON Status Output
```
WHEN: php artisan posts:status --format=json
THEN:
  - Valid JSON output
  - Contains all required status fields
  - Numeric values are accurate
  - Timestamps in ISO format
```

#### Test: Human Readable Status
```
WHEN: php artisan posts:status (default format)
THEN:
  - Human-readable text output
  - Clear formatting and labels
  - All key metrics displayed
  - No JSON or raw data visible
```

### posts:export Command Tests

#### Test: JSON Export Success
```
GIVEN: Posts exist in database
WHEN: php artisan posts:export --format=json --output=export.json
THEN:
  - File created at specified path
  - Valid JSON structure
  - All post data included
  - Screenshot paths preserved
  - Exit code 0
```

#### Test: CSV Export Success
```
GIVEN: Posts exist in database
WHEN: php artisan posts:export --format=csv --output=export.csv
THEN:
  - CSV file created with headers
  - All records exported
  - Special characters escaped properly
  - File readable by standard tools
```

#### Test: Export Permission Error
```
GIVEN: Read-only output directory
WHEN: php artisan posts:export --output=/readonly/export.json
THEN:
  - Exit code 1
  - Clear error message about permissions
  - No partial file created
```

### posts:cleanup Command Tests

#### Test: Orphaned Screenshot Cleanup
```
GIVEN: Screenshot files exist without database records
WHEN: php artisan posts:cleanup --orphaned-screenshots
THEN:
  - Orphaned files identified and deleted
  - Database records remain intact
  - Report shows cleanup statistics
  - Exit code 0
```

#### Test: Age-based Cleanup
```
GIVEN: Posts older than 30 days in database
WHEN: php artisan posts:cleanup --older-than=30days
THEN:
  - Old posts and screenshots deleted
  - Recent posts preserved
  - Cascade deletes work correctly
  - Report shows deletion count
```

## File-based Message Queue Tests

### Extension Message Creation Tests

#### Test: Valid Capture Message
```
GIVEN: Post capture occurs in extension
WHEN: Extension writes capture message to queue
THEN:
  - Message file created in correct directory
  - JSON format valid and complete
  - Unique filename generated
  - Required fields populated
```

#### Test: Message File Permissions
```
GIVEN: Message file created by extension
WHEN: Laravel attempts to read message
THEN:
  - File readable by Laravel process
  - Content parseable as JSON
  - All required fields accessible
```

### Laravel Message Processing Tests

#### Test: Message Queue Processing
```
GIVEN: Capture messages in queue directory
WHEN: Laravel processes message queue
THEN:
  - All messages processed in order
  - Database updated with captured data
  - Processed messages archived/deleted
  - Error messages logged for failures
```

#### Test: Malformed Message Handling
```
GIVEN: Invalid JSON in message file
WHEN: Laravel processes message queue
THEN:
  - Malformed message skipped
  - Error logged with file details
  - Processing continues with remaining messages
  - Bad message moved to error directory
```

## Integration Error Handling Tests

### Database Connection Tests

#### Test: Database Lock Handling
```
GIVEN: Extension writing to database
WHEN: Laravel attempts concurrent access
THEN:
  - SQLite lock handling works correctly
  - Operations queue appropriately
  - No data corruption occurs
  - Both processes complete successfully
```

#### Test: Database Corruption Recovery
```
GIVEN: Corrupted database file
WHEN: Extension or Laravel attempts access
THEN:
  - Corruption detected gracefully
  - Error reported clearly
  - Backup/recovery process initiated
  - User notified of issue
```

### File System Error Tests

#### Test: Screenshot Storage Failure
```
GIVEN: Full disk or permission error
WHEN: Extension attempts screenshot save
THEN:
  - Text data still captured to database
  - Error logged appropriately
  - User notified of partial capture
  - Operation continues gracefully
```

#### Test: Message Queue Directory Missing
```
GIVEN: Queue directory deleted or unavailable
WHEN: Extension attempts message creation
THEN:
  - Directory recreated automatically
  - Message written successfully
  - Error logged but operation continues
```

## Performance Contract Tests

### Large Dataset Tests

#### Test: Bulk Processing Performance
```
GIVEN: 10,000 unprocessed posts in database
WHEN: posts:process command executed
THEN:
  - Processing completes within 5 minutes
  - Memory usage remains under 256MB
  - Database connections managed properly
  - Progress reported appropriately
```

#### Test: Screenshot Storage Scaling
```
GIVEN: 1,000 high-resolution screenshots
WHEN: Extension captures additional screenshots
THEN:
  - Storage operations complete under 3 seconds each
  - File system performance remains stable
  - No memory leaks in extension
  - Disk space monitoring works correctly
```

### Concurrent Access Tests

#### Test: Extension + Laravel Concurrent Operations
```
GIVEN: Extension actively capturing posts
WHEN: Laravel processing runs simultaneously
THEN:
  - No database deadlocks occur
  - Both operations complete successfully
  - Data consistency maintained
  - Performance impact minimal
```

## Security Contract Tests

### Data Validation Tests

#### Test: SQL Injection Prevention
```
GIVEN: Malicious tweet content with SQL injection attempts
WHEN: Extension inserts data via prepared statements
THEN:
  - Content stored as literal text
  - No SQL execution of malicious content
  - Database integrity maintained
  - Security audit logs clean
```

#### Test: File Path Validation
```
GIVEN: Malicious screenshot path with directory traversal
WHEN: Extension attempts file operation
THEN:
  - Path validation prevents traversal
  - File operation fails safely
  - Error logged appropriately
  - No unauthorized file access occurs
```

### Access Control Tests

#### Test: Database Permission Boundaries
```
GIVEN: Extension and Laravel with different permissions
WHEN: Each attempts database operations
THEN:
  - Extension limited to intended tables only
  - Laravel has full administrative access
  - Permission violations detected and blocked
  - Audit trail maintained
```