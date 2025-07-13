// CRITICAL: DO NOT MODIFY THIS FILE - ISOLATED LIKE BUTTON DETECTION DEBUGGING
// This file contains the core like button detection logic that must not regress

(function() {
  'use strict';
  
  console.log('🎯 Like Button Debug Module Loaded at', new Date().toISOString());
  console.log('DEBUG MODULE VERSION: 2.0 - MutationObserver approach');
  
  // Track buttons we've already attached listeners to
  const processedButtons = new WeakSet();
  
  // Handler for like button clicks
  function handleLikeButtonClick(e) {
    const button = e.currentTarget;
    const isLike = button.getAttribute('data-testid') === 'like';
    const isUnlike = button.getAttribute('data-testid') === 'unlike';
    
    console.group(`🎯 ${isLike ? 'LIKE' : 'UNLIKE'} BUTTON CLICKED (Direct Handler)`);
    console.log('Button:', button);
    console.log('Aria-label:', button.getAttribute('aria-label'));
    console.log('Event phase:', e.eventPhase === 1 ? 'CAPTURE' : e.eventPhase === 2 ? 'TARGET' : 'BUBBLE');
    
    // Extract tweet data
    const tweetContainer = button.closest('[data-testid="tweet"]');
    if (tweetContainer) {
      console.log('📦 Tweet container found');
      const timeElement = tweetContainer.querySelector('time[datetime]');
      const tweetUrl = timeElement?.closest('a')?.getAttribute('href');
      const tweetId = tweetUrl?.match(/\/status\/(\d+)/)?.[1];
      console.log('Tweet ID:', tweetId || 'NOT FOUND');
      
      // Get tweet text
      const tweetText = tweetContainer.querySelector('[data-testid="tweetText"]')?.textContent;
      console.log('Tweet preview:', tweetText ? tweetText.substring(0, 50) + '...' : 'NO TEXT FOUND');
    }
    
    console.groupEnd();
    
    // Don't stop propagation - let Twitter's handlers run too
  }
  
  // Attach listeners directly to like/unlike buttons
  function attachButtonListeners() {
    // Find all like and unlike buttons
    const buttons = document.querySelectorAll('[data-testid="like"], [data-testid="unlike"]');
    let newButtonsCount = 0;
    
    buttons.forEach(button => {
      if (!processedButtons.has(button)) {
        // Mark as processed
        processedButtons.add(button);
        newButtonsCount++;
        
        // Add click listener directly to the button
        // Use capture phase to get the event before Twitter's handlers
        button.addEventListener('click', handleLikeButtonClick, true);
        
        // Also add to bubble phase as backup
        button.addEventListener('click', handleLikeButtonClick, false);
      }
    });
    
    if (newButtonsCount > 0) {
      console.log(`🎯 Attached listeners to ${newButtonsCount} new like/unlike buttons`);
    }
  }
  
  // Set up MutationObserver to watch for new buttons
  const observer = new MutationObserver((mutations) => {
    // Check if any mutations might have added new buttons
    for (const mutation of mutations) {
      if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
        // New nodes added, check for buttons
        attachButtonListeners();
        break;
      }
    }
  });
  
  // Start observing the entire document for changes
  observer.observe(document.body, {
    childList: true,
    subtree: true
  });
  
  // Initial scan for buttons
  setTimeout(() => {
    console.log('🎯 Initial button scan...');
    attachButtonListeners();
    
    const likeButtons = document.querySelectorAll('[data-testid="like"]');
    const unlikeButtons = document.querySelectorAll('[data-testid="unlike"]');
    console.log(`🎯 Page has ${likeButtons.length} like buttons and ${unlikeButtons.length} unlike buttons`);
  }, 1000);
  
  // Expose debug functions
  window.testLikeDetection = function() {
    console.log('🎯 Manual button scan...');
    attachButtonListeners();
    
    const likeButtons = document.querySelectorAll('[data-testid="like"]');
    const unlikeButtons = document.querySelectorAll('[data-testid="unlike"]');
    console.log(`Found ${likeButtons.length} like buttons and ${unlikeButtons.length} unlike buttons`);
    
    return { likeButtons, unlikeButtons };
  };
  
  window.debugButtonClick = function(buttonIndex = 0) {
    const buttons = document.querySelectorAll('[data-testid="like"], [data-testid="unlike"]');
    if (buttons[buttonIndex]) {
      console.log('🎯 Simulating click on button:', buttons[buttonIndex]);
      buttons[buttonIndex].click();
    } else {
      console.log('❌ No button at index', buttonIndex);
    }
  };
  
  console.log('🎯 Like detection ready. Using MutationObserver + direct button listeners.');
  console.log('🎯 Debug functions: testLikeDetection(), debugButtonClick(index)');
  
})();