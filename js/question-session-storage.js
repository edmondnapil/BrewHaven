// Button-scoped behavior only: restore/save auth answers during session
document.addEventListener('DOMContentLoaded', function() {
  // If page was reloaded, clear any session-stored answers
  try {
    var reloaded = false;
    if (performance && performance.getEntriesByType) {
      var navs = performance.getEntriesByType('navigation');
      if (navs && navs.length && navs[0].type === 'reload') reloaded = true;
    } else if (performance && performance.navigation) {
      if (performance.navigation.type === 1) reloaded = true; // TYPE_RELOAD
    }
    if (reloaded) {
      sessionStorage.removeItem('authFormData');
    }
  } catch (e) { /* ignore */ }
  
  // Restore previously entered answers/selections from sessionStorage
  try {
    const raw = sessionStorage.getItem('authFormData');
    if (raw) {
      const data = JSON.parse(raw);
      const ids = ['auth_question1','auth_answer1','auth_question2','auth_answer2','auth_question3','auth_answer3'];
      ids.forEach(id => {
        const el = document.getElementById(id);
        if (el && data.hasOwnProperty(id)) {
          el.value = data[id];
          // Trigger events so existing validation/UI reacts if needed
          el.dispatchEvent(new Event('change', { bubbles: true }));
          el.dispatchEvent(new Event('input', { bubbles: true }));
        }
      });
    }
  } catch (e) { /* ignore */ }

  // Wire Previous button to save current answers then navigate back
  const prevBtn = document.getElementById('prev-button');
  if (prevBtn) {
    prevBtn.addEventListener('click', function(e) {
      e.preventDefault();
      // Collect current answers/selections
      const payload = {
        auth_question1: (document.getElementById('auth_question1')||{}).value || '',
        auth_answer1: (document.getElementById('auth_answer1')||{}).value || '',
        auth_question2: (document.getElementById('auth_question2')||{}).value || '',
        auth_answer2: (document.getElementById('auth_answer2')||{}).value || '',
        auth_question3: (document.getElementById('auth_question3')||{}).value || '',
        auth_answer3: (document.getElementById('auth_answer3')||{}).value || ''
      };
      try { sessionStorage.setItem('authFormData', JSON.stringify(payload)); } catch (e) {}
      // Navigate back to registration
      window.location.href = 'register.php';
    });
  }
  
  // If registration data has already been cleared (e.g., after successful submission),
  // ensure the temporary session copy used by register.php is also cleared so the
  // registration form shows blank fields on next visit.
  window.addEventListener('pageshow', function() {
    if (!localStorage.getItem('registrationFormData')) {
      sessionStorage.removeItem('registrationFormDataSession');
    }
  });
});

