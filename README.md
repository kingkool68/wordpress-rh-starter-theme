# Russell Heimlich's Starter WordPress Theme
## Getting Started

Install Composer dependencies for PHPCS and code linting: `composer install`

Install node dependencies like Gulp: `npm install`

Start Gulp wich will watch for changes and recompile Sass and JavaScript: `npm run start`

## Templating

Rendering markup is handled by passing data to [Twig](https://twig.symfony.com/) templates via [Sprig](https://github.com/kingkool68/sprig). WordPress [escaping functions](https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/) are available as Twig filters.

## CSS

Sass files get compiled to `rh.min.css` which is heavily optimized for performant delivery. See the `class-rh-scripts-and-styles.php` file for helper methods.

## JavaScript

JavaScript files wil lbe uglified and minified in the `/wp-content/themes/rh/assets/js/` directory. Scripts must end with the extension `.src.js` in order for them to be compiled. Scripts without the `.src.js` suffix will be deleted by the Gulp watch process when they are recompiled. When in production you should call the uglified and minified version. See the `class-rh-scripts-and-styles.php` file for helper methods.

## Styleguide

This theme comes with a styleguide built in to the theme. Visit `/styleguide/` to access the files in the `/styleguide/` directory of the theme. `/styleguide/colors/` corresponds to the `/styleguide/colors.php` in the theme. Be sure to add any components or modules to the styleguide to make quality control easier.
