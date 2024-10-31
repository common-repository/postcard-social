<?php

function postcard_parse_message_for_tags($postcard){
    $pattern = "/(?:^|\s)(\#\w+)/";
    preg_match_all($pattern, $postcard["message"], $matches, PREG_OFFSET_CAPTURE);
    $matches = $matches[0];  # only need first array of matches
    $tags = array();
    foreach ($matches as $match){
        $tags[] = trim(str_replace("#","", $match[0]));
    }
    return $tags;
}

function postcard_add_tags($tags = array(), $postcard){
   global $wpdb;
   $tags_table_name = $wpdb->prefix . "pc_tags";
   $tags_posts_table_name = $wpdb->prefix . "pc_tags_posts";
   foreach($tags as $tag){
       $tag_id = $wpdb->get_var("SELECT id FROM $tags_table_name WHERE tag='$tag'");
       if(!$tag_id){
           $wpdb->insert($tags_table_name, array("tag" => $tag));
           $tag_id = $wpdb->insert_id;
       }
       $wpdb->insert($tags_posts_table_name, array("tag_id" => $tag_id, "postcard_id" => $postcard["id"]));
   }
}

function postcard_get_permalink($id){
    if( defined("POSTCARD_ARCHIVE_PAGE_ID") ){
        $page_id = POSTCARD_ARCHIVE_PAGE_ID;
    } else {
        $page_id = (int)get_option(POSTCARD_ARCHIVE_PAGE);
        define("POSTCARD_ARCHIVE_PAGE_ID", $page_id);
    }
    $url =  get_bloginfo("url") . "?page_id=" . $page_id . "&pc_id=$id";
    return $url;
}