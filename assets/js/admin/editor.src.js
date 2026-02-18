(function ($) {
	if ('object' !== typeof acf) {
		return;
	}
	// Allow svgs in icon select fields. ACF < 2.6.8
	acf.add_filter(
		'select2_args',
		function (args, $select, settings, field, instance) {
			// Taken from `acf-input.js`, just removed escaping from `$selection.html(acf.strEscape(selection.text));`.
			args.templateSelection = function (selection) {
				var $selection = $('<span class="acf-selection"></span>');
				$selection.html(selection.text);
				$selection.data('element', selection.element);
				return $selection;
			};

			return args;
		}
	);

	// Allow svgs in icon select fields. ACF >= 2.6.8.
	acf.add_filter(
		'select2_escape_markup',
		function (
			escaped_value,
			original_value,
			$select,
			settings,
			field,
			instance
		) {
			return original_value;
		}
	);
})(jQuery);
