/* Cookie Consent WPS – Admin JS */
(function ($) {
	'use strict';

	const { ajaxUrl, nonce, i18n, langPresets, cookies, settings, siteUrl } = window.ccwpsAdmin || {};

	/* ---- Notice ---- */
	function showNotice(msg, type = 'success') {
		const $n = $('#ccwps-notice');
		$n.text(msg).attr('class', 'ccwps-notice ' + type).show();
		setTimeout(() => $n.fadeOut(), 4000);
	}

	function ajaxPost(action, data, cb) {
		$.post(ajaxUrl, { action, nonce, ...data }, cb).fail(() => showNotice(i18n.error, 'error'));
	}

	/* ---- Color pickers ---- */
	$('.ccwps-color-picker').wpColorPicker({
		change: function(event, ui) {
			var key = $(this).attr('name');
			var chk = $('[data-target="' + key + '"]');
			if (chk.length) chk.prop('checked', false);
		}
	});

	/* ---- Transparent checkbox ---- */
	$(document).on('change', '.ccwps-transparent-check', function () {
		var key = $(this).data('target');
		var $picker = $('#' + key);
		if ($(this).is(':checked')) {
			$picker.val('transparent').trigger('change');
		}
	});

	/* ---- Radio highlight ---- */
	$(document).on('change', '.ccwps-radio-option input[type="radio"]', function () {
		$(this).closest('.ccwps-radio-group').find('.ccwps-radio-option').removeClass('selected');
		$(this).closest('.ccwps-radio-option').addClass('selected');
	});

	/* ---- Layout picker ---- */
	$(document).on('change', '.ccwps-layout-opt input[type="radio"]', function () {
		$('.ccwps-layout-opt').removeClass('active');
		$(this).closest('.ccwps-layout-opt').addClass('active');
	});

	/* =====================
	   SAVE SETTINGS
	   ===================== */
	$(document).on('click', '.ccwps-save-settings', function () {
		const $btn = $(this).prop('disabled', true).text(i18n.saving || 'Saving...');
		const data = {};

		$('#ccwps-settings-form').find('[name]').each(function () {
			const el  = $(this);
			const key = el.attr('name');
			if (!key) return;
			if (el.is(':checkbox')) {
				data[key] = el.is(':checked') ? '1' : '0';
			} else if (el.is(':radio')) {
				if (el.is(':checked')) data[key] = el.val();
			} else {
				data[key] = el.val();
			}
		});

		// WP color picker stores value separately
		$('.ccwps-color-picker').each(function () {
			const key = $(this).attr('name');
			if (key) data[key] = $(this).val();
		});

		ajaxPost('ccwps_save_settings', { settings: data }, function (res) {
			$btn.prop('disabled', false).text(i18n.saveSettings || 'Save settings');
			res.success ? showNotice(i18n.saved) : showNotice(i18n.error, 'error');
		});
	});

	/* =====================
	   ADMIN FLAG PICKER
	   ===================== */
	// Toggle dropdown — position fixed to escape sidebar overflow
	$(document).on('click', '.ccwps-flag-picker-current', function (e) {
		e.stopPropagation();
		const $picker   = $(this).closest('.ccwps-flag-picker');
		const $dropdown = $picker.find('.ccwps-flag-picker-dropdown');
		const isOpen    = $picker.hasClass('open');

		// Close any other open pickers
		$('.ccwps-flag-picker').removeClass('open');

		if (!isOpen) {
			// Position the dropdown using viewport coordinates
			const rect = this.getBoundingClientRect();
			$dropdown.css({
				top:   rect.bottom + 4,
				left:  rect.left,
				width: rect.width
			});
			$picker.addClass('open');
		}
	});

	// Close on outside click
	$(document).on('click', function (e) {
		if (!$(e.target).closest('.ccwps-flag-picker').length) {
			$('.ccwps-flag-picker').removeClass('open');
		}
	});

	// Select language
	$(document).on('click', '.ccwps-fp-option', function () {
		const lang    = $(this).data('lang');
		const $picker = $(this).closest('.ccwps-flag-picker');
		const current = $picker.data('current');
		if (lang === current) { $picker.removeClass('open'); return; }

		if (!confirm(i18n.confirmLangChange || 'Change admin language and apply frontend translations?')) {
			$picker.removeClass('open');
			return;
		}
		$picker.removeClass('open');

		ajaxPost('ccwps_save_admin_lang', { lang }, function (res) {
			if (res.success) {
				window.location.reload();
			} else {
				showNotice(i18n.error, 'error');
			}
		});
	});

	/* =====================
	   TRANSLATION LANG PRESETS
	   ===================== */
	$(document).on('click', '.ccwps-lang-btn', function () {
		const lang = $(this).data('lang');
		const preset = langPresets && langPresets[lang];
		if (!preset || !preset.strings) return;

		Object.entries(preset.strings).forEach(([key, value]) => {
			const $el = $('[data-lang-key="' + key + '"]');
			if ($el.length) $el.val(value);
		});

		$('.ccwps-lang-btn').removeClass('active');
		$(this).addClass('active');
		showNotice(i18n.langApplied);
	});

	/* =====================
	   CLEAR LOG
	   ===================== */
	$(document).on('click', '#ccwps-clear-log', function () {
		if (!confirm(i18n.confirmClear)) return;
		ajaxPost('ccwps_clear_log', {}, function (res) {
			if (res.success) { showNotice(i18n.logCleared); setTimeout(() => location.reload(), 1200); }
		});
	});

	/* =====================
	   RESET SETTINGS
	   ===================== */
	$(document).on('click', '#ccwps-reset-defaults', function () {
		if (!confirm(i18n.confirmReset)) return;
		ajaxPost('ccwps_reset_settings', {}, function (res) {
			if (res.success) { showNotice(i18n.resetDone); setTimeout(() => location.reload(), 1400); }
		});
	});

	/* =====================
	   COOKIE MANAGEMENT
	   ===================== */
	function openCookieModal(data = {}) {
		const isEdit = !!data.id;
		$('#ccwps-cookie-modal-title').text(isEdit ? (i18n.editCookie || 'Edit cookie') : (i18n.addCookie || 'Add cookie'));
		$('#ccwps-cookie-id').val(data.id || '');
		$('#c-name').val(data.name || '');
		$('#c-domain').val(data.domain || '');
		$('#c-expiration').val(data.expiration || '');
		$('#c-path').val(data.path || '/');
		$('#c-description').val(data.description || '');
		$('#c-category').val(data.category || 'necessary');
		$('#c-is-regex').prop('checked', !!+data.is_regex);
		$('#ccwps-cookie-modal').show();
	}

	$(document).on('click', '#ccwps-add-cookie', () => openCookieModal());
	$(document).on('click', '.ccwps-edit-cookie', function () { openCookieModal($(this).data('row')); });

	$(document).on('click', '#ccwps-save-cookie', function () {
		const name = $('#c-name').val().trim();
		if (!name) { alert(i18n.enterCookieName || 'Enter cookie name.'); return; }
		const d = {
			id: $('#ccwps-cookie-id').val(),
			name, domain: $('#c-domain').val(), expiration: $('#c-expiration').val(),
			path: $('#c-path').val(), description: $('#c-description').val(),
			category: $('#c-category').val(), is_regex: $('#c-is-regex').is(':checked') ? '1' : '',
		};
		ajaxPost('ccwps_save_cookie', d, function (res) {
			if (res.success) { $('#ccwps-cookie-modal').hide(); location.reload(); }
			else showNotice(res.data || i18n.error, 'error');
		});
	});

	$(document).on('click', '.ccwps-delete-cookie', function () {
		if (!confirm(i18n.confirmDelete)) return;
		ajaxPost('ccwps_delete_cookie', { id: $(this).data('id') }, function (res) {
			res.success ? location.reload() : showNotice(i18n.error, 'error');
		});
	});

	/* =====================
	   BLOCK MANAGEMENT
	   ===================== */
	function openBlockModal(data = {}) {
		const isEdit = !!data.id;
		$('#ccwps-block-modal-title').text(isEdit ? (i18n.editRule || 'Edit rule') : (i18n.addRule || 'Add rule'));
		$('#ccwps-block-id').val(data.id || '');
		$('#b-source').val(data.script_source || '');
		$('#b-category').val(data.category || 'analytics');
		$('#b-is-regex').prop('checked', !!+data.is_regex);
		$('#ccwps-block-modal').show();
	}

	$(document).on('click', '#ccwps-add-block', () => openBlockModal());
	$(document).on('click', '.ccwps-edit-block', function () { openBlockModal($(this).data('row')); });

	$(document).on('click', '#ccwps-save-block', function () {
		const source = $('#b-source').val().trim();
		if (!source) { alert(i18n.enterScriptSource || 'Enter script source.'); return; }
		const d = {
			id: $('#ccwps-block-id').val(), script_source: source,
			category: $('#b-category').val(), is_regex: $('#b-is-regex').is(':checked') ? '1' : '',
		};
		ajaxPost('ccwps_save_block', d, function (res) {
			if (res.success) { $('#ccwps-block-modal').hide(); location.reload(); }
			else showNotice(res.data || i18n.error, 'error');
		});
	});

	$(document).on('click', '.ccwps-delete-block', function () {
		if (!confirm(i18n.confirmDelete)) return;
		ajaxPost('ccwps_delete_block', { id: $(this).data('id') }, function (res) {
			res.success ? location.reload() : showNotice(i18n.error, 'error');
		});
	});

	/* =====================
	   MODAL CLOSE
	   ===================== */
	$(document).on('click', '.ccwps-modal-close', function () { $(this).closest('.ccwps-modal').hide(); });
	$(document).on('click', '.ccwps-modal', function (e) { if ($(e.target).hasClass('ccwps-modal')) $(this).hide(); });
	$(document).on('keydown', function (e) { if (e.key === 'Escape') $('.ccwps-modal:visible').hide(); });

	/* =====================
	   PREVIEW BANNER / MODAL
	   ===================== */
	$(document).on('click', '#ccwps-preview-banner', function () {
		const url = siteUrl + '?ccwps_preview=banner&t=' + Date.now();
		window.open(url, '_blank', 'width=1200,height=800');
	});

	$(document).on('click', '#ccwps-preview-modal', function () {
		const url = siteUrl + '?ccwps_preview=modal&t=' + Date.now();
		window.open(url, '_blank', 'width=1200,height=800');
	});

	/* =====================
	   COOKIE LIST PREVIEW (in admin modal)
	   ===================== */
	$(document).on('click', '#ccwps-preview-cookie-list', function () {
		const cookiesData = window.ccwpsAdmin.cookies || {};
		let html = '';

		const catLabels = {
			necessary: i18n.catNecessary,
			analytics: i18n.catAnalytics,
			targeting: i18n.catTargeting,
			preferences: i18n.catPreferences,
		};

		Object.entries(cookiesData).forEach(([cat, list]) => {
			if (!list.length) return;
			html += `<h3 style="margin:14px 0 8px;font-size:15px;">${catLabels[cat] || cat}</h3>`;
			html += `<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:13px;">
				<thead><tr>
					<th style="padding:8px 10px;text-align:left;border-bottom:2px solid #e5e7eb;background:#f9fafb;">${i18n.cookieColName || ''}</th>
					<th style="padding:8px 10px;text-align:left;border-bottom:2px solid #e5e7eb;background:#f9fafb;">${i18n.cookieColDomain || ''}</th>
					<th style="padding:8px 10px;text-align:left;border-bottom:2px solid #e5e7eb;background:#f9fafb;">${i18n.cookieColExpiration || ''}</th>
					<th style="padding:8px 10px;text-align:left;border-bottom:2px solid #e5e7eb;background:#f9fafb;">${i18n.cookieColDescription || ''}</th>
				</tr></thead><tbody>`;
			list.forEach(ck => {
				html += `<tr>
					<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;"><code>${ck.name}</code></td>
					<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;">${ck.domain || '—'}</td>
					<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;">${ck.expiration || '—'}</td>
					<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;">${ck.desc || '—'}</td>
				</tr>`;
			});
			html += '</tbody></table></div>';
		});

		if (!html) html = '<p style="padding:16px;color:#6b7280;">' + (i18n.cookieListEmpty || 'No cookies are declared.') + '</p>';
		$('#ccwps-cookie-list-preview').html(html);
		$('#ccwps-cookie-list-modal').show();
	});

	/* =====================
	   COPY SHORTCODE
	   ===================== */
	$(document).on('click', '.ccwps-copy-btn', function () {
		const text = $(this).data('copy');
		navigator.clipboard ? navigator.clipboard.writeText(text) : (() => {
			const ta = document.createElement('textarea');
			ta.value = text;
			document.body.appendChild(ta);
			ta.select();
			document.execCommand('copy');
			document.body.removeChild(ta);
		})();
		const $btn = $(this);
		$btn.text(i18n.copied || 'Copied!');
		setTimeout(() => $btn.text(i18n.copy || 'Copy'), 2000);
	});

	/* =====================
	   FILE DROP (Import)
	   ===================== */
	const $drop  = $('#ccwps-file-drop');
	const $input = $('#ccwps-import-file');
	const $btn   = $('#ccwps-import-btn');
	const $name  = $('#ccwps-file-name');

	if ($drop.length) {
		function handleFile(file) {
			if (!file) return;
			$name.text(file.name);
			$drop.addClass('has-file');
			$btn.prop('disabled', false);
		}
		$input.on('change', function () { handleFile(this.files[0]); });
		$drop.on('dragover dragenter', function (e) { e.preventDefault(); $(this).addClass('drag-over'); })
		     .on('dragleave drop',    function (e) {
			e.preventDefault();
			$(this).removeClass('drag-over');
			if (e.type === 'drop') {
				$input[0].files = e.originalEvent.dataTransfer.files;
				handleFile(e.originalEvent.dataTransfer.files[0]);
			}
		});
	}

}(jQuery));

