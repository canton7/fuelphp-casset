<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * This library provides an alternative to the built-in Asset library.
 * This library has been based on the original Asset library, although
 * extensive modifications and additions have been made.
 *
 * @package    Casset
 * @version    1.0
 * @author     Antony Male
 * @license    MIT License
 * @copyright  2011 Antony Male
 * @link       http://github.com/canton7/fuelphp-casset
 */


class Casset {

	/**
	 * @var array Array of paths in which the css, js, img directory structure
	 *            can be found, relative to $asset_url
	 */
	protected static $asset_paths = array();

	/**
	 * @var string The URL to be prepanded to all assets.
	 */
	protected static $asset_url = '/';

	/**
	 * @var array The folders in which css, js, and images can be found.
	 */
	protected static $folders = array(
		'css' => 'css/',
		'js' => 'js/',
		'img' => 'img/',
	);

	/**
	 * @var string The directory, relative to public/, where cached minified failes
	 *             are stored.
	 */
	protected static $cache_path = 'assets/cache/';

	/**
	 * @var array Holds groups of assets. Is documenented fully in the config file.
	 */
	protected static $groups = array(
		'css' => array('global' => array('files' => array(), 'enabled' => true)),
		'js' => array('global' => array('files' => array(), 'enabled' => true)),
	);

	/**
	 * @var array Holds inline js and css.
	 */
	protected static $inline_assets = array(
		'css' => array(),
		'js' => array(),
	);

	/**
	 * @var bool Whether to minfy.
	 */
	protected static $min = true;

	/**
	 * @var bool Whether to show comments above the <script>/<link> tags showing
	 *           which files have been minified into that file.
	 */
	protected static $show_files = false;

	/**
	 * @var bool Whether to show comments inside minified files showing which
	 *           original file is where.
	 */
	protected static $show_files_inline = false;

	/**
	 * @var bool Wether we've been initialized.
	 */
	public static $initialized = false;

	/**
	* Loads in the config and sets the variables
	*/
	public static function _init()
	{
		// Prevent multiple initializations
		if (static::$initialized)
		{
			return;
		}

		Config::load('casset', true);

		$paths = Config::get('casset.paths', array('assets/'));

		foreach($paths as $path)
		{
			static::add_path($path);
		}

		static::$asset_url = Config::get('casset.url', Config::get('base_url'));

		static::$folders = array(
			'css' => Config::get('casset.css_dir', static::$folders['css']),
			'js' => Config::get('casset.js_dir', static::$folders['js']),
			'img' => Config::get('casset.img_dir', static::$folders['img']),
		);

		static::$cache_path = Config::get('casset.cache_path', static::$cache_path);

		$group_sets = Config::get('casset.groups', array());

		foreach ($group_sets as $group_type => $groups)
		{
			foreach ($groups as $group_name => $group)
			{
				static::add_group($group_type, $group_name, $group['files'], $group['enabled']);
			}
		}

		static::$min = Config::get('casset.min', static::$min);

		static::$show_files = Config::get('casset.show_files', static::$show_files);
		static::$show_files_inline = Config::get('casset.show_files', static::$show_files_inline);

		static::$initialized = true;
	}

	/**
	 * Adds a path to the asset paths array.
	 *
	 * @param string $path the path to add.
	 */
	public static function add_path($path)
	{
		array_unshift(static::$asset_paths, str_replace('../', '', $path));
	}

	/**
	 * Removes a path from the asset paths array.
	 *
	 * @param string $path the path to remove.
	 */
	public static function remove_path($path)
	{
		if (($key = array_search(str_replace('../', '', $path), static::$asset_paths)) !== false)
		{
			unset(static::$asset_paths[$key]);
		}
	}

	/**
	 * Adds a group of assets. If a group of this name exists, it will be
	 * overwritten.
	 *
	 * @param string $group_type 'js' or 'css'
	 * @param string $group_name The name of the group
	 * @param array $files The files to add to the group. Takes the form
	 *        array('file1', array('file2', 'file2.min'))
	 * @param bool $enabled Whether the group is enabled. Enabled groups will be
	 *        rendered with render_js / render_css
	 */
	public static function add_group($group_type, $group_name, $files, $enabled = true)
	{
		foreach ($files as &$file)
		{
			if (!is_array($file))
				$file = array($file, false);
		}
		static::$groups[$group_type][$group_name] = array(
			'files' => $files,
			'enabled' => $enabled,
		);
	}

