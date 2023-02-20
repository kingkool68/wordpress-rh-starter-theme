/**
 * Configuration.
 *
 * Project Configuration for gulp tasks.
 *
 * In paths you can add <<glob or array of globs>>. Edit the variables as per your project requirements.
 */

module.exports = {

	// START Editing Project Variables.
	// Project options.
	project: 'Russell Heimlich', // Project Name.
	projectURL: '', // Local project URL of your already running WordPress site. Could be something like wpgulp.local or localhost:8888.
	productURL: './', // Theme/Plugin URL. Leave it like it is, since our gulpfile.js lives in the root folder.

	// Style options.
	styleSRC: ['./assets/scss/**/*.scss', './blocks/**/*.scss'], // Path to main .scss file.
	styleDestination: './assets/css/', // Path to place the compiled CSS file. Default set to root folder.
	outputStyle: 'compressed', // Available options → 'compact' or 'compressed' or 'nested' or 'expanded'
	errLogToConsole: true,
	precision: 10,
	includePaths: ['./assets/scss'], // Tell Sass where to look for @import statements

	// JS Custom options.
	scriptSRC: ['./**/*.src.js', '!./vendor/', '!./node_modules/' ], // Globs of scripts to process.
	scriptBase: './', // Path where the globs are considered to start.
	scriptDest: './', // Path where we save the scripts back to.

	// Watch files paths.
	styleWatchFiles: ['./assets/scss/**/*.scss', './blocks/**/*.scss'], // *.scss files to watch changes and recompile
	scriptWatchFiles: ['./assets/js/**/*.src.js', './blocks/**/*.src.js'], // *.src.js files to watch changes and recompile

	filesToClean: ['./assets/css/', './assets/js/**/*.js', '!./assets/js/', '!./assets/js/**/*.src.js'],

	// Dependencies to move into place
	// NOTE: rename is object of options to pass to gulp-rename
	filesToMove: [

		/*
		{
			src: './node_modules/flickity/dist/flickity.css',
			dest: './assets/scss/vendor/',
			rename: {
				basename: '_flickity',
				extname: '.scss'
			}
		}, {
			src: './node_modules/flickity/dist/flickity.pkgd.js',
			dest: './assets/js/vendor/',
			rename: {
				basename: 'flickity.src',
				extname: '.js'
			}
		}
		*/
	],

	// Browsers you care about for autoprefixing. Browserlist https://github.com/ai/browserslist
	BROWSERS_LIST: [
		'last 10 versions',
		'ie >= 6',
		'Android >= 2.3'
	]
};
