Casset
======

Casset is an alternative to fuelphp's Asset class.  
Casset supports minifying and combining scripts, in order to reduce the number and size of http requests need to load a given page. Grouping syntax has been made cleaner, and the ability to render all groups, and enable/disable specific groups, added.  
There are are some other changes too, please read on!

Thanks to Stephen Clay (and Douglas Crockford) for writing the minification libraries, stolen from http://code.google.com/p/minify/.

Installation
------------

### Manual
1. Clone / [download](https://github.com/canton7/fuelphp-casset/zipball/master)
2. Stick in fuel/packages/
3. Optionally edit fuel/packages/casset/config/casset.php (the defaults are sensible)
4. Create public/assets/cache
5. Add 'casset' to the 'always_load/packages' array in app/config/config.php (or call `Fuel::add_package('casset')` whenever you want to use it).
6. Enjoy :)

Introduction
------------

Casset is an easy-to-use asset management library. It boasts the following features:

- Speficy which assets to use for a particular page in your view/controller, and print them in your template.
- Collect your assets into groups, either pre-defined or on-the-fly.
- Enable/disable specific groups from your view/controller.
- Minify your groups and combine into single files to reduce browser requests and loading times.
- Define JS/CSS in your view/controller to be included in your template.
- Namespace your assets.

Basic usage
-----------

JS and CSS files are handled the same way, so we'll just consider JS. Just substitude 'js' with 'css' for css-related functions.

Javascript files can be added using the following, where "myfile.js" and "myfile2.js" are the javascript files you want to include,
and are located at public/assets/js/myfile.js and public/assets/js/myfile2.js (configurable).

```php
Casset::js('myfile.js');
Casset::js('myfile2.js');
```

By default, Casset will minify both of these files and combine them into a single file (which is written to public/assets/cache/\<md5 hash\>.js).
To include this file in your page, use the following:

```php
echo Casset::render_js();
/*
Returns something like
<script type="text/javascript" src="http://localhost/site/assets/cache/d148a723c710760bc62ca3ecc8c50206.js?1307384477"></script>
*/
```

If you've got minification turned off (see the section at the bottom of this readme), you'll instead get:

```php
<script type="text/javascript" src="http://localhost/site/assets/js/myfile.js"></script>
<script type="text/javascript" src="http://localhost/site/assets/js/myfile2.js"></script>
```

If you have a specific file ("myfile.min.js") which you want Casset to use, rather than generating its own minified version, you
can pass this as the second argument, eg:

```php
Casset::js('myfile.js', 'myfile.min.js');
```

Some folks like css and js tags to be together. `Casset::render()` is a shortcut which calls `Casset::render_css()` then `Casset::render_js()`.

Images
------

Although the original Asset library provided groups, etc, for dealing with images, I couldn't see the point.

Therefore image handling is somewhat simpler, and can be summed up by the following line, where the third argument is an optional array of attributes:

```php
echo Casset::img('test.jpg', 'alt text', array('width' => 200));
```

You can also pass an array of images (which will all have to same attributes applied to them), eg:

```php
echo Casset::img(array('test.jpg', 'test2.jpg'), 'Some thumbnails');
```

This function has more power when you consider namespacing, detailed later.

Groups
------

Groups are collections of js/css files.
A group can be defined in the config file, or on-the-fly. They can be enabled and disabled invidually, and rendered individually.

CSS and JS have their own group namespaces, so feel free to overlap.

To define a group in the config file, use the 'groups' key, eg:

```php
'groups' => array(
	'js' => array(
		'group_name' => array(
			'files' => array(
				array('file1.js', 'file1.min.js'),
				'file2.js'
			),
			'enabled' => true,
			'combine' => false,
			'min' => false,
		),
		'group_name_2' => array(.....),
	),
	'css' => array(
		'group_name' => array(
			'files' => array(
				array('file1.css', 'file1.min.css'),
				'file2.css',
			),
			'enabled' => true,
		),
		'group_name_3' => array(.....),
	),
),
```

