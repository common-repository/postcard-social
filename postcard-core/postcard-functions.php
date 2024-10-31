<?php

function postcard_gallery($options=null){
    $results = postcard_get_collection( array_merge($options, array("image" => true)));
    if(!is_array($results)){
        $results = array($results); //puts a single into an array (this occurs due to get_by_id calls)
    }
    ob_start();
    echo "<ul class='postcard-gallery'";
    if(isset($options["tags"]) && $options["tags"] != NULL) {
        echo " data-params='tags=". implode(",", $options["tags"]) . "'";
    }
    echo ">";

    foreach($results as $postcard):
?>
     <li class="postcard-container" data-postcard-id="<?php echo $postcard->postcard_id; ?>">
        <img class="thumbnail" src="<?php echo substr_replace($postcard->image,"-160x160", -4, 0); ?>">
<?php
        if($postcard->video){
            echo '<span class="video-indicator">video</span>';
        }
 ?>
     </li>
<?php
    endforeach;
    echo "</ul>";
    if(!defined("POSTCARD_MODAL_WINDOW_INSERTED")):
        define("POSTCARD_MODAL_WINDOW_INSERTED", TRUE);
?>
    <div id="postcard-modal-window">
        <div class="vertical-center">
            <div class="postcard-modal-container">
                <div class="postcard-modal pc-main">
                    <div class="media-section">
                        <div class="media-container">
                        </div>
                    </div>
                    <div class="message-section">
                        <div class="info-container">

                        </div>
                        <div class="message-container">
                            <span class="value">
                            </span>
                        </div>
                    </div>
                </div>
                <button class="prev">Previous</button>
                <button class="next">Next</button>
            </div>
        </div>
    </div>
<?php
    endif;
    return ob_get_clean();
}

function postcard_feed($options=null){
    require_once("tasks.php");
    $results = postcard_get_collection($options);
    if(!is_array($results)){
        $results = array($results); //puts a single into an array (this occurs due to get_by_id calls)
    }
    ob_start();
    if($results):
        $profile_pics = array();
        echo "<ul class='postcard-feed'>";
        foreach($results as $postcard):
            if (isset($profile_pics[$postcard->user_id])){
                $profile = $profile_pics[$postcard->user_id];
            } else {
                $profile_pics[$postcard->user_id] = $profile = postcard_profile_picture($postcard->user_id);
            }
            echo "<li class='postcard-container'>";
?>
            <div class="info-container">
                <img class="profile-pic" src="<?php echo $profile; ?>" />
                <div class="date">
                    <span class="value">
                        <?php echo date("M d\<\b\\r\/\>Y", strtotime($postcard->date)) ?>
                    </span>
                </div>
            </div>
            <div class="message-container">
                <span class="postcard-user-title"><?php echo get_the_author_meta( "display_name", $postcard->user_id ); ?> posted:</span>
                <span class="postcard-message"><?php echo nl2br(stripslashes(postcard_style_message($postcard->message))); ?></span>
                <br/>
                <?php if($postcard->url != null): ?>
                    <a target="_blank" class="postcard-link" href="<?php echo $postcard->url ?>">
                        Visit Link
                    </a>
                <?php endif; ?>
                <a class="postcard-permalink" href="<?php echo postcard_get_permalink($postcard->postcard_id); ?>">Permalink</a>
            </div>
            <div class="media-container" style="width: <?php echo $postcard->width; ?>px; height: <?php echo $postcard->height; ?>px;">
                <?php if($postcard->video != null): ?>
                <div class="video-container">
                    <video id="postcard-video-<?php echo $postcard->postcard_id; ?>" data-poster="<?php echo $postcard->image; ?>" data-width="<?php echo $postcard->width; ?>" data-height="<?php echo $postcard->height; ?>" class="video-js vjs-default-skin" controls loop>
                        <source src="<?php echo $postcard->video; ?>" type="video/mp4">
                    </video>
                </div>
                <?php endif; ?>
                <?php if($postcard->image != null): ?>
                <div class="image-container">
                    <img class="feed-image" src="<?php echo $postcard->image; ?>">
                </div>
                <?php endif; ?>
            </div>
<?php
            echo "</li>";
        endforeach;
        echo "</ul>";
    else:
?>
    <div class="no-results">
        Sorry, no Postcard results were found.
    </div>
<?php
    endif;
    return ob_get_clean();
}