/* ============================================
   CUSTOM ICON UPLOAD (Media Library)
   ============================================ */
(function ($) {

	/* Show/hide custom icon row based on icon type select */
	$(document).on('change', '.ccwps-icon-type-select', function () {
		var val = $(this).val();
		if (val === 'custom') {
			$('#ccwps-custom-icon-row').show();
		} else {
			$('#ccwps-custom-icon-row').hide();
		}
	});

	var mediaFrame = null;

	$(document).on('click', '#ccwps-icon-upload-btn', function (e) {
		e.preventDefault();

		// Reuse existing frame if open
		if (mediaFrame) {
			mediaFrame.open();
			return;
		}

		// Create WP media frame
		mediaFrame = wp.media({
			title:    i18n.mediaTitle || 'Select custom icon',
			button:   { text: i18n.mediaButton || 'Use this image' },
			multiple: false,
			library:  { type: [ 'image' ] }
		});

		mediaFrame.on('select', function () {
			var attachment = mediaFrame.state().get('selection').first().toJSON();
			var url = attachment.url;

			// Update hidden input
			$('#icon_custom_url').val(url);

			// Update preview
			var $preview = $('#ccwps-icon-preview');
			$preview
				.removeClass('ccwps-icon-preview--empty')
				.html('<img src="' + url + '" alt="' + (i18n.customIconAlt || 'Custom icon') + '">');

			// Show remove button
			$('#ccwps-icon-remove-btn').show();
		});

		mediaFrame.open();
	});

	$(document).on('click', '#ccwps-icon-remove-btn', function (e) {
		e.preventDefault();
		$('#icon_custom_url').val('');
		$('#ccwps-icon-preview')
			.addClass('ccwps-icon-preview--empty')
			.html('<span>' + (i18n.noImage || 'No image') + '</span>');
		$(this).hide();
		mediaFrame = null;
	});

}(jQuery));
