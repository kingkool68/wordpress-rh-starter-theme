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
	browserAutoOpen: false,
	injectChanges: true,

	// Style options.
	styleSRC: './assets/scss/**/*.scss', // Path to main .scss file.
	styleDestination: './assets/css/', // Path to place the compiled CSS file. Default set to root folder.
	outputStyle: 'compressed', // Available options → 'compact' or 'compressed' or 'nested' or 'expanded'
	errLogToConsole: true,
	precision: 10,

	// JS Custom options.
	jsCustomSRC: './assets/js/**/*.src.js', // Path to JS custom scripts folder.
	jsCustomDestination: './assets/js/', // Path to place the compiled JS custom scripts file.
	jsCustomFile: 'custom', // Compiled JS custom file name. Default set to custom i.e. custom.js.

	// Images options.
	// imagesSRC: './assets/img/raw/**.{png,jpg,gif,svg}', // Source folder of images which should be optimized.
	imgSRC: './assets/img/raw/*', // Source folder of images which should be optimized and watched.
	imgDST: './assets/img/', // Destination folder of optimized images. Must be different from the imagesSRC folder.

	// Watch files paths.
	styleWatchFiles: './assets/scss/**/*.scss', // Path to all *.scss files inside css folder and inside them.
	customJSWatchFiles: './assets/js/**/*.src.js', // Path to all custom JS files.
	projectPHPWatchFiles: './**/*.php', // Path to all PHP files.

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

	/*
	// Translation options.
	textDomain: '', // Your textdomain here.
	translationFile: '', // Name of the transalation file.
	translationDestination: './languages', // Where to save the translation files.
	packageName: '', // Package name.
	bugReport: '', // Where can users report bugs.
	lastTranslator: '', // Last translator Email ID.
	team: '', // Team's Email ID.
	*/
	// Browsers you care about for autoprefixing. Browserlist https://github.com/ai/browserslist
	BROWSERS_LIST: [
		'last 10 versions',
		'ie >= 6',
		'Android >= 2.3'
	]

	// STOP Editing Project Variables.
};
