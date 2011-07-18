Changelog
=========

This file lists the important changes between versions. For a list of minor changes, check the log.

v1.9
---
Hotfix release.  
Fixes #1, reported by jaysonic, where add_path was (for some reason) private.


v1.8
----

- CSS files are no longer sorted, instead keeping the order they were added to the group in.
- Allow overriding of js_dir, css_dir and img_dir on a per-path basis (see the 'paths' config key).
- `Casset::render()` lost its 'min' parameter. Instead, minification can be controlled on a per-group basis from the config file.
- 'combine' option added. Read the readme ("Minification and combining") for details on how to use this with the 'min' option. This addition changes the behaviour of 'min' slighlty, but shouldn't break anything.
- Assets at remote locations are now supported. There are (necessarily) some gotchas, so please read the readme!
- The 'enabled' option in the 'groups' config key is now optional, and defaults to true.