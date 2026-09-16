'use strict';

(function() {
	// Disable default browser password toggle for all password inputs with toggle icons
	function disableBrowserPasswordToggle() {
		const passwordInputs = document.querySelectorAll('.input-with-toggle input[type="password"]');
		passwordInputs.forEach(function(input) {
			// Set autocomplete to off to prevent browser's default password manager toggle
			if (!input.hasAttribute('autocomplete')) {
				input.setAttribute('autocomplete', 'off');
			}
			
			// Prevent browser's default password reveal button
			// For Edge/IE
			if (input.style) {
				input.style.setProperty('-ms-reveal', 'none', 'important');
			}
		});
	}
	
	// Run on DOMContentLoaded
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', disableBrowserPasswordToggle);
	} else {
		disableBrowserPasswordToggle();
	}
	
	// Also run when new elements are added dynamically
	const observer = new MutationObserver(function(mutations) {
		disableBrowserPasswordToggle();
	});
	
	if (document.body) {
		observer.observe(document.body, {
			childList: true,
			subtree: true
		});
	}
	
	// Support dynamically added icons (e.g., in popups) via event delegation
	document.addEventListener('click', function(e) {
		var icon = e.target.closest && e.target.closest('.togele-eye');
		if (!icon) return;
		var targetId = icon.dataset.target;
		if (!targetId) return;
		var input = document.getElementById(targetId);
		if (!input) return;
		if (input.type === 'password') {
			input.type = 'text';
			icon.classList.remove('fa-eye');
			icon.classList.add('fa-eye-slash');
		} else {
			input.type = 'password';
			icon.classList.remove('fa-eye-slash');
			icon.classList.add('fa-eye');
		}
	});
})();


