=== Postcard Social Networking Plugin for WordPress ===
Contributors: bitwit
Donate link: http://www.postcardsocial.net
Tags: social, networking, postcard, feed
Requires at least: 3.0
Tested up to: 3.8.1
Stable tag: 1.4.1
License: GPL v2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This is designed for use with Postcard for iOS. Using shortcodes you can embed feeds and galleries of your content into posts and pages.

== Description ==

The Postcard Social Networking Plugin for WordPress is designed to be compatible with Postcard for iOS. Without the companion
app, this plugin won't serve much purpose.

The intention of the Postcard app and plugin is to help users achieve a few key things:

* Help users that want to post and display social content on their own website without any display restrictions
* Help users create fresh content for their website when there isn't time for long-form blogging
* Help users that want to own their content by creating sharable permalinks that are attached to messages to networks like Facebook and Twitter
* Help users drive traffic to their own websites

== Installation ==

1. Upload the `postcard-plugin`folder  to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. You are now ready to setup the network up in Postcard on your mobile phone.
4. In the iOS app - enter your WordPress website url, username and password to get set up
5. Now you are ready to post content directly to your own website.
6. New content will display in the newly created "Postcard Archive" page as well as "My Postcards" in your settings menu. There are many more options for customization.

Once you are posting content to your website, you can use insert short tags in the post/pages editor to retrieve your content like so:

**[postcard-archive]**
This shortcode will create a feed of content that is queryable using url (a.k.a. GET) parameters such as ?tags=interesting
When you first install Postcard a page is created with this shortcode and used as your permalink url for all future shared content, should you choose to host picture/video content when sharing to other networks

**[postcard-feed]**
This shortcode will create feed of content that is filterable via attributes such as:

    [postcard-feed tags="interesting,useful"]

**[postcard-gallery]**
This shortcode will create an image gallery and only display image and video content and is filterable via attributes such as:

    [postcard-gallery count=20]

**#profile**
If you tag a photo upload with #profile or privately tag it with 'profile' this will become your effective new 'profile picture'
that is used in the gallery overlay

== Changelog ==

= 1.4 =
* Several new settings to manage auto-creating new posts for every piece of social content
* Auto post - Titles can be the first line of your message
* Auto post - Tags and hashtags can be translated to WordPress categories and tags various ways
* Auto post - Feature image can be set automatically
* Postcard backend menus now appear based on 'manage_options' capability rather than 'edit_themes'
* Better video player with flash fallback for better compatibility
* fixes issue with galleries filtered by tags not showing in the modal overlay
* fixes issue with galleries filtered by tags not paging properly when Next/Previous buttons are clicked
* fixes issue with content not being searchable by tags
* fixes issue with a warning message related to mktime() being in the response
* fixes issue with gallery videos not auto-playing
* image attachments now get appropriate thumbnails generated and show up properly in the media section

= 1.3 =
* Postcard API launches after 'after_setup_theme' instead of 'plugins_loaded' to allow themes to listen for actions/hooks and modify
* Adds new filter 'postcard_new_content' which passes an array of the social content's parameters
* New option page allows you to enable automatic post-creation on the submission of new content
* Uploading images and video now includes them in the media library

= 1.2.4 =
* Basic text field editing for postcards: date, message, link url, image url, video url

= 1.2.3 =
* Content upload issue with domains that include a path (e.g. http://domain.com/blog/)

= 1.2.2 =
* jQuery conflict issue resolved

= 1.2 =
* Fixes a problem with the gallery javascript not being included

= 1.0 =
* This is a beta version and should not be used any longer

== Upgrade Notice ==

= 1.2.2 =
You should update to this version for full interactive gallery capabilities.

= 1.2 =
You should update to this version for full interactive gallery capabilities.