	/**
	 * Searches the asset paths to locate a file.
	 * Throws an exception if the asset can't be found.
	 *
	 * @param string $file The name of the asset to search for
	 * @param string $asset_type 'css', 'js' or 'img'
	 * @return string The path to the asset, relative to $asset_url
	 */
	public function find_file($file, $asset_type)
	{
		if (strpos($file, '//') === false)
		{
			$folder = static::$folders[$asset_type];
			$file = ltrim($file, '/');

			foreach (static::$asset_paths as $path)
			{
				if (is_file($path.$folder.$file))
				{
					return $path.$folder.$file;
				}
			}
			throw new Fuel_Exception('Coult not find asset: '.$file);
		}
		else
		{
			return $file;
		}
	}

	/**
	 * Enables both js and css groups of the given name.
	 *
	 * @param string $group The group to enable.
	 */
	public static function enable($group)
	{
		static::asset_enabled('js', $group, true);
		static::asset_enabled('css', $group, true);
	}

	/**
	 * Disables both js and css groups of the given name.
	 *
	 * @param string $group The group to disable.
	 */
	public static function disable($group)
	{
		static::asset_enabled('js', $group, false);
		static::asset_enabled('css', $group, false);
	}

	/**
	 * Enable a group of javascript assets.
	 *
	 * @param string $group The group to enable.
	 */
	public static function enable_js($group)
	{
		static::asset_enabled('js', $group, true);
	}

	/**
	 * Disable a group of javascript assets.
	 *
	 * @param string $group The group to disable.
	 */
	public static function disable_js($group)
	{
		static::asset_enabled('js', $group, false);
	}

	/**
	 * Enable a group of css assets.
	 *
	 * @param string $group The group to enable.
	 */
	public static function enable_css($group)
	{
		static::asset_enabled('css', $group, true);
	}

	/**
	 * Disable a group of css assets.
	 *
	 * @param string $group The group to disable.
	 */
	public static function disable_css($group)
	{
		static::asset_enabled('css', $group, false);
	}

	/**
	 * Enables / disables an asset.
	 *
	 * @param string $type 'css' / 'js'
	 * @param string $group The group to enable/disable
	 * @param bool $enabled True to enabel to group, false odisable
	 */
	private static function asset_enabled($type, $group, $enabled)
	{
		if (!array_key_exists($group, static::$groups[$type]))
				return;
		static::$groups[$type][$group]['enabled'] = $enabled;
	}

	/**
	 * Add a javascript asset.
	 *
	 * @param string $script The script to add.
	 * @param string $script_min If given, will be used when $min = true
	 *        If omitted, $script will be minified internally
	 * @param string $group The group to add this asset to. Defaults to 'global'
	 */
	public static function js($script, $script_min = false, $group = 'global')
	{
		static::add_asset('js', $script, $script_min, $group);
	}

	/**
	 * Add a css asset.
	 *
	 * @param string $sheet The script to add
	 * @param string $sheet_min If given, will be used when $min = true
	 *        If omitted, $script will be minified internally
	 * @param string $group The group to add this asset to. Defaults to 'global'
	 */
	public static function css($sheet, $sheet_min = false, $group = 'global')
	{
		static::add_asset('css', $sheet, $sheet_min, $group);
	}

	/**
	 * Abstraction of js() and css().
	 *
	 * @param string $type 'css' / 'js'
	 * @param string $script The script to add.
	 * @param string $script_min If given, will be used when $min = true
	 *        If omitted, $script will be minified internally
	 * @param string $group The group to add this asset to
	 */
	private static function add_asset($type, $script, $script_min, $group)
	{
		if (!array_key_exists($group, static::$groups[$type]))
		{
			// Assume they want the group enabled
			static::add_group($type, $group, array(array($script, $script_min)), true);
		}
		else
		{
			array_push(static::$groups[$type][$group]['files'], array($script, $script_min));
		}
	}

	/**
	 * Add a string containing javascript, which can be printed inline with
	 * js_render_inline().
	 *
	 * @param string $content The javascript to add
	 */
	public static function js_inline($content)
	{
		static::add_asset_inline('js', $content);
	}

	/**
	 * Add a string containing css, which can be printed inline with
	 * css_render_inline().
	 *
	 * @param string $content The css to add
	 */
	public static function css_inline($content)
	{
		static::add_asset_inline('css', $content);
	}

