<?php

class Casset {

	protected static $asset_paths = array();

	protected static $asset_url = '/';

	protected static $folders = array(
		'css' => 'css/',
		'js' => 'js/',
	);

	protected static $cache_path = 'assets/cache/';

	protected static $groups = array(
		'css' => array('global' => array('files' => array(), 'enabled' => true, 'const' => false)),
		'js' => array('global' => array('files' => array(), 'enabled' => true, 'const' => false)),
	);

	protected static $min = true;

	public static $initialized = false;

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
		);

		static::$cache_path = Config::get('casset.cache_path', static::$cache_path);

		$group_sets = Config::get('casset.groups', array());

		foreach ($group_sets as $group_type => $groups)
		{
			foreach ($groups as $group_name => $group)
			{
				static::add_group($group_type, $group_name, $group['files'], true, $group['enabled']);
			}
		}

		static::$min = Config::get('casset.min', static::$min);

		static::$initialized = true;
	}

	public static function add_path($path)
	{
		array_unshift(static::$asset_paths, str_replace('../', '', $path));
	}

	public static function remove_path($path)
	{
		if (($key = array_search(str_replace('../', '', $path), static::$_asset_paths)) !== false)
		{
			unset(static::$_asset_paths[$key]);
		}
	}

	public static function add_group($group_type, $group_name, $files, $const = false, $enabled = true)
	{
		static::$groups[$group_type][$group_name] = array(
			'files' => $files,
			'enabled' => $enabled,
			'const' => $const,
		);
	}

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

	public static function enable_js($group)
	{
		static::asset_enabled('js', $group, true);
	}

	public static function disable_js($group)
	{
		static::asset_enabled('js', $group, false);
	}

	public static function enable_css($group)
	{
		static::asset_enabled('css', $group, true);
	}

	public static function disable_css($group)
	{
		static::asset_enabled('css', $group, false);
	}

	private static function asset_enabled($type, $group, $enabled)
	{
		if (!array_key_exists($group, static::$groups[$type]))
				return;
		static::$groups[$type][$group]['enabled'] = $enabled;
	}

	public static function js($script, $script_min = false, $group = 'global')
	{
		static::add_asset('js', $script, $script_min, $group);
	}

	public static function css($sheet, $sheet_min = false, $group = 'global')
	{
		static::add_asset('css', $sheet, $sheet_min, $group);
	}

	private static function add_asset($type, $script, $script_min, $group)
	{
		if (!array_key_exists($group, static::$groups[$type]))
		{
			// Assume they want the group enabled
			static::add_group($type, $group, array(
				'files' => array(array($script, $script_min)),
				'enabled' => true
			));
		}
		else
		{
			array_push(static::$groups[$type][$group]['files'], array($script, $script_min));
		}
	}

	public function render_js($group = false, $inline = false, $min = null)
	{
		// Very simple minimisation for now
		if ($min === null)
			$min = static::$min;

		$file_groups = static::files_to_render('js', $group, $min);

		$ret = '';

		foreach ($file_groups as $group_name => $file_group)
		{
			if ($min)
			{
				$filename = static::combine_and_minify('js', $group_name, $file_group);
				if ($inline)
					$ret .= html_tag('script', array('type' => 'text/javascript'), PHP_EOL.file_get_contents(DOCROOT.static::$cache_path.'/'.$filename).PHP_EOL).PHP_EOL;
				else
					$ret .= html_tag('script', array(
						'type' => 'text/javascript',
						'src' => static::$asset_url.static::$cache_path.$filename,
					)).PHP_EOL;
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
							'src' => $file['file'],
						)).PHP_EOL;
				}
			}

		}
		return $ret;
	}

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
				$filename = static::combine_and_minify('css', $group_name, $file_group);
				if ($inline)
					$ret .= html_tag('style', array('type' => 'text/css'), PHP_EOL.file_get_contents(DOCROOT.static::$cache_path.'/'.$filename).PHP_EOL).PHP_EOL;
				else
					$ret .= html_tag('script', array(
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
						$ret .= html_tag('script', array(
							'rel' => 'stylesheet',
							'type' => 'text/css',
							'href' => $file['file'],
						)).PHP_EOL;
				}
			}

		}
		return $ret;
	}

	private static function files_to_render($type, $group, $min)
	{
		if ($group == false)
			$group_names = array_keys(static::$groups[$type]);
		else
			$group_names = array($group);

		// All const groups get their own key in this array
		// All non-const groups get munged into the 'global' group
		$files = array();

		$minified = false;

		foreach ($group_names as $group_name)
		{
			if (static::$groups[$type][$group_name]['enabled'] == false)
				continue;

			$file_group_name = static::$groups[$type][$group_name]['const'] ? $group_name : 'global';
			if (!array_key_exists($file_group_name, $files))
				$files[$file_group_name] = array();

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
				array_push($files[$file_group_name], array(
					'file' => $file,
					'minified' => $minified,
				));
			}
			// If minifying, sort by filename
			uasort($files[$file_group_name], function($a, $b) {
				return ($a['file'] > $b['file']) ? 1 : -1;
			});
		}
		return $files;
	}

	private static function combine_and_minify($type, $group_name, $file_group)
	{
		// Generate combined filename
		// The 'global' group is non-const, and needs an md5 as the filename
		// Other groups are const, and filename can be derived from the group name
		$ext = '.'.$type;
		if ($group_name == 'global')
			$filename = md5(implode('', array_map(function($a) {
				return $a['file'];
			}, $file_group))).$ext;
		else
			$filename = 'group_'.$group_name.$ext;
		// Get the last modified time of all of the component files
		$last_mod = 0;
		foreach ($file_group as $file)
		{
			$mod = filemtime(DOCROOT.$file['file']);
			if ($mod > $last_mod)
				$last_mod = $mod;
		}

		$filepath = DOCROOT.static::$cache_path.'/'.$filename;
		$needs_update = (!file_exists($filepath) || filemtime($filepath) < $last_mod);

		if ($needs_update)
		{
			$content = '';
			foreach ($file_group as $file)
			{
				if ($file['minified'])
					$content .= file_get_contents($file['file']).PHP_EOL;
				else
				{
					if ($type == 'js')
						$content = Casset_JSMin::minify(file_get_contents($file['file'])).PHP_EOL;
					elseif ($type == 'css')
					{
						$content = Casset_Csscompressor::process(file_get_contents($file['file']).PHP_EOL);
						$content = Casset_Cssurirewriter::rewrite($content, dirname($file['file']));
					}
				}
			}
			file_put_contents($filepath, $content);
		}
		return $filename;
	}
}

/* End of file casset.php */