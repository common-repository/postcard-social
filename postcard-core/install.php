<?php

function postcard_install_core()
{
    postcard_install_tables();
    postcard_install_archive_page();
}

function postcard_install_tables()
{
    global $wpdb;
    $posts_table_name = $wpdb->prefix . "pc_postcards";
    $posts_sql = "CREATE TABLE $posts_table_name (
      id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
      user_id MEDIUMINT(9) NOT NULL,
      date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
      message TEXT NOT NULL,
      url VARCHAR(256) DEFAULT NULL,
      image VARCHAR(256) DEFAULT NULL,
      video VARCHAR(256) DEFAULT NULL,
      width MEDIUMINT(9) DEFAULT NULL,
      height MEDIUMINT(9) DEFAULT NULL,
      UNIQUE KEY id (id)
    );";

    $tags_table_name = $wpdb->prefix . "pc_tags";
    $tags_sql = "CREATE TABLE $tags_table_name (
      id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
      tag VARCHAR(256) DEFAULT '' NOT NULL,
      UNIQUE KEY id (id)
    );";

    $tags_posts_table_name = $wpdb->prefix . "pc_tags_posts";
    $tags_posts_sql = "CREATE TABLE $tags_posts_table_name (
      id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
      postcard_id MEDIUMINT(9) NOT NULL,
      tag_id MEDIUMINT(9) NOT NULL,
      UNIQUE KEY id (id)
    );";

    $network_posts_table_name = $wpdb->prefix . "pc_network_posts";
    $network_posts_sql = "CREATE TABLE $network_posts_table_name (
      id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
      postcard_id MEDIUMINT(9) NOT NULL,
      network VARCHAR(256) DEFAULT '' NOT NULL,
      network_post_id VARCHAR(256) NOT NULL,
      UNIQUE KEY id (id)
    );";

    $auth_tokens = $wpdb->prefix . "pc_auth_tokens";
    $tokens_sql = "CREATE TABLE $auth_tokens (
      id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
      token VARCHAR(256) DEFAULT '' NOT NULL,
      user_id MEDIUMINT(9) NOT NULL,
      expires DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
      UNIQUE KEY id (id)
    );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($posts_sql);
    dbDelta($tags_sql);
    dbDelta($tags_posts_sql);
    dbDelta($network_posts_sql);
    dbDelta($tokens_sql);
}

function postcard_install_archive_page()
{
    $archive_page_id = get_option(POSTCARD_ARCHIVE_PAGE);
    if (!$archive_page_id) {
        global $user_ID;
        $page['post_type'] = 'page';
        $page['post_content'] = '';
        $page['post_parent'] = 0;
        $page['post_author'] = $user_ID;
        $page['post_status'] = 'publish';
        $page['post_title'] = 'Social Archive';
        $page['post_content'] = '[postcard-archive]';
        $page['pinged'] = 'ca.bitwit.postcard/social-archive-install';
        $pageid = wp_insert_post($page);
        if ($pageid == 0) {
            die("WARNING: Could not install social gallery page. Permalinks to your social content won't work without this :(");
        } else {
            add_option(POSTCARD_ARCHIVE_PAGE, $pageid);
        }
    }
}