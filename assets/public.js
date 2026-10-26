(function () {
	'use strict';

	function syncAddressType() {
		var checked = document.querySelector('.qslm-public input[name="address_type"]:checked');
		var value = checked ? checked.value : 'personal';
		document.querySelectorAll('[data-qslm-address]').forEach(function (box) {
			box.hidden = box.getAttribute('data-qslm-address') !== value;
		});
	}

	document.addEventListener('change', function (event) {
		if (event.target.matches('.qslm-public input[name="address_type"]')) {
			syncAddressType();
		}
		if (event.target.matches('.qslm-public input[name="card_version"]')) {
			var group = event.target.closest('.qslm-public__style-grid');
			if (group) {
				group.querySelectorAll('.qslm-public__style').forEach(function (style) {
					style.classList.toggle('is-selected', style.contains(event.target));
				});
			}
		}
	});

	document.querySelectorAll('.qslm-public input[name="card_version"]:checked').forEach(function (input) {
		var style = input.closest('.qslm-public__style');
		if (style) {
			style.classList.add('is-selected');
		}
	});

	function initQslmTurnstile() {
		if (!window.turnstile) {
			return;
		}
		document.querySelectorAll('.qslm-public__turnstile.cf-turnstile').forEach(function (box) {
			if (box.getAttribute('data-qslm-rendered')) {
				return;
			}
			if (box.querySelector('iframe') || box.querySelector('input[name="cf-turnstile-response"]')) {
				box.setAttribute('data-qslm-rendered', '1');
				return;
			}
			box.setAttribute('data-qslm-rendered', '1');
			try {
				window.turnstile.render(box, {
					sitekey: box.getAttribute('data-sitekey'),
					theme: box.getAttribute('data-theme') || 'auto',
					action: box.getAttribute('data-action') || 'qslm_exchange'
				});
			} catch (e) {
				/* Keep the server-side check as the source of truth. */
			}
		});
	}

	if (document.querySelector('.qslm-public__turnstile.cf-turnstile')) {
		initQslmTurnstile();
		var qslmTurnstileTries = 0;
		var qslmTurnstileTimer = window.setInterval(function () {
			qslmTurnstileTries++;
			initQslmTurnstile();
			if (window.turnstile || qslmTurnstileTries >= 60) {
				window.clearInterval(qslmTurnstileTimer);
			}
		}, 250);
		window.addEventListener('load', initQslmTurnstile);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', syncAddressType);
	} else {
		syncAddressType();
	}
})();
