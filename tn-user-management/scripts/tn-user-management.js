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

		var deleteCapability = target.closest('.tn731-umg-delete-capability');
		if (deleteCapability) {
			var capability = deleteCapability.getAttribute('data-capability');
			var confirmation = window.TN731UMGCapabilities
				? window.TN731UMGCapabilities.deleteConfirm.replace('%s', capability)
				: 'Remove ' + capability + ' from every stored role on this site? This cannot be undone automatically.';

			if (!window.confirm(confirmation)) {
				event.preventDefault();
				return;
			}

			if (window.TN731UMGCapabilities) {
				event.preventDefault();
				deleteSingleCapability(deleteCapability);
			}

			return;
		}

		var deleteCapabilityGroup = target.closest('.tn731-umg-confirm-remove-capability-group');
		if (deleteCapabilityGroup) {
			var capabilityGroup = deleteCapabilityGroup.getAttribute('data-capability-group') || 'this capability group';

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

	function deleteSingleCapability(button) {
		if (button.disabled) {
			return;
		}

		var settings = window.TN731UMGCapabilities;
		var capability = button.getAttribute('data-capability');
		var formData = new window.FormData();
		var status = document.getElementById('tn731-umg-capability-status');

		formData.append('action', 'tn731_umg_delete_capability');
		formData.append('nonce', settings.nonce);
		formData.append('capability', capability);

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

				updateCapabilityCounts(response.data.counts);
				removeCapabilityRow(button.closest('tr'), settings.emptyGroup);
				showCapabilityStatus(status, '', false);
			})
			.catch(function(error) {
				button.disabled = false;
				button.removeAttribute('aria-busy');
				button.classList.remove('is-loading');
				showCapabilityStatus(status, error.message || settings.error, true);
			});
	}

	function updateCapabilityCounts(counts) {
		if (!counts) {
			return;
		}

		['administrator', 'user', 'subscriber'].forEach(function(role) {
			var count = document.getElementById('tn731-umg-' + role + '-capability-count');
			if (count && typeof counts[role] !== 'undefined') {
				count.textContent = 'Registered (' + counts[role] + ')';
			}
		});
	}

	function removeCapabilityRow(row, emptyGroupLabel) {
		if (!row || !row.parentElement) {
			return;
		}

		var body = row.parentElement;
		row.remove();

		if (!body.querySelector('tr')) {
			var emptyRow = document.createElement('tr');
			var emptyCell = document.createElement('td');
			emptyCell.colSpan = 5;
			emptyCell.textContent = emptyGroupLabel;
			emptyRow.appendChild(emptyCell);
			body.appendChild(emptyRow);
		}
	}

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

				updateCapabilityCounts({user: response.data.userCount});

				showCapabilityStatus(status, '', false);
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
		status.hidden = !message;
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