	/**
	 * Abstraction of js_inline() and css_inline().
	 *
	 * @param string $type 'css' / 'js'
	 * @param string $content The css / js to add
	 */
	private static function add_asset_inline($type, $content)
	{
		array_push(static::$inline_assets[$type], $content);
	}

	/**
	 * Renders the specific javascript group, or all groups if no group specified.
	 *
	 * @param string $group Which group to render. If omitted renders all groups
	 * @param bool $inline If true, the result is printed inline. If false, is
	 *        written to a file and linked to. In fact, $inline = true also causes
	 *        a cache file to be written for speed purposes
	 * @param bool $min True to minify the javascript files. null to use the config value
	 * @return string The javascript tags to be written to the page
	 */
	public function render_js($group = false, $inline = false, $min = null)
	{
		if ($min === null)
			$min = static::$min;

		$file_groups = static::files_to_render('js', $group, $min);

		$ret = '';

		foreach ($file_groups as $group_name => $file_group)
		{
			if ($min)
			{
				$filename = static::combine_and_minify('js', $file_group);
				if (!$inline && static::$show_files)
				{
					$ret .= '<!--'.PHP_EOL.'Group: '.$group_name.PHP_EOL.implode('', array_map(function($a){
						return "\t".$a['file'].PHP_EOL;
					}, $file_group)).'-->'.PHP_EOL;
				}
				if ($inline)
					$ret .= html_tag('script', array('type' => 'text/javascript'), PHP_EOL.file_get_contents(DOCROOT.static::$cache_path.'/'.$filename).PHP_EOL).PHP_EOL;
				else
					$ret .= html_tag('script', array(
						'type' => 'text/javascript',
						'src' => static::$asset_url.static::$cache_path.$filename,
					), '').PHP_EOL;
			}
			else
			{
				foreach ($file_group as $file)
				{
					if ($inline)
						$ret .= html_tag('script', array('type' => 'text/javascript'), PHP_EOL.file_get_contents($file['file']).PHP_EOL).PHP_EOL;
					else
						$ret .= html_tag('script', array(
							'type' => 'text/javascript',
							'src' => static::$asset_url.$file['file'],
						), '').PHP_EOL;
				}
			}

		}
		return $ret;
	}

	/**
	 * Renders the specific css group, or all groups if no group specified.
	 *
	 * @param string $group Which group to render. If omitted renders all groups
	 * @param bool $inline If true, the result is printed inline. If false, is
	 *        written to a file and linked to. In fact, $inline = true also causes
	 *        a cache file to be written for speed purposes
	 * @param bool $min True to minify the css files. null to use the config value
	 * @return string The css tags to be written to the page
	 */
	public function render_css($group = false, $inline = false, $min = null)
	{
		if ($min === null)
			$min = static::$min;

		$file_groups = static::files_to_render('css', $group, $min);

		$ret = '';

		foreach ($file_groups as $group_name => $file_group)
		{
			if ($min)
			{
				$filename = static::combine_and_minify('css', $file_group);
				if (!$inline && static::$show_files)
				{
					$ret .= '<!--'.PHP_EOL.'Group: '.$group_name.PHP_EOL.implode('', array_map(function($a){
						return "\t".$a['file'].PHP_EOL;
					}, $file_group)).'-->'.PHP_EOL;
				}
				if ($inline)
					$ret .= html_tag('style', array('type' => 'text/css'), PHP_EOL.file_get_contents(DOCROOT.static::$cache_path.'/'.$filename).PHP_EOL).PHP_EOL;
				else
					$ret .= html_tag('link', array(
						'rel' => 'stylesheet',
						'type' => 'text/css',
						'href' => static::$asset_url.static::$cache_path.$filename,
					)).PHP_EOL;
			}
			else
			{
				foreach ($file_group as $file)
				{
					if ($inline)
						$ret .= html_tag('style', array('type' => 'text/css'), PHP_EOL.file_get_contents($file['file']).PHP_EOL).PHP_EOL;
					else
						$ret .= html_tag('link', array(
							'rel' => 'stylesheet',
							'type' => 'text/css',
							'href' => static::$asset_url.$file['file'],
						)).PHP_EOL;
				}
			}

		}
		return $ret;
	}