function postcard_profile_picture($id = null){
    $results = postcard_get_collection(array("user_id" => $id, "tags" => array("profile"), "limit" => 1));
    if($results){
        return substr_replace($results[0]->image,"-160x160", -4, 0);
    } else {
        return get_bloginfo('url') . "/wp-content/plugins/postcard-plugin/postcard-core/img/user-blank.png";
    }
}

function postcard_process_gallery_shortcode($atts){
    $data = postcard_process_shortcode($atts);
    return postcard_gallery($data['options']);
}

function postcard_process_feed_shortcode($atts){
    $data = postcard_process_shortcode($atts);
    return postcard_feed($data['options']);
}

function postcard_process_archive_shortcode(){
    $pc_id = NULL;
    $types = NULL;
    $tags = NULL;
    $count = 10;
    extract($_GET, EXTR_IF_EXISTS);
    $params = array(
        'id' => $pc_id,
        'limit' => $count
    );

    if ($tags != NULL){
        $tags = explode(",", $tags);
        $params['tags'] = $tags;
    }

    if ($types != NULL){
        $types = explode(",", $types);
        foreach($types as $type){
            $params[$type] = true;
        }
    }

    return postcard_feed($params);
}

function postcard_process_shortcode($atts){
    extract( shortcode_atts( array(
        'id' => NULL,
        'types' => NULL,
        'tags' => NULL,
        'count' => 10
    ), $atts ) );

    $options = array(
        "limit" => $count,
        "id" => $id
    );

    if ($tags != NULL){
        $tags = explode(",", $tags);
        $options['tags'] = $tags;
    }

    if ($types != NULL){
        $types = explode(",", $types);
        foreach($types as $type){
            $options[$type] = true;
        }
    }

    return array("id" => $id, "options" => $options);
}

function postcard_get_by_id($id){
    global $wpdb;
    $postcards_table = $wpdb->prefix . "pc_postcards";
    $query = "SELECT *, $postcards_table.id as postcard_id FROM $postcards_table WHERE id='$id'";
    $result = $wpdb->get_row($query);
    return $result;
}

function postcard_get_collection($params = null){
    # if the id parameter is included, we ignore all other params and fetch it directly with a different query
    if(isset($params["id"]) && $params["id"] != NULL){
        return postcard_get_by_id($params["id"] );
    }

    global $wpdb;
    $postcards_table = $wpdb->prefix . "pc_postcards";
    $tags_table = $wpdb->prefix . "pc_tags";
    $tags_posts_table = $wpdb->prefix . "pc_tags_posts";
    $defaults = array(
        "user_id" => NULL, # specific user id
        "text" => NULL,  # query by postcard text
        "url" => false,  # BOOL for url only postcards (non-media)
        "video" => false,  # BOOL for video or not
        "image" => false,  # BOOL for image or not
        "tags" => NULL,  # array of tags, if any
        "limit" => 10,  # how many to retrieve
        "order" => "id DESC", # order by?
        "group" => "id", # not likely to be changed, but we group responses by id to avoid duplicates when querying by multiple tags
        "since" => NULL, # can be either an id or a DATETIME stamp
        "before" => NULL # can be either an id or a DATETIME stamp
    );
    if($params == null){
        $options = $defaults;
    } else {
        $options = array_merge($defaults, $params);
    }

    $joins = array();
    $where_and_conditions = array();
    $where_or_conditions = array();

    if($options["user_id"] != NULL){
        $where_and_conditions[] = "user_id = '" . $options["user_id"] . "'";
    }

    if($options["url"] == true){
        $where_and_conditions[] = "NOT url IS NULL";
    }

    if($options["image"] == true){
        $where_and_conditions[] = "NOT image IS NULL";
    }

    if($options["video"] == true){
        $where_and_conditions[] = "NOT video IS NULL";
    }

    if (is_array($options["tags"])){
        $count = count($options["tags"]);
        $query = "SELECT * FROM $tags_table WHERE ";
        foreach($options["tags"] as $index => $tag){
            $query .= "tag='$tag'";
            if($index < $count - 1){
                $query .= " OR ";
            }
        }
        $results = $wpdb->get_results($query);
        if($results){
            $joins[] = "LEFT JOIN $tags_posts_table ON $postcards_table.id = $tags_posts_table.postcard_id";
            foreach($results as $result){
                $where_or_conditions[] = "$tags_posts_table.tag_id = $result->id";
            }
        } else {
            //we need this to fail and return no results because the tag doesn't even exist
            $where_and_conditions[] = "1=0";
        }
    }

    if($options["before"] != NULL){
        $before = $options["before"];
        if(is_numeric($before)){
            $options["order"] = "id DESC";
            $where_and_conditions[] = "$postcards_table.id < $before";
        } else {
            $options["order"] = "date DESC";
            $before = date("Y-m-d H:i:s", strtotime($before));
            $where_and_conditions[] = "$postcards_table.date < $before";
        }

    } elseif($options["since"] != NULL){
        $since = $options["since"];
        if(is_numeric($since)){
            $options["order"] = "id ASC";
            $where_and_conditions[] = "$postcards_table.id > $since";
        } else {
            $options["order"] = "date ASC";
            $since = date("Y-m-d H:i:s", strtotime($since));
            $where_and_conditions[] = "$postcards_table.date > $since";
        }
    }

    # Combine all joins into a string
    if( count($joins) > 0){
        $join_statement = implode(" ", $joins);
    } else {
        $join_statement = "";
    }

    # Evaluate all the where segments and combine into a string
    $where_statement = "";
    if( count($where_and_conditions) > 0){
        $where_statement = implode(" AND ", $where_and_conditions);
    }

    if( count($where_or_conditions) > 0){
        if($where_statement != "")
            $where_statement .= " AND ";
        $where_statement .= "(" . implode(" OR ", $where_or_conditions) . ")";
    }

    if ($where_statement != ""){
        $where_statement = "WHERE " . $where_statement;
    }

    $query = sprintf("SELECT *, $postcards_table.id as postcard_id FROM $postcards_table $join_statement $where_statement GROUP BY $postcards_table.%s HAVING COUNT($postcards_table.%s) >= 1 ORDER BY $postcards_table.%s LIMIT %s", $options["group"], $options["group"], $options["order"], $options["limit"]);

    $results = $wpdb->get_results($query);
    return $results;
}

