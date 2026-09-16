// Handle logout functionality
function handleLogout() {
  // Clear any stored session data
  sessionStorage.clear();
  localStorage.clear();
  
  // Clear login form fields by passing the values through URL parameters
  // These will be cleared when the login page loads
  const loginUrl = 'login.php?clearFields=true';
  
  // Enable back navigation before redirecting
  if (window.enableBackNavigation) {
    window.enableBackNavigation();
  }
  
  // Redirect to login page
  window.location.href = loginUrl;
}

document.addEventListener("DOMContentLoaded", () => {
  // Add logout button event listener
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
      e.preventDefault();
      handleLogout();
    });
  }

  // Handle logout button - enable back navigation and redirect to login
  const logoutEl = document.getElementById('logoutBtn') || document.querySelector('.logout-btn');
  if (logoutEl) {
    logoutEl.addEventListener('click', function(e) {
      e.preventDefault(); // Prevent default link behavior
      // Remove the back-blocker
      if (window.enableBackNavigation) {
        window.enableBackNavigation();
      }
      // Go to login page, replacing history (clears dashboard from history)
      window.location.replace('login.php');
    });
  }
});