As you can see, the javascript and css groups are entirely separate.
Each group consists of the following parts:  
**files**: a list of files present in the group. Each file definition can either be a string or a 2-element array.
If you're using minification, but have a pre-minified copy of your file (jquery is an example), you can pass this as the second
array element.  
**enabled**: Optional, specifies whether a group is enabled. A group will only be rendered when it is enabled. Default true.  
**combine**: This optional key allows you to override the 'combine' config key on a per-group bases.  
**min**: This optional key allows you to override the 'min' config key on a per-group basis.

Groups can be enabled using `Casset::enable_js('group_name')`, and disabled using `Casset::disable_js('group_name')`. CSS equivalents also exist.  
The shortcuts `Casset::enable('group_name')` and `Casset::disable('group_name')` also exist, which will enable/disable both the js and css groups of the given name, if they are defined.  
You can also pass an array of groups to enable/disable.

Specific groups can be rendered using eg `Casset::render_js('group_name')`. If no group name is passed, *all* groups will be rendered.  
Note that when a group is rendered, it is disabled. See the "Extra attributes" section for an application of this behaviour.

Files can be added to a group by passing the group name as the third argument to `Casset::js` / `Casset::css`, eg:

```php
Casset::js('myfile.js', 'myfile.min.js', 'group_name');
Casset::css('myfile.css', false, 'group_name');
```

(As an aside, you can pass any non-string value instead of 'false' in the second example, and Casset will behave the same: generate your minified file for you.)

If the group name doesn't exist, the group is created, and enabled.

When you call `Casset::render()` (or the js- and css-specific varients), the order that groups are rendered is determined by the order in which they were created, with groups present in the config file appearing first.
Similarly (for JS files only), the order in which files appear is determined by the order in which they were added.
This allows you a degree of control over what order your files are included in your page, which may be necessary when satisfying dependancies.
If this isn't working for you, or you want something a bit more explicit, try this: If file A depends on B, add B to its own group and explicitely render it first.

NOTE: Calling ``Casset::js('file.js')`` will add that file to the "global" group. Use / abuse as you need!

Paths and namespacing
---------------------

The Asset library searches through all of the items in the 'paths' config key until it finds the first matching file.
However, this approach was undesirable, as it means that if you had the directory structure below, and tried to include 'index.js', the file that was included would be determined by the order of the
entries in the paths array.

```
assets/
   css/
   js/
      index.js
   img/
   admin/
      css/
      js/
	     index.js
      img/
```

Casset brings decent namespacing to the rescue!
For the above example, you can specify the following in your config file:

```
'paths' => array(
	'core' => 'assets/',
	'admin' => 'assets/admin/',
),
```

Which path to use is then decided by prefixing the asset filename with the key of the path to use. Note that if you omit the path key, the current default path key (initially 'core') is used.

```php
Casset::js('index.js');
// Or
Casset::js('core::index.js');
// Will add assets/js/index.js

Casset::js('admin::index.js');
// Will add assets/admin/js/index.js

echo Casset::img('test.png', 'An image');
// <img src="...assets/img/test.png" alt="An image" />

echo Casset::img('admin::test.png', 'An image');
// <img src="...assets/admin/img/test.png" alt="An image" />
```

If you wish, you can change the current default path key using `Casset::set_path('path_key')`. This can be useful if you know that all of the assets in a given file will be from a given path. For example:

```php
Casset::set_path('admin);
Casset::js('index.js');
// Will add assets/admin/js/index.js
```

The "core" path can be restored by calling `Casset::set_path()` with no arguments (or calling `Casset::set_path('core')`).

You can also namespace the files listed in the config file's 'groups' section, in the same manner.
Note that these are loaded before the namespace is changed from 'core', so any files not in the core namespace will have to by explicitely prepended with the namespace name.

