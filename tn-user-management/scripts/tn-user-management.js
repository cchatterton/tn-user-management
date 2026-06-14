(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		syncUsernameFromEmail();
	});

	document.addEventListener('click', function(event) {
		var target = event.target;

		if (!target.classList.contains('tn731-umg-select-all') && !target.classList.contains('tn731-umg-deselect-all')) {
			return;
		}

		event.preventDefault();

		var targetId = target.getAttribute('data-target');
		var wrap = document.getElementById(targetId);

		if (!wrap) {
			return;
		}

		var checked = target.classList.contains('tn731-umg-select-all');
		var boxes = wrap.querySelectorAll('input[type="checkbox"]');

		boxes.forEach(function(box) {
			box.checked = checked;
		});
	});

	function syncUsernameFromEmail() {
		var usernameField =
			document.querySelector('#user_login') ||
			document.querySelector('input[name="user[username]"]');

		var emailField =
			document.querySelector('#email') ||
			document.querySelector('#user_email') ||
			document.querySelector('input[name="email"]') ||
			document.querySelector('input[name="user[email]"]');

		if (!usernameField || !emailField) {
			return;
		}

		var row = usernameField.closest('tr') || usernameField.closest('p') || usernameField.parentElement;

		if (row) {
			row.style.display = 'none';
		}

		function syncUsername() {
			usernameField.value = (emailField.value || '').trim().toLowerCase();
		}

		syncUsername();
		emailField.addEventListener('input', syncUsername);
		emailField.addEventListener('change', syncUsername);
	}
})();
