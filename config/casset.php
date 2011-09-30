<?php

/**
 * Casset: Convenient asset library for FuelPHP.
 *
 * @package    Casset
 * @version    v1.9
 * @author     Antony Male
 * @license    MIT License
 * @copyright  2011 Antony Male
 * @link       http://github.com/canton7/fuelphp-casset
 */

return array(

	/**
	 * An array of paths that will be searched for assets.
	 * Each path is assigned a name, which is used when referring to that asset.
	 * See the js() and css() docs for more info.
	 * Each asset is a RELATIVE path from the base_url WITH a trailing slash.
	 * There must be an entry with the key 'core'. This is used when no path
	 * is specified.
	 *
	 * array(
	 *		'core' => 'assets/'
	 * )
	 *
	 * You can also choose to override the js_dir, css_dir and/or img_dir config
	 * options on a per-path basis. You can override just one dir, two, or all
	 * of them.
	 * In this case, the syntax is
	 * array (
	 *		'some_key' => array(
	 *			'path' => 'more_assets/',
	 *			'js_dir' => 'javascript/',
	 *			'css_dir' => 'styles/'
	 *			'img_dir' => 'images/',
	 *		),
	 * )
	 */
	'paths' => array(
		'core' => 'assets/',
	),

	/**
	 * URL to your Fuel root. Typically this will be your base URL,
	 * WITH a trailing slash:
	 *
	 * Config::get('base_url')
	 */
	'url' => Config::get('base_url'),

	/**
	 * Asset Sub-folders
	 *
	 * Names for the js and css folders (inside the asset path).
	 *
	 * Examples:
	 *
	 * js/
	 * css/
	 * img/
	 *
	 * This MUST include the trailing slash ('/')
	 */
	'js_dir' => 'js/',
	'css_dir' => 'css/',
	'img_dir' => 'img/',

	/**
	 * When minifying, the minified, combined files are cached.
	 * This value specifies the location of those files, relative to public/
	 *
	 * This MUST include the trailing slash ('/')
	 */
	'cache_path' => 'assets/cache/',

	/**
	 * Note the following with regards to combining / minifying files:
	 * Combine and minify:
	 *   Files are minified (or the minified form used, if given), and combined
	 *   into a single cache file.
	 * Combine and not minify:
	 *   Non-minified versions of files are combined into a single cache file.
	 * Not combine and minify:
	 *   Minified versions of files are linked to, if given. Otherwise the non-
	 *   minified versions are linked to.
	 *   NOTE THIS IS POTENTIALLY UNEXPECTED BEHAVIOUR, but makes sense when you
	 *   take remote assets into account.
	 * Not combine and not minify:
	 *   Non-minified versions of files are linked to.
	 */

	/**
	 * Whether to minify files.
	 */
	'min' => true,

	/**
	 * Whether to combine files
	 */
	'combine' => true,

	/*
	 * Whether to version images or not.
	 * NOTE: If this is TRUE then you need to include the following in your
	 * .htaccess file (or equivalent).
	 *
	 * # http://example.com/assets/img/test.1298892196.jpeg
	 * RewriteRule ^(.*)\/(.+)\.([0-9]+)\.(js|css|jpg|jpeg|gif|png|php)$ $1/$2.$4 [L]
	 *
	 * This has to go above the index.php removal lines if you are using that
	 * feature. If you are it should look something like this:
	 *
		<IfModule mod_rewrite.c>
			RewriteBase /

			# http://example.com/assets/img/test.1298892196.jpeg
			RewriteRule ^(.*)\/(.+)\.([0-9]+)\.(js|css|jpg|jpeg|gif|png)$ $1/$2.$4 [L]

		    RewriteCond %{REQUEST_FILENAME} !-f
		    RewriteCond %{REQUEST_FILENAME} !-d

		    RewriteRule ^(.*)$ index.php/$1 [L]
		</IfModule>
	 *
	*/
	'version_images' => false,

	/**
	 * When minifying, whether to show the files names in each combined
	 * file in a comment before the tag to the file.
	 */
	'show_files' => true,

	/**
	 * When minifying, whether to put comments in each minified file showing
	 * the origin location of each part of the file.
	 */
	'show_files_inline' => false,

	/**
	 * Groups of scripts.
	 * You can predefine groups of js and css scripts which can be enabled/disabled
	 * and rendered.
	 * There isn't much flexibility in this syntax, and no error-checking is performed,
	 * so please be careful!
	 *
	 * The groups array follows the following structure:
	 * array(
	 *    'js' => array(
	 *       'group_name' => array(
	 *          'files' => array(
	 *             array('file1.js', 'file1.min.js'),
	 *             'file2.js'
	 *          ),
	 *          'enabled' => true,
	 *          'min' => false,
	 *       ),
	 *       'group_name_2' => array(.....),
	 *    ),
	 *    'css' => aarray(
	 *       'group_name' => array(
	 *          'files' => array(
	 *             array('file1.css', 'file1.min.css'),
	 *             'file2.css',
	 *          ),
	 *          'enabled' => true,
	 *       ),
	 *       'group_name_2' => array(.....),
	 *    ),
	 * ),
	 *
	 * - 'js' and 'css' are special keys, used by functions like render_js and
	 *    render_css. Either can happily be omitted.
	 * - 'group_name' is a user-defined group name. Files can be added to a
	 *    particular group using the third argument of css() or js().
	 *    Similarly, individual groups can be rendered by passing the group
	 *    name to render_css() or render_js().
	 *    Another point to note is that each group is minified into its own
	 *    distinct cache file. This is a compromise between allowing the
	 *    browser to cache files, and flooding it with too many files.
	 * - 'files' is a list of the files present in the group.
	 *    Each file can either be defined by a string, or by an array of 2 elements.
	 *    If the string form is used, the file will be minified using an internal
	 *    library when 'min' = true.
	 *    If the array form is used, the second element in the array is used
	 *    when 'min' = true. This is useful when a library also provided a minified
	 *    version of itself (eg jquery).
	 * - 'enabled': whether the group will be rendered when render_css() or
	 *    render_js() is called.
	 * - 'min: an optional key, allowing you to override the global 'min' config
	 *    key on a per-group basis. If null or not specified, the 'min' config#
	 *    key will be used.
	 *    Using this,
	 */
	'groups' => array(
	),
);

/* End of file config/casset.php */