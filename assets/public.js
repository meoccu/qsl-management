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

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', syncAddressType);
	} else {
		syncAddressType();
	}
})();
