=== Gutenberg Templates ===
Contributors: kmgalanakis
Tags: Gutenberg, Templates
Donate link: https://github.com/kmgalanakis
Requires at least: 5.0
Tested up to: 5.0
Requires PHP: 5.6
Stable tag: 1.0.1
License: GPL

Design templates using Gutenberg for pages, posts and custom types. Templates will ultimately be able to use custom fields for dynamic content.

== Description ==
Gutenberg Templates allows to build dynamic templates with custom fields and taxonomy and assign them to any content type in WordPress.

The templates will work like PHP templates. You design them once and they automatically apply to all items of a post type. When you edit a template, changes apply immediately to all the items that use that template.

Any item can opt-out of the post type template application at any time using a meta box control on the item edit page.

== Installation ==
 
1. Upload the plugin to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
 
== Frequently Asked Questions ==
 
= Does this plugin use the built-in Gutenberg templates system? =

No. This is an alternative implementation approach to Gutenberg's built-in templates system.

= What kind of custom fields can I use in templates? =
 
This plugin will ultimately integrate with the native custom fields as well as with several plugins offering custom fields functionality. Currently, any plugin offering this option using shortcodes is already supported by injecting the shortcodes inside blocks during the template design.

== Screenshots ==
 
1. Editing a template and adding the Gutenberg Templates post content block.

2. A rich template design.

3. Assigning the template to a post type.

4. Editing a template not assigned to any post type, targetted to be applied on a certain item.

5. Editing an item that the post type of which already has a template assignment.

6. The frontend view of an item with a template assigned.

7. The opt-out message.

8. The frontend view of the opted-out item.

== Upgrade Notice ==

= 1.0.1 =
* Fixed the "Assigned post type" column on the Gutenberg Template listing page to show the correct name.

= 1.0 =
* Initial release

== Changelog ==

= 1.0.1 =
* Fixed the "Assigned post type" column on the Gutenberg Template listing page to show the correct name.
 
= 1.0 =
* Initial release
