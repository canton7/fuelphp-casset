Casset
======

Casset is an alternative to fuelphp's Asset class.  
Casset supports minifying and combining scripts, in order to reduce the number and size of http requests need to load a given page.

Thanks to Stephen Clay (and Douglas Crockford) for writing the minification libraries, stolen from http://code.google.com/p/minify/.

Installation
------------

1. Clone / download
2. Copy classes/casset.php and classes/casset into app/classes/
3. Copy config/casset.php into app/config/ (optional, casset has sensible defaults)
4. Create public/assets/cache
5. Enjoy

Basic usage
-----------

JS and CSS files are handled the same way, so we'll just consider JS. Just substitude 'js' with 'css' for css-related functions.

Javascript files can be added using the following, where "myfile.js" and "myfile2.js" are the javascript files you want to include,
and are located at public/assets/js/myfile.js and public/assets/js/myfile2.js.

```php
Casset::js('myfile.js');
Casset::js('myfile2.js');
```

By default, Casset will minify both of these files and combine them into a single file (which is written to public/assets/cache/<md5 hash>.js).
To include this file in your page, use the following:

```php
echo Casset::render_js();
/*
Returns something like
<script type="text/javascript" src="http://localhost/site/assets/cache/d148a723c710760bc62ca3ecc8c50206.js?1307384477"></script>
*/
```

If you have a specific file ("myfile.min.js") which you want Casset to use, rather than generating its own minified version, you
can pass this as the second argument, eg:

```php
Casset::js('myfile.js', 'myfile.min.js');
```

Images
------

Although the origin Asset library provided groups, etc, for dealing with images, I couldn't see the point.

Therefore image handling is somewhat simpler, and can be summed up by the following line:

```php
echo Casset::img('test.jpg', array('alt' => 'This is an image'));
```

You can also pass an array of images (which will all have to same attributes applied to them), eg:

```php
echo Casset::img(array('test.jpg', 'test2.jpg'));
```

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
**enabled**: Whether a group is enabled. A group will only be rendered when it is enabled.

Groups can be enabled using `Casset::enable_js('group_name')`, and disabled using `Casset::disable_js('group_name')`. CSS equivalents also exist.  
The shortcuts `Casset::enable('group_name')` and `Casset::disable('group_name')` also exist, which will enable/disable both the js and css groups of the given name, if they exist.

Specific groups can be rendered using `Casset::render_js('group_name')`. If no group name is passed, *all* groups will be rendered.

Files can be added to a group by passing the group name as the third argument to `Casset::js` / `Casset::css`, eg:

```php
Casset::js('myfile.js', 'myfile.min.js', 'group_name');
Casset::css('myfile.css', false, 'group_name');
```

Groups can also be declared on the fly, as shown below. I can't think of why you'd want to do this, which is why the syntax is a little messy.

```php
Casset::add_group('js', 'group_name', array('file1.js', array('file2.js', 'file2.min.js')), $enabled);
```

NOTE: Calling ``Casset::js('file.js')`` will add that file to the "global" group. Use / abuse as you need!


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
(as I don't see a need for those features in this context).

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

Minification
------------

Minification uses libraries from Stephen Clay's [Minify library](http://code.google.com/p/minify/).

When minification is enabled (see the "min" key in the config file), when an enabled group is rendered, it is combined, minified, and stored in a file in public/assets/cache/.
This is an attempt to achieve a balance between spamming the browser with lots of files, and allowing the browser to cache files.
The assumption is that each group is likely to appear fairly independantly, so combining groups isn't worth it.

You can choose to include a comment above each `<script>` and `<link>` tag saying which group is contained with that file by setting the "show_files" key to true in the config file.
Similarly, you can choose to put comments inside each minified file, saying which origin file has ended up where -- set "show_files_inline" to true.

`Casset::render_js()` and `Casset::render_css()` take an optional third argument, allowing you to control minification on a per-group basis if you need.
The following will minify the 'group_name' group, even if minification is turned off in the config file.

```php
echo Casset::render_js('group_name', false, true);
```

NOTE: If you change the contents of a group, a new cache file will be generated. However the old one will not be removed (Casset doesn't know if you've got a single page where you add an extra file to a group).
Therefore an occasional clearout of `public/assets/cache/` is recommended.

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