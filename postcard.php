<?php
/*
Plugin Name: Postcard Social Plugin
Plugin URI: http://www.postcardsocial.net
Description: Allows you to store and index social content you are sharing through the Postcard iOS app
Version: 1.4.1
Author: Kyle Newsome
Author URI: http://www.bitwit.ca
License: GPL v2
*/

DEFINE("POSTCARD_ARCHIVE_PAGE", 'ca.bitwit.postcard/social-archive-install');

register_activation_hook( __FILE__, 'postcard_install_plugin' );
function postcard_install_plugin(){
    require_once("postcard-core/install.php");
    postcard_install_core();
}

add_action("after_setup_theme", "postcard_plugin_startup", 1);
function postcard_plugin_startup(){
    require_once("postcard-core/startup.php");
    postcard_run_startup();
}

