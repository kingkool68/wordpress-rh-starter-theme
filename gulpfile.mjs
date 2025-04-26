/**
 * Gulpfile.
 *
 * Gulp with WordPress.
 *
 * Implements:
 *      1. Live reloads browser with BrowserSync.
 *      2. CSS: Sass to CSS conversion, error catching, Autoprefixing, Sourcemaps,
 *         CSS minification, and Merge Media Queries.
 *      3. JS: Concatenates & uglifies Vendor and Custom JS files.
 *      4. Images: Minifies PNG, JPEG, GIF and SVG images.
 *      5. Watches files for changes in CSS or JS.
 *      6. Watches files for changes in PHP.
 *      7. Corrects the line endings.
 *
 * @author Ahmad Awais (https://github.com/ahmadawais)
 *         Contributors: https://AhmdA.ws/WPGContributors
 * @version 1.9.3
 */

/**
 * Load Config.
 *
 * Customize your project in the config.js file
 */
import config from './gulp-config.js';

/**
 * Load Plugins.
 *
 * Load gulp plugins and passing them semantic names.
 */
import gulp from 'gulp'; // Gulp of-course

// CSS related plugins.
import dartSass from 'sass'; // Gulp pluign for Sass compilation.
import gulpSass from 'gulp-sass';
var sass = gulpSass(dartSass);
import minifycss from 'gulp-uglifycss' // Minifies CSS files.
import autoprefixer from 'gulp-autoprefixer'; // Autoprefixing magic.
import jmq from 'gulp-join-media-queries'; // For combining media queries

// JS related plugins.
import uglify from 'gulp-uglify'; // Minifies JS files
import babel from 'gulp-babel'; // Compiles ESNext to browser compatible JS.

import { createGulpEsbuild } from 'gulp-esbuild';
var esbuild = createGulpEsbuild({
	pipe: true,
});

// Utility related plugins.
import rename from 'gulp-rename'; // Renames files E.g. style.css -> style.min.css
import lineec from 'gulp-line-ending-corrector'; // Consistent Line Endings for non UNIX systems. Gulp Plugin for Line Ending Corrector (A utility that makes sure your files have consistent line endings)
import filter from 'gulp-filter'; // Enables you to work on a subset of the original files by filtering them using globbing.
import notify from 'gulp-notify'; // Sends message notification to you
import remember from 'gulp-remember'; // Adds all the files it has ever seen back into the stream
import plumber from 'gulp-plumber'; // Prevent pipe breaking caused by errors from gulp plugins
// import debug from 'gulp-debug'; // For debugging Gulp filenames and paths
import { deleteSync } from 'del'; // Handles deleting files and directories
/**
 * Task: `styles`.
 *
 * Compiles Sass, Autoprefixes it and Minifies CSS.
 *
 * This task does the following:
 *    1. Gets the source scss file
 *    2. Compiles Sass to CSS
 *    3. Writes Sourcemaps for it
 *    4. Autoprefixes it and generates style.css
 *    5. Renames the CSS file with suffix .min.css
 *    6. Minifies the CSS file and generates style.min.css
 */
gulp.task('styles', function () {
	return gulp
		.src(config.styleSRC)
		// .pipe( sourcemaps.init() )
		.pipe(
			sass({
				errLogToConsole: config.errLogToConsole,
				outputStyle: config.outputStyle,
				precision: config.precision,
				loadPaths: config.loadPaths,
			})
		)
		.on('error', sass.logError)
		.pipe(autoprefixer(config.BROWSERS_LIST))
		.pipe(filter('**/*.css')) // Filtering stream to only css files
		.pipe(jmq({ log: true })) // Merge Media Queries only for .min.css version.
		.pipe(rename({ suffix: '.min' }))
		.pipe(minifycss())
		// .pipe( sourcemaps.write( './' ) )
		.pipe(lineec()) // Consistent Line Endings for non UNIX systems.
		.pipe(gulp.dest(config.styleDestination))
		.pipe(notify({ message: 'TASK: "styles" Completed! 💯', onLast: true }));
});

/**
 * Task: `scripts`.
 *
 * Compile and uglify JavaScript files.
 *
 * This task does the following:
 *     1. Gets the source folder for JS custom files
 *     2. Concatenates all the files and generates custom.js
 *     3. Renames the JS file with suffix .min.js
 *     4. Uglifes/Minifies the JS file and generates custom.min.js
 */
gulp.task('scripts', function () {
	return gulp
		.src(config.scriptSRC, {
			since: gulp.lastRun('scripts'), // Only run on changed files.
			base: config.scriptBase,
		})
		.pipe(
			plumber({
				errorHandler: function (err) {
					notify.onError('Error: <%= error.message %>')(err);
					this.emit('end'); // End stream if error is found
				}
			})
		)
		.pipe(
			esbuild({
				bundle: true,
				loader: { '.js': 'js' }
			})
		)
		.pipe(
			babel({
				presets: [
					[
						'@babel/preset-env', // Preset to compile your modern JS to ES5.
						{
							targets: { browsers: config.BROWSERS_LIST } // Target browser list to support.
						}
					]
				]
			})
		)
		.pipe(
			rename(function (path) {
				path.basename = path.basename.replace('.src', '');
				return path;
			})
		)
		.pipe(remember('scripts')) // Bring all files back to stream
		.pipe(uglify())
		.pipe(lineec()) // Consistent Line Endings for non UNIX systems.
		.pipe(gulp.dest(config.scriptDest))
		.pipe(notify({ message: 'TASK: "scripts" Completed! 💯', onLast: true }));
});

gulp.task('clean', function () {
	var deletedPaths = deleteSync(config.filesToClean);
	return gulp
		.src('.')
		.pipe(notify({ message: 'TASK: "clean" Completed! 💯', onLast: true }));
});

gulp.task('setup', function () {
	config.filesToMove.forEach(function (file) {
		if (!file.rename) {
			file.rename = {};
		}
		gulp
			.src(file.src)
			.pipe(

				rename(file.rename)
			)
			.pipe(gulp.dest(file.dest));
	});
	return gulp
		.src('.')
		.pipe(notify({ message: 'TASK: "setup" Completed! 💯', onLast: true }));
});

/**
 * Watch Tasks.
 *
 * Watches for file changes and runs specific tasks.
 */
gulp.task(
	'default',
	gulp.parallel(
		'clean',
		'styles',
		'scripts',
		function watchFiles() {
			gulp.watch(config.styleWatchFiles, gulp.parallel('styles')); // Reload on SCSS file changes.
			gulp.watch(config.scriptWatchFiles, gulp.series('scripts')); // Reload on scripts file changes.
		}
	)
);