function postcard_get_user_by_id($id){
    global $wpdb;
    $user_table = $wpdb->prefix . "users";
    $query = "SELECT * FROM $user_table WHERE id='$id'";
    $result = $wpdb->get_row($query);
    if($result){
        $data = array(
            "id" => $id,
            "username" => $result->user_login,
            "display_name" => $result->display_name,
            "picture" => postcard_profile_picture($id)
        );
    } else {
        $data = NULL;
    }
    return $data;
}

function postcard_get_tags_for_id($postcard_id){
    global $wpdb;
    $tags_table = $wpdb->prefix . "pc_tags";
    $tags_postcards_table = $wpdb->prefix . "pc_tags_posts";
    $query = "SELECT tag FROM $tags_table WHERE id IN (SELECT tag_id FROM $tags_postcards_table WHERE postcard_id='$postcard_id')";
    $results = $wpdb->get_results($query);

    $data = array();
    foreach($results as $result){
        $data[] = $result->tag;
    }

    return $data;
}

function postcard_delete_by_id($postcard_id){
    global $wpdb;
    $postcards_table = $wpdb->prefix . "pc_postcards";
    $tags_postcards_table = $wpdb->prefix . "pc_tags_posts";
    $postcard_result = $wpdb->delete( $postcards_table, array( 'id' => $postcard_id ) );
    $tags_result = $wpdb->delete( $tags_postcards_table, array( 'postcard_id' => $postcard_id ) );
    return TRUE;
}

function postcard_relative_date($time){
    $date = strtotime($time);
    $diff = ((mktime() - $date) / 1000);
    $day_diff =  floor($diff / 86400);

    if ( is_numeric($day_diff) || $day_diff < 0 ) return null;

    if($day_diff == 0)	{
        if($diff < 60) return floor($diff) + "s";
        if($diff < 120) return "1m";
        if($diff < 3600) return floor($diff/60) + "m";
        if($diff < 7200) return "1h";
        if($diff < 86400) return floor($diff/3600) + "h";
    }
    else {
        if($day_diff == 1)
            return "1d";
        if($day_diff < 7)
            return $day_diff + "d";
        if($day_diff == 7)
            return "1w";
        if($day_diff > 7)
            return ceil($day_diff / 7) + "w";
    }

    return null;
}

function postcard_style_message($message) {
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
    $message = preg_replace('/(^|\s)#(\w*[a-zA-Z_]+\w*)/', '$1<a href="http://twitter.com/search?q=%23$2">#$2</a>', $message);
    return $message;
}