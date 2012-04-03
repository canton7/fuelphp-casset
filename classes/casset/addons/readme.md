Casset Addons
=============

Addons are extra classes which allow Casset to integrate with other third-party tools, in ways which are not possible using the callbacks.

Currently there is only one addon: Twig.
However, this will change if people require other addons.

Twig
----

This extension adds the `Caset::render`, `Casset::render_js`, `Casset::render_css`, and `Casset::img` methods as twig functions (`render_assets`, `render_js`, `render_css`, and `img` respectively).

To enable this extension, edit `config/parser.php`, and add `Casset_Addons_Twig` to the `extensions` key under 'View_Twig', like so:

```php
'View_Twig' => array(
	'extensions' => array(
		'Twig_Fuel_Extension',
		'Casset_Addons_Twig',
	),
),
```
