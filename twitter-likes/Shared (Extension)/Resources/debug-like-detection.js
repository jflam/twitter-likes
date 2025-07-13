// CRITICAL: DO NOT MODIFY THIS FILE - ISOLATED LIKE BUTTON DETECTION DEBUGGING
// This file contains the core like button detection logic that must not regress

(function() {
  'use strict';
  
  console.log('🎯 Like Button Debug Module Loaded at', new Date().toISOString());
  
  // Verify we're on the right domain
  console.log('🎯 Current URL:', window.location.href);
  console.log('🎯 Domain:', window.location.hostname);
  
  // Check if we can find any buttons immediately
  setTimeout(() => {
    console.log('🎯 Initial button scan:');
    const likeButtons = document.querySelectorAll('[data-testid="like"]');
    const unlikeButtons = document.querySelectorAll('[data-testid="unlike"]');
    console.log(`  - Found ${likeButtons.length} like buttons`);
    console.log(`  - Found ${unlikeButtons.length} unlike buttons`);
    
    // Check for any tweets
    const tweets = document.querySelectorAll('[data-testid="tweet"]');
    console.log(`  - Found ${tweets.length} tweets on page`);
    
    // Log a sample button if found
    if (likeButtons.length > 0) {
      console.log('🎯 Sample like button:', likeButtons[0]);
    }
    if (unlikeButtons.length > 0) {
      console.log('🎯 Sample unlike button:', unlikeButtons[0]);
    }
  }, 2000);
  
  // Track ALL clicks for debugging
  let clickCount = 0;
  document.addEventListener('click', function(e) {
    clickCount++;
    
    // Log every 10th click to see if events are firing
    if (clickCount % 10 === 0) {
      console.log(`🎯 Debug: ${clickCount} clicks detected so far`);
    }
    
    // Check for any data-testid attribute
    const testId = e.target.getAttribute('data-testid');
    if (testId) {
      console.log('🎯 Clicked element with data-testid:', testId);
    }
    
    const likeButton = e.target.closest('[data-testid="like"]');
    const unlikeButton = e.target.closest('[data-testid="unlike"]');
    
    if (likeButton || unlikeButton) {
      console.group('🎯 LIKE/UNLIKE BUTTON CLICK DETECTED');
      console.log('Click target:', e.target);
      console.log('Target tag:', e.target.tagName);
      console.log('Target testid:', e.target.getAttribute('data-testid'));
      
      if (likeButton) {
        console.log('✅ Like button found:', likeButton);
        console.log('Button aria-label:', likeButton.getAttribute('aria-label'));
        console.log('Button classes:', likeButton.className);
      }
      
      if (unlikeButton) {
        console.log('❤️ Unlike button found:', unlikeButton);
        console.log('Button aria-label:', unlikeButton.getAttribute('aria-label'));
        console.log('Button classes:', unlikeButton.className);
      }
      
      const tweetContainer = (likeButton || unlikeButton).closest('[data-testid="tweet"]');
      if (tweetContainer) {
        console.log('📦 Tweet container found');
        const timeElement = tweetContainer.querySelector('time[datetime]');
        const tweetUrl = timeElement?.closest('a')?.getAttribute('href');
        const tweetId = tweetUrl?.match(/\/status\/(\d+)/)?.[1];
        console.log('Tweet ID:', tweetId || 'NOT FOUND');
      } else {
        console.log('❌ No tweet container found');
      }
      
      console.groupEnd();
    }
  }, true); // Use capture phase to catch events early
  
  // Expose test function
  window.testLikeDetection = function() {
    console.log('🎯 Testing like button detection...');
    const likeButtons = document.querySelectorAll('[data-testid="like"]');
    const unlikeButtons = document.querySelectorAll('[data-testid="unlike"]');
    console.log(`Found ${likeButtons.length} like buttons and ${unlikeButtons.length} unlike buttons`);
    
    // Show first few buttons for inspection
    if (likeButtons.length > 0) {
      console.log('First like button:', likeButtons[0]);
      console.log('Parent structure:', likeButtons[0].parentElement);
    }
    
    return { likeButtons, unlikeButtons };
  };
  
  console.log('🎯 Debug module ready. Run testLikeDetection() to scan for buttons.');
  
})();