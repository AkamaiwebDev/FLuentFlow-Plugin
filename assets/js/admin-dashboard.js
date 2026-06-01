(function () {
	'use strict';

	var labels = {
		enabled: 'Enabled',
		disabled: 'Disabled',
	};

	/* ------------------------------------------------
	   Toggle label switcher
	   ------------------------------------------------ */
	document.querySelectorAll('.ffbb-toggle input[type="checkbox"]').forEach(function (checkbox) {
		checkbox.addEventListener('change', function () {
			var label = this.closest('.ffbb-toggle').querySelector('.ffbb-toggle-label');
			if (label) {
				label.textContent = this.checked ? labels.enabled : labels.disabled;
			}
		});
	});

	/* ------------------------------------------------
	   Clipboard copy — shared for token and shortcode chips
	   ------------------------------------------------ */
	function copyToClipboard(text, btn) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(function () {
				flashCopied(btn);
			})['catch'](function () {
				fallbackCopy(text, btn);
			});
		} else {
			fallbackCopy(text, btn);
		}
	}

	function fallbackCopy(text, btn) {
		var textarea = document.createElement('textarea');
		textarea.value = text;
		textarea.style.position = 'fixed';
		textarea.style.opacity = '0';
		document.body.appendChild(textarea);
		textarea.select();
		try {
			document.execCommand('copy');
			flashCopied(btn);
		} catch (e) {
			// silently fail
		}
		document.body.removeChild(textarea);
	}

	function flashCopied(btn) {
		btn.classList.add('ffbb-copied');
		var originalLabel = btn.querySelector('.ffbb-token-label');
		if (originalLabel) {
			originalLabel.textContent = 'Copied!';
		}
		setTimeout(function () {
			btn.classList.remove('ffbb-copied');
			if (originalLabel) {
				originalLabel.textContent = btn.getAttribute('data-original-label') || originalLabel.textContent;
			}
		}, 1800);
	}

	/* ------------------------------------------------
	   Attach clipboard to all data-clipboard elements
	   ------------------------------------------------ */
	document.querySelectorAll('[data-clipboard]').forEach(function (el) {
		var text = el.getAttribute('data-clipboard');
		var labelEl = el.querySelector('.ffbb-token-label');
		if (labelEl && !el.hasAttribute('data-original-label')) {
			el.setAttribute('data-original-label', labelEl.textContent);
		}
		el.addEventListener('click', function (e) {
			e.preventDefault();
			copyToClipboard(text, el);
		});
	});
})();
