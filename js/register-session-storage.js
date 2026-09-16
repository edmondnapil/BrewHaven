// Button-scoped persistence for current session only
document.addEventListener('DOMContentLoaded', function() {
  // If this page was reloaded, clear session copy to satisfy temporary persistence
  try {
    var reloaded = false;
    if (performance && performance.getEntriesByType) {
      var navs = performance.getEntriesByType('navigation');
      if (navs && navs.length && navs[0].type === 'reload') reloaded = true;
    } else if (performance && performance.navigation) {
      if (performance.navigation.type === 1) reloaded = true; // TYPE_RELOAD
    }
    if (reloaded) {
      sessionStorage.removeItem('registrationFormDataSession');
    }
  } catch (e) { /* ignore */ }

  // Restore previously entered registration info from sessionStorage
  try {
    const raw = sessionStorage.getItem('registrationFormDataSession');
    if (raw) {
      const data = JSON.parse(raw);
      const ids = [
        'idnumber','lastname','firstname','middlename','extension','birth_date','age','gender',
        'email','username','password','repassword',
        'street','barangay','city_municipality','province','country','zipcode'
      ];
      ids.forEach(id => {
        const el = document.getElementById(id);
        if (el && Object.prototype.hasOwnProperty.call(data, id)) {
          el.value = data[id];
          // Trigger events so existing validation/UI reacts if needed
          el.dispatchEvent(new Event('change', { bubbles: true }));
          el.dispatchEvent(new Event('input', { bubbles: true }));
        }
      });
    }
  } catch (e) { /* ignore */ }

  // Before going to questions, save current registration values to sessionStorage
  const nextBtn = document.getElementById('next-button');
  if (nextBtn) {
    nextBtn.addEventListener('click', function() {
      try {
        const payload = {};
        [
          'idnumber','lastname','firstname','middlename','extension','birth_date','age','gender',
          'email','username','password','repassword',
          'street','barangay','city_municipality','province','country','zipcode'
        ].forEach(id => {
          const el = document.getElementById(id);
          if (el) payload[id] = el.value || '';
        });
        sessionStorage.setItem('registrationFormDataSession', JSON.stringify(payload));
      } catch (e) { /* ignore */ }
    }, { capture: true }); // capture to ensure it runs regardless of other handlers
  }
});