In addition, you can override the config options 'js_path', 'css_path' and 'img_path' on a per-path basis. In this case, the element of the 'paths' config array takes the following form,
where each of 'js_path', 'css_path' and 'img_path' are optional. If they are not specified, the defaults will be used.

```php
array (
	'some_key' => array(
		'path' => 'more_assets/',
		'js_dir' => 'javascript/',
		'css_dir' => 'styles/'
		'img_dir' => 'images/',
		),
	),
),
```

This can be particularly useful when you're using some third-party code, and don't have control over where the assets are located.

Globbing
--------

As well as filenames, you can specify [http://php.net/glob](glob patterns). This will act exactly the same as if each file which the glob matches had been added individually.  
For example:

```php
Casset::css('*.css');
// Runs glob('assets/css/*.css') and adds all matches.

Casset::css('admin::admin_*.css');
// (Assuming the paths configuration in the "Paths and namespacing" section)
// Executes glob('adders/admin/css/admin_*.css') and adds all matches

Casset::js('*.js', '*.js');
// Adds all js files in assets/js, ensuring that none of them are minified.
```

An exception is thrown when no files can be matched.

Inlining
--------

If you want Casset to display a group inline, instead of linking to a cache file, you can pass `true` as the second argument to `Casset::render_js()` or `Casset::render_css()`.
For example...

```php
// Render 'group_name' js inline.
echo Casset::render_js('group_name', true);
// Render all css groups inline.
echo Casset::render_css(false, true);
```

Occasionally it can be useful to declare a bit of javascript in your view, but have it included in your template. Casset allows for this, although it doesn't support groups and minification
(as I don't see a need for those features in this context -- give me a shout if you find an application for them, and I'll enhance).

In your view:

```php
$bar = 'baz';
$js = <<<EOF
	var foo = "$bar";
EOF;
Casset::js_inline($js);
```

In your template:

```php
echo Casset::render_js_inline();
/*
Will output:
<script type="text/javascript">
	var foo = "baz";
</script>
*/
```

Similarly, `Casset::css_inline()` and `Casset::render_css_inline()` exist.

Extra attributes
----------------

`Casset::render_js()` and `Casset::render_css()` support an optional third argument which allows the user to define extra attributes to be applied to the script/link tag.  
This can be combined with the fact that one a group has been rendered, it is disabled, allowing the following to be done:

```php
Casset::css('main.css');
Casset::css('print.css', false, 'print');

// Render the 'print' group
echo Casset::render_css('print', false, array('media' => 'print');
// <link rel="stylesheet" type="text/css" href="http://...print.css" media="print />

// Render everything else, except the 'print' group
echo Casset::render_css();
// <link rel="stylesheet" type="text/css" href="http://...main.css" />
```

Minification and combining
--------------------------

Minification uses libraries from Stephen Clay's [Minify library](http://code.google.com/p/minify/).

The 'min' and 'combine' config file keys work together to control exactly how Casset operates:

**Combine and minify:**
When an enabled group is rendered, the files in that group are minified (or the minified version used, if given, see the second parameter of eg `Casset::js()`),
and combined into a single cache file in public/assets/cache (configurable).

**Combine and not minify:**
When an enabled group is rendered, the files in that group are combined into a a single cache file in public/assets/cache (configurable). The files are not minified.

**Not combine and minify:**
When an enabled group is rendered, a separate `<script>` or `<link>` tag is created for each file.
If a minified version of a file has been given, it will be linked to. Otherwise, the non-minified version is linked to.
NOTE THAT THIS MIGHT BE UNEXPECTED BEHAVIOUR. It is useful, however, when linking to remote assets. See the section on remote assets.

**Not combine and not minify**
When an enabled group is rendered, a separate `<script>` or `<link>` tag is created for each file.
The non-minified version of the file is used in each case.

You can choose to include a comment above each `<script>` and `<link>` tag saying which group is contained with that file by setting the "show_files" key to true in the config file.
Similarly, you can choose to put comments inside each minified file, saying which origin file has ended up where -- set "show_files_inline" to true.

You can control whether Casset minifies or combines individual groups, see the groups section.

When minifying CSS files, urls are rewritten to take account of the fact that your css file has effectively moved into `public/assets/cache`.

With both CSS and JS files, when a cache file is used, changing the order in which files were added to the group will re-generate the cache file, with the files in their new positions.
This is because the order of files can be important, as dependancies may need to be satisfied.
Bear this in mind when adding files to groups dynamically -- if you're changing the order of files in an otherwise identical group, you're not allowing
the browser to properly use its cache.

NOTE: If you change the contents of a group, and a cache file is used, a new cache file will be generated. However the old one will not be removed (groups are mutable,
so Casset doesn't know whether a page still uses the old cache file).
Therefore an occasional clearout of `public/assets/cache/` is recommended. See the section below on clearing the cache.

Remote files
------------

Casset supports handing files on remote machines, as well as the local one.
This is done by creating a new namespace, and specifying a url instead of a relative path.
All files using that namespace will then be fetched from the given url.

However, there are a couple of caveats:
 - It is possible for Casset to fetch, combine and minify remote assets. However, it can obviously only write the cache file locally.
 - Casset doesn't bother to check the modification times on remote files when deciding whether the cache is out of date (as this would cause lots of http requests from your server, and entirely defeat
   the point of caching in the first place). Therefore if the remote file changes, Casset's cache will not be updated, and you'll have to remove it manually, or with the cache-clearing functions.

For this reason, recommended practice is to either turn off combining files entirely if using remote assets (possibly undesirable),
or create one or more groups dedicated to remote files, in which combination is disabled.

Note that when combining files is disabled, but minification enabled, each file in the group will have its own `<script>` or `<link>` tag, but the minified version of the file will be linked to, if supplied.
If no minified version of the file is supplied, the non-minified version will be linked to.  
This behaviour was designed for use when using remote assets, where the desired behaviour is to avoid caching the file locally, instead leaving it on the remote server.

Here is an example, using the Google API libraries:

```php
// In config/casset.php
'paths' => array(
	'core' => 'assets/',
	'google_api' => array(
		'path' => 'http://ajax.googleapis.com/ajax/libs/',
		'js_dir' => '',
	),
),

'groups' => array(
	'js' => array(
		'jquery' => array(
			'files' => array(
				array('google_api::jquery/1.6.2/jquery.js', 'google_api::jquery/1.6.2/jquery.min.js'),
			),
			'enabled' => true,
			'combine' => false,
		),
	),
),

// Then you can also do....
Casset::js('google_api::jqueryui/1.8.14/jquery-ui.js', 'google_api::jqueryui/1.8.14/jquery-ui.min.js', 'jquery');


echo Casset::render();

// If minification is disabled:
// <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.js"></script>
// <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.14/jquery-ui.js"></script>

// If minification is enabled:
// <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
// <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.14/jquery-ui.min.js"></script>
```

Getting asset paths / urls
--------------------------

Thanks to [Peter](http://fuelphp.com/forums/posts/view_reply/3097) for this one. You can ask Casset for the path / url to a specific file.
Files are specified in exactly the same way as with eg `Casset::js()`, with the same rules to do with namespacing, `Casset::set_path()`, etc.

The functions in question are `Casset::get_filepath_js()`, `Casset::get_filepath_css()`, and `Casset::get_filepath_img()`.

They're all used in the same way:

```php
echo Casset::get_filepath_js('file.js');
// assets/js/file.js
```

Note that fuel executes in the `/public` directory, so the paths returned are relative to the current working dir.
If you'd prefer urls to be returned, pass `true` as the second parameter.
Note that a url will not be added if you're referencing a remote file.

```php
echo Casset::get_filepath_js('file.js', true);
// eg http://localhost/site/public/assets/js/file.js
```

Complexities start arising when you specify globs.
By default, an array will be returned if more than one file is found, otherwise a string is returned.
To override this behaviour, and return an array even if only one file is found, pass `true` as the third parameter.

```php
print_r(Casset::get_filepath_js('file.js', false, true));
// Array( [0] => 'assets/js/file.js' )

print_r(Casset::get_filepath_js('file*.js'));
// Array( [0] => 'assets/js/file1.js', [1] => 'assets/js/file2.js' )
```

There also exists `Casset::get_filepath()`, which takes the form

```php
Casset::get_filepath($name, $type, $add_url = false, $force_array = false);
```

`$name`, `$add_url` and `$force_array` are the same as for `Casset::get_filepath_js()`, while the `$type` argument is one of 'js', 'css', or 'img'.
In the future there are plans to let you specify your own types, hence why this is exposed :)

Clearing the cache
------------------
Since cache files are not automatically removed (Casset has no way of knowing whether a cache file might be needed again), a few methods have been provided to remove cache files.

`Casset::clear_cache()` will clear all cache files, while `Casset::clear_js_cache()` and `Casset::clear_css_cache()` will remove just JS and CSS files respectively.  
All of the above functions optionally accept an argument allowing you to only delete cache files last modified before a certain time. This time is specified as a
[strtotime](http://php.net/strtotime)-formatted string, for example "2 hours ago", "last Tuesday", or "20110609".  
For example:

```php
Casset::clear_js_cache('2 hours ago');
// Removes all js cache files last modified more than 2 hours ago

Casset::clear_cache('yesterday');
// Removes all cache files last modified yesterday
```

Comparison to Assetic
---------------------

A frequent question is how Casset differs from kriswallsmith's [Assetic](https://github.com/fuel-packages/fuel-assetic). InCasset and Assetic have completely different goals.

* Assetic is a very powerful asset mangement framework. It allows you to perform minification, compression and compilation on your assets, although learning it will take time.
* Casset is designed to make assets very easy to handle. You call `Casset::js()` then `Casset::render_js()`, and everything is taken care of.

If you're a developer tasked with fully optimising your site's page load time, for example, go with Assetic. If you want a very easy way to manage your assets, with some minification
thrown in for free, (and have no need for Assetic's complex features), go with Casset.

Examples
--------

Let's say we have a site which uses jquery on every page, jquery-ui on some pages, and then various other odds and sods.

In the config file:

```php
'groups' => array(
	'js' => array(
		'jquery' => array(
			'files' => array(
				array('jquery.js', 'jquery.min.js'),
			),
			'enabled' => true,
		),
		'jquery-ui' => array(
			'files' => array(
				array('jquery-ui.js', 'jquery-ui.min.js'),
			),
			'enabled' => false,
		),
	),
	'css' => array(
		'jquery-ui' => array(
			'files' => array(
				'jquery-ui.css',
			),
			'enabled' => false,
		),
	),
),
```

In our template file:

```html
...
<head>
<?php echo Casset::render_css() ?>
</head>
...
<body>
...
<?php
	echo Casset::render_js();
	echo Casset::render_js_inline();
?>
</body>
```

We can then turn the jquery-ui group on as we please.

file_1.php: (doesn't use jquery-ui)

```php
...
Casset::js('file_1.js');
Casset::css('file_1.css');
...
```

file_2.php: (does use jquery-ui)

```php
...
Casset::js('file_2.js');
Casset::css('file_2.css');
Casset::enable('jquery-ui');
...
```

Contributing
------------

If you've got any issues/complaints/suggestions, please tell me and I'll do my best!

Pull requests are also gladly accepted. This project uses [git flow](http://nvie.com/posts/a-successful-git-branching-model/), so please base your work on the tip of the `develop` branch, and rebase onto `develop` again before submitting the pull request.
