(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		syncUsernameFromEmail();
	});

	document.addEventListener('click', function(event) {
		var target = event.target;
		var capabilityToggle = target.closest('.tn731-umg-capability-toggle');

		if (capabilityToggle && window.TN731UMGCapabilities) {
			event.preventDefault();
			updateUserCapability(capabilityToggle);
			return;
		}

		if (target.classList.contains('tn731-umg-confirm-remove-capability')) {
			if (!window.confirm('Remove this capability from Administrator and User?')) {
				event.preventDefault();
			}

			return;
		}

		if (target.classList.contains('tn731-umg-confirm-remove-capability-group')) {
			var capabilityGroup = target.getAttribute('data-capability-group') || 'this capability group';

			if (!window.confirm('Remove ' + capabilityGroup + ' from every stored role on this site? This cannot be undone automatically.')) {
				event.preventDefault();
			}

			return;
		}

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

	function updateUserCapability(button) {
		if (button.disabled) {
			return;
		}

		var settings = window.TN731UMGCapabilities;
		var capability = button.getAttribute('data-capability');
		var currentlyEnabled = button.getAttribute('data-enabled') === 'yes';
		var formData = new window.FormData();
		var status = document.getElementById('tn731-umg-capability-status');

		formData.append('action', 'tn731_umg_toggle_user_capability');
		formData.append('nonce', settings.nonce);
		formData.append('capability', capability);
		formData.append('enabled', currentlyEnabled ? 'no' : 'yes');

		button.disabled = true;
		button.setAttribute('aria-busy', 'true');
		button.classList.add('is-loading');

		window.fetch(settings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		})
			.then(function(response) {
				return response.json();
			})
			.then(function(response) {
				if (!response.success || !response.data) {
					throw new Error(response.data && response.data.message ? response.data.message : settings.error);
				}

				var label = button.querySelector('.tn731-umg-capability-toggle-label');
				if (label) {
					label.textContent = response.data.label;
				}
				button.setAttribute('data-enabled', response.data.enabled ? 'yes' : 'no');
				button.setAttribute('aria-checked', response.data.enabled ? 'true' : 'false');
				button.setAttribute('aria-label', response.data.ariaLabel);

				var count = document.getElementById('tn731-umg-user-capability-count');
				if (count) {
					count.textContent = 'Registered (' + response.data.userCount + ')';
				}

				showCapabilityStatus(status, response.data.message || settings.success, false);
			})
			.catch(function(error) {
				showCapabilityStatus(status, error.message || settings.error, true);
			})
			.finally(function() {
				button.disabled = false;
				button.removeAttribute('aria-busy');
				button.classList.remove('is-loading');
			});
	}

	function showCapabilityStatus(status, message, isError) {
		if (!status) {
			return;
		}

		status.textContent = message;
		status.classList.toggle('is-error', isError);
	}

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
