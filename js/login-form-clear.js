// Clear form fields when page loads and prevent autofill
document.addEventListener('DOMContentLoaded', function() {
  // Function to clear fields
  function clearFormFields() {
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    
    // Clear values
    if (username) username.value = '';
    if (password) password.value = '';
    
    // Clear any error messages
    const errorMessages = document.querySelectorAll('.error-message');
    errorMessages.forEach(el => el.textContent = '');
    
    // Prevent autocomplete
    if (username) {
      username.autocomplete = 'off';
      username.setAttribute('readonly', true);
    }
    if (password) {
      password.autocomplete = 'new-password';
      password.setAttribute('readonly', true);
    }
  }
  
  // Clear fields immediately
  clearFormFields();
  
  // Clear fields again after a short delay to catch any browser autofill
  setTimeout(clearFormFields, 100);
  
  // Remove readonly attributes when user interacts with the fields
  document.addEventListener('click', function enableFields() {
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    
    if (username) username.removeAttribute('readonly');
    if (password) password.removeAttribute('readonly');
    
    // Remove this event listener after first interaction
    document.removeEventListener('click', enableFields);
  }, { once: true });
});

