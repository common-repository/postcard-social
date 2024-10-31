<?php
/**
 * Created by PhpStorm.
 * User: kylenewsome
 * Date: 3/20/2014
 * Time: 11:19 AM
 */

function postcard_generate_new_post($postcard)
{
    $post = array(
        "post_title" => postcard_get_post_title($postcard),
        "post_content" => postcard_get_post_content($postcard),
        "post_status" => "publish",
        "post_author" => $postcard["user_id"],
    );

    $tags_and_categories = postcard_get_post_tags_categories($postcard);
    if ($tags_and_categories) {
        $post = array_merge($post, $tags_and_categories);
    }

    $post_id = wp_insert_post($post);
    $new_permalink = get_permalink($post_id);
    $postcard["permalink"] = $new_permalink;
    $postcard["post_id"] = $post_id;
    postcard_evaluate_feature_image_option($postcard, $post_id);

    //Have a way to associate the original content with this post
    //This will allow for future content regeneration if users change their settings among other benefits
    add_post_meta($post_id, "postcard_associated_id", $postcard["id"]);

    return $postcard;
}

//
// Option assessment related functions
// i.e. functions that check an option and return appropriate value/perform action
//

function postcard_get_post_title($postcard)
{
    $title_option = get_option("postcard_auto_post_title");
    switch ($title_option) {
        case 0:
            //The first line of the message
            $first_line = strtok($postcard["message"], "\r\n");
            $title = NULL;
            if (strlen($first_line) > 100) {
                $space_position = NULL;
                preg_match('/\s/', $first_line, $space_position, PREG_OFFSET_CAPTURE, 100);
                if (count($space_position) == 0) {
                    $title = $first_line;
                } else {
                    $title = substr($first_line, 0, $space_position[0][1]);
                }
            } else {
                $title = $first_line;
            }
            return $title;
            break;
        case 1:
        default:
            //Status Update & The Date
            return "Status Update - " . date("Y-m-d", time());
            break;
    }
}

function postcard_get_post_content($postcard){

    $postContent = get_option("postcard_auto_post_content");
    switch($postContent){
        case 0:
            return "[postcard-feed id='" . $postcard["id"] . "']";
            break;
        case 1:
            ob_start();
            include("templates/post-template-default.php");
            return ob_get_clean();
        break;

        default:
            return "";
            break;

    }
}

function postcard_get_post_tags_categories($postcard)
{
    $tags_option = get_option("postcard_auto_post_tag");
    switch ($tags_option) {
        case 0:
            return array("tags_input" => array_merge($postcard["hashtags"], $postcard["private_tags"]));
            break;
        case 1: //categories for the post
            $category_tags = array_merge($postcard["hashtags"], $postcard["private_tags"]);
            $post_categories = postcard_get_and_create_categories_for_tags($category_tags);
            return array("post_category" => $post_categories);
            break;
        case 2: //private tags as categories, #hashtags as tags
            $post_categories = postcard_get_and_create_categories_for_tags($postcard["private_tags"]);
            return array("tags_input" => $postcard["hashtags"], "post_category" => $post_categories);
            break;
        case 3: //Neither
        default:
            return NULL;
            break;
    }
}


function postcard_evaluate_feature_image_option($postcard, $new_post_id)
{
    $feature_image_option = get_option("postcard_auto_post_image_feature");
    if ($feature_image_option == 0 && isset($postcard['image_attachment_id'])) {
        add_post_meta($new_post_id, '_thumbnail_id', $postcard["image_attachment_id"]);
    }
}

//
// Utility Functions called by option assessment functions
//

function postcard_get_and_create_categories_for_tags($tags){
    $post_categories = array();
    $categories = get_categories();
    $category_dictionary = array();
    if ($categories) {
        foreach ($categories as $category) {
            $category_dictionary[$category->name] = $category->term_id;
        }
    }
    require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');
    foreach ($tags as $category_tag) {
        if (isset($category_dictionary[$category_tag])) {
            $post_categories[] = $category_dictionary[$category_tag];
        } else {
            $category_id = wp_create_category($category_tag);
            $post_categories[] = $category_id;
        }
    }
    return $post_categories;
}

function postcard_style_post_message($message) {
    # replace links
    $pattern = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";
    $has_protocol = preg_match("#((http|https|ftp)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#ie", $message);
    if ($has_protocol) {
        $message = preg_replace($pattern, "<a href=\"$1\" target=\"_blank\">$1</a>", $message);
    } else {
        $message = preg_replace($pattern, "<a href=\"http://$1\" target=\"_blank\">$1</a>", $message);
    }
    # replace @ symbols
    $message = preg_replace('/(?<=^|\s)@([a-z0-9_]+)/i', '<a href="http://www.twitter.com/$1">@$1</a>', $message);

    # replace hashtags
    $pattern = "/(?:^|\s)(\#\w+)/";
    preg_match_all($pattern, $message, $matches, PREG_OFFSET_CAPTURE);
    $matches = $matches[1];
    foreach ($matches as $match){
        $tag_url = get_term_link(str_replace("#","", $match[0]), 'post_tag');
        $tag_replacement = '<a href="' . $tag_url . '">' . $match[0] . '</a>';
        $message = str_replace($match[0], $tag_replacement, $message);
    }

    return $message;
}

