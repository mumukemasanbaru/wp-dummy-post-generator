=== WP Dummy Post Generator ===
Contributors: rogerhub
Donate link: http://www.vim.org/iccf/
Tags: dummy, generator, filler, lorem ipsum, posts
Requires at least: 3.1
Tested up to: 3.8
Stable tag: 0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin generates dummy posts and dummy categories on WordPress. It will also let you specify a Blog Name and a Tagline to be set.

== Description ==

When I was developing blog themes, I found it useful to have a plugin generate a set of predefined categories and subcategories, and create random posts in them. It got tiresome making these by hand over and over, so I wrote a plugin that will do it. It will let you specify a post category hierarchy and other settings. These settings can be exported and applied on other blogs as well.

== Installation ==

1. Upload the `wp-dummy-post-generator` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Tools > Dummy Posts
1. Choose settings and create posts

== Frequently asked questions ==

= How do I use the category generator? =

Here's an example:

    Parent 1
	 Child 1
	 Child 2//custom-slug-for-child2
	Parent 2
	Parent 3

== Screenshots ==

1. The control panel of the WP Dummy Posts plugin.

== Changelog ==

= 0.4 =
Fixed bug with post slugs not being generated

= 0.2 =
Followed nourmaan's suggestions for more realistic posts. Added:

 - Different block types (paragraph, list, image, blockquote)
 - Shorter titles (distributed between 4~6 words)
 - New source text
 - Markup inside paragraphs to test styles (BOLD, italic, CODE, del, ins, etc..)

= 0.1 =
Initial Release
