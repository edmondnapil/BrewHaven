// === BACK BUTTON PROTECTION ===
// This runs immediately (before DOM is ready) to block back navigation silently
(function() {
  // Track if we're preventing navigation
  let isPreventing = true;
  
  // Push initial state to lock user in
  history.pushState(null, "", location.href);
  
  function preventBack(e) {
    if (!isPreventing) return;
    
    // Immediately push state again to prevent navigation
    history.pushState(null, "", location.href);
    
    // Also use replaceState as backup
    history.replaceState(null, "", location.href);
    
    // Push another state immediately to ensure we stay locked
    setTimeout(() => {
      if (isPreventing) {
        history.pushState(null, "", location.href);
      }
    }, 0);
  }

  // Listen for back button events - silently prevent navigation
  window.addEventListener("popstate", preventBack, true);
  
  // Also listen with capture phase for maximum coverage
  window.addEventListener("popstate", preventBack);

  // Continuously re-push state to ensure back button is always disabled
  // More frequent interval for better protection
  const stateInterval = setInterval(() => {
    if (isPreventing) {
      history.pushState(null, "", location.href);
    } else {
      clearInterval(stateInterval);
    }
  }, 100);

  // Block keyboard shortcuts for back navigation (Alt+Left, Backspace, etc.)
  function handleKeydown(e) {
    if (!isPreventing) return;
    
    if (
      (e.altKey && (e.key === "ArrowLeft" || e.key === "ArrowRight")) ||
      (e.key === "Backspace" &&
        !["input", "textarea"].includes((e.target.tagName || "").toLowerCase()))
    ) {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
      // Silently prevent - no alert
    }
  }
  window.addEventListener("keydown", handleKeydown, true);

  // Store references for cleanup on logout
  window._preventBack = preventBack;
  window._handleKeydown = handleKeydown;
  window._stateInterval = stateInterval;

  // Restore normal behavior after logout
  window.enableBackNavigation = function() {
    isPreventing = false;
    if (window._stateInterval) {
      clearInterval(window._stateInterval);
    }
    window.removeEventListener("popstate", preventBack, true);
    window.removeEventListener("popstate", preventBack);
    window.removeEventListener("keydown", handleKeydown, true);
  };
})();

