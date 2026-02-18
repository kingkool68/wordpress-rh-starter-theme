/**
 * Get Google Analytics event values from a given element
 *
 * @return  {object}  The event values
 */
export function getAnalyticsEventValuesFromElement(el) {
	var output = {
		category: null,
		action: null,
		label: null,
		value: null,
	};

	if (!el.hasAttribute || !el.hasAttribute('data-ga-category')) {
		return output;
	}

	output.category = el.getAttribute('data-ga-category');
	output.action = el.getAttribute('data-ga-action') || el.href;
	output.label = el.getAttribute('data-ga-label');
	output.value = parseInt(el.getAttribute('data-ga-value')) || null;

	return output;
}

/**
 * Wait for a variable to be set in the global window scope and fire a callback
 *
 * @param {string} varialbe - The variable to wait for
 * @param {function} callback - The callback to fire once the variable is found
 * @param {int} delay - The amount of time to wait before each iteration
 * @param {int} maxIterations - The number of maximum iterations to try and find the variable
 */
export function waitFor(variable, callback, delay = 240, maxIterations = 10) {
	var iteration = 0;
	var interval = setInterval(
		function () {
			if (window[variable]) {
				clearInterval(interval);
				callback();
			}

			iteration++;
			if (iteration > maxIterations) {
				clearInterval(interval);
			}
		},
		delay,
		iteration
	);
}