	/**
	 * Determines the list of files to be rendered, along with whether they
	 * have been minified already.
	 *
	 * @param string $type 'css' / 'js'
	 * @param array $group The groups to render. If false, takes all groups
	 * @param bool $min Whether to minify
	 * @return array An array of array('file' => file_name, 'minified' => whether_minified)
	 */
	private static function files_to_render($type, $group, $min)
	{
		// If no group specified, print all groups.
		if ($group == false)
			$group_names = array_keys(static::$groups[$type]);
		else
			$group_names = array($group);

		$files = array();

		$minified = false;

		foreach ($group_names as $group_name)
		{
			if (static::$groups[$type][$group_name]['enabled'] == false)
				continue;
			// If there are no files in the group, there's no point in printing it.
			if (count(static::$groups[$type][$group_name]['files']) == 0)
				continue;

			if (!array_key_exists($group_name, $files))
				$files[$group_name] = array();

			foreach (static::$groups[$type][$group_name]['files'] as $file_set)
			{
				if ($min)
				{
					$file = static::find_file(($file_set[1]) ? $file_set[1] : $file_set[0], $type);
					$minified = ($file_set[1] != false);
				}
				else
				{
					$file = static::find_file($file_set[0], $type);
				}
				array_push($files[$group_name], array(
					'file' => $file,
					'minified' => $minified,
				));
			}
			// If minifying, sort by filename
			uasort($files[$group_name], function($a, $b) {
				return ($a['file'] > $b['file']) ? 1 : -1;
			});
		}
		return $files;
	}

	/**
	 * Takes a list of files, and combines them into a single minified file.
	 * Doesn't bother if none of the files have been modified since the cache
	 * file was written.
	 *
	 * @param string $type 'css' / 'js'
	 * @param array $file_group Array of ('file' => filename, 'minified' => is_minified)
	 *        to combine and minify.
	 * @return string The path to the cache file which was written.
	 */
	private static function combine_and_minify($type, $file_group)
	{
		$ext = '.'.$type;
		$filename = md5(implode('', array_map(function($a) {
			return $a['file'];
		}, $file_group))).$ext;
		// Get the last modified time of all of the component files
		$last_mod = 0;
		foreach ($file_group as $file)
		{
			$mod = filemtime(DOCROOT.$file['file']);
			if ($mod > $last_mod)
				$last_mod = $mod;
		}

		$filepath = DOCROOT.static::$cache_path.'/'.$filename;
		$needs_update = (!file_exists($filepath) || ($mtime = filemtime($filepath)) < $last_mod);

		if ($needs_update)
		{
			$content = '';
			foreach ($file_group as $file)
			{
				if (static::$show_files_inline)
					$content .= '/* '.$file['file'].' */'.PHP_EOL;
				if ($file['minified'])
					$content .= file_get_contents($file['file']).PHP_EOL;
				else
				{
					if ($type == 'js')
					{
						$content .= Casset_JSMin::minify(file_get_contents($file['file'])).PHP_EOL;
					}
					elseif ($type == 'css')
					{
						$css = Casset_Csscompressor::process(file_get_contents($file['file'])).PHP_EOL;
						$content .= Casset_Cssurirewriter::rewrite($css, dirname($file['file']));
					}
				}
			}
			file_put_contents($filepath, $content);
			$mtime = time();
		}
		return $filename.'?'.$mtime;
	}

	/**
	 * Renders the javascript added through js_inline().
	 *
	 * @return string <script> tags containing the inline javascript
	 */
	public static function render_js_inline()
	{
		$ret = '';
		foreach (static::$inline_assets['js'] as $content)
		{
			$ret .= html_tag('script', array('type' => 'text/javascript'), PHP_EOL.$content.PHP_EOL).PHP_EOL;
		}
		return $ret;
	}

	/**
	 * Renders the css added through css_inline().
	 *
	 * @return string <style> tags containing the inline css
	 */
	public static function render_css_inline()
	{
		$ret = '';
		foreach (static::$inline_assets['css'] as $content)
		{
			$ret .= html_tag('script', array('type' => 'text/javascript'), PHP_EOL.$content.PHP_EOL).PHP_EOL;
		}
		return $ret;
	}

	/**
	 * Locates the given image(s), and returns the resulting <img> tag.
	 *
	 * @param mixed $images Image(s) to print. Can be string or array of strings
	 * @param array $attr Attributes to apply to each image (eg alt)
	 * @return string The resulting <img> tag(s)
	 */
	public static function img($images, $attr = array())
	{
		if (!is_array($images))
			$images = array($images);
		$ret = '';
		foreach ($images as $image)
		{
			$attr['src'] = static::$asset_url.static::find_file($image, 'img');
			$ret .= html_tag('img', $attr);
		}
		return $ret;
	}
}

/* End of file casset.php */