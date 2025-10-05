/**
 * Customize the project in the config.js file
 */
import config from './gulp-config.js';

/**
 * Load gulp plugins and passing them semantic names.
 */
import gulp from 'gulp'; // Gulp of-course

// CSS related plugins.
import * as dartSass from 'sass'; // Gulp pluign for Sass compilation
import gulpSass from 'gulp-sass';
var sass = gulpSass(dartSass);

import csso from 'gulp-csso'; // CSS optimixations and minimizing
import autoprefixer from 'autoprefixer'; // Autoprefixing magic
import postcss from 'gulp-postcss'; // Run PostCSS tasks
import sortMediaQueries from 'postcss-sort-media-queries'; // Merge similiar media queries

// JS related plugins.
import { createGulpEsbuild } from 'gulp-esbuild';
var esbuild = createGulpEsbuild({
	pipe: true,
});

// Utility related plugins.
import fs from 'fs'; // The filesystem for manipulating files
import { exec } from 'child_process';
import plumber from 'gulp-plumber'; // Prevent pipe breaking caused by errors from gulp plugins
import sourcemaps from 'gulp-sourcemaps'; // For generating sourcemaps
import rename from 'gulp-rename'; // Renames files E.g. style.css -> style.min.css
import lineec from 'gulp-line-ending-corrector'; // Consistent Line Endings for non UNIX systems
import log from 'fancy-log'; // Fancy logging
// import debug from 'gulp-debug'; // For debugging Gulp filenames and paths
import { deleteSync } from 'del'; // Handles deleting files and directories
import prettier from 'gulp-prettier'; // For linting and fixing files

/**
 * Helper to log success messages
 *
 * @param   {string}  message  The success message to display
 *
 * @return  {string}           The pretty formatted success message
 */
function logSuccess(message) {
	log('✅✅✅✅ ' + message);
}

/**
 * Helper to log serror messages
 *
 * @param   {string}  message  The error message to display
 *
 * @return  {string}           The pretty formatted error message
 */
function logError(message) {
	log('❌❌❌❌ ' + message);

	// Make an audible beep
	if (process.platform === 'darwin') {
		// Play a beep sound on MacOS
		exec("osascript -e 'beep'");
	} else {
		process.stdout.write('\x07');
	}
}

/**
 * Delete previously compiled files
 */
gulp.task('clean', function () {
	deleteSync(config.filesToClean);
	return gulp.src('.').on('end', () => logSuccess('🧹'));
});

/**
 * Compile Sass and generate CSS files
 */
gulp.task('styles', function () {
	let hadError = false;
	return (
		gulp
			.src(config.styleSRC)
			.pipe(
				plumber({
					errorHandler: function (err) {
						hadError = true;
						logError(err.messageFormatted);
						this.emit('end'); // End stream if error is found
					},
				})
			)
			// .pipe(sourcemaps.init())
			.pipe(
				sass({
					outputStyle: 'expanded',
					precision: config.precision,
					loadPaths: config.loadPaths,
				})
			)
			.pipe(
				postcss([
					autoprefixer({
						overrideBrowserslist: config.BROWSERS_LIST,
					}),
					sortMediaQueries(),
				])
			)
			.pipe(rename({ suffix: '.min' }))
			.pipe(csso())
			.pipe(lineec())
			// .pipe(sourcemaps.write('.'))
			.pipe(gulp.dest(config.styleDestination))
			.on('finish', () => {
				if (!hadError) {
					logSuccess('🎨');
				}
			})
	);
});

/**
 * See if a compiled CSS file contains certain patterns we know are errors
 */
gulp.task(
	'styles:test',
	gulp.series('clean', 'styles', function runStyleTests(done) {
		const css = fs.readFileSync('assets/css/rh.min.css', 'utf8');
		const checks = [
			{
				pattern: 'svg + xml',
				message: 'There should be no spaces around + in "svg+xml"',
			},
			{
				pattern: 'rem+3);',
				message: 'There should be spaces around + in clamp() functions',
			},
		];

		checks.forEach(({ pattern, message }) => {
			if (css.includes(pattern)) {
				throw new Error(logError(message));
			}
		});

		return gulp.src('.').on('end', () => logSuccess('🧪'));
	})
);

/**
 * Compile JavaScript files using esbuild
 */
gulp.task('scripts', function () {
	let hadError = false;
	return gulp
		.src(config.scriptSRC)
		.pipe(
			plumber({
				errorHandler: function (err) {
					hadError = true;
					logError(err.message);
					this.emit('end'); // End stream if error is found
				},
			})
		)
		.pipe(
			esbuild({
				bundle: true,
				minify: true,
				sourcemap: true,
				target: ['es2015'],
				loader: { '.js': 'js' },
			})
		)
		.pipe(lineec()) // Consistent Line Endings for non UNIX systems
		.pipe(
			rename(function (path) {
				if (path.extname === '.js') {
					path.basename = path.basename.replace('.src', '');
				}
				return path;
			})
		)
		.pipe(gulp.dest(config.scriptDest))
		.on('finish', () => {
			if (!hadError) {
				logSuccess('🛠️');
			}
		});
});

/**
 * Copy files from a directery like node_modules to another location
 */
gulp.task('setup', function (done) {
	config.filesToMove.forEach(function (file) {
		if (!file.rename) {
			file.rename = {};
		}
		gulp.src(file.src).pipe(rename(file.rename)).pipe(gulp.dest(file.dest));
	});
	done();
});

/**
 * Run Prettier in "check" mode against all source files.
 *
 * This task verifies that JavaScript and stylesheet files conform
 * to Prettier’s formatting rules without making any changes.
 * If formatting issues are found, the task will fail.
 */
gulp.task('prettier', function () {
	return gulp
		.src([...config.scriptSRC, ...config.styleSRC])
		.pipe(prettier.check());
});

/**
 * Run Prettier in "write" mode to automatically fix formatting issues.
 *
 * This task reformats JavaScript and stylesheet files according
 * to Prettier’s rules and writes the corrected files back to disk.
 */
gulp.task('prettier:fix', function () {
	return gulp
		.src([...config.scriptSRC, ...config.styleSRC])
		.pipe(prettier())
		.pipe(gulp.dest('.'));
});

/**
 * Watches for file changes and runs specific tasks
 */
gulp.task(
	'default',
	gulp.parallel('clean', 'styles', 'scripts', function watchFiles() {
		gulp.watch(config.styleWatchFiles, gulp.parallel('styles')); // Reload on SCSS file changes.
		gulp.watch(config.scriptWatchFiles, gulp.series('scripts')); // Reload on scripts file changes.
	})
);
