<?php

function postcard_success_response($data, $message = null)
{
    header('Content-type: application/json');
    echo json_encode(array(
        "success" => true,
        "payload" => $data,
        "message" => $message
    ));
    die();
}

function postcard_error_response($message = null)
{
    header('HTTP/1.1 400 Bad Request', true, 400);
    header('Content-type: application/json');
    echo json_encode(array(
        "success" => false,
        "message" => $message
    ));
    die();
}

class PostcardApi
{

    private $data = null;
    private $token_data = null;
    private $routes = array(
        "" => "status",
        "status" => "status",
        "authenticate" => "authenticate",
        "post/add" => "post_add",
        "post/add_with_media" => "post_add_with_media",
        "post/delete" => "post_delete",
        "post/get" => "post_get",
        "post/search" => "post_search",
        "user/get" => "user_get",
        "user/picture" => "user_picture"
    );

    public function processRequest($action)
    {
        $method = NULL;
        if (substr($action, -1) == "/") {
            $action = substr($action, 0, -1);
        }

        if (array_key_exists($action, $this->routes)) {
            $method = $this->routes[$action];
        } else {
            postcard_error_response("Bad Method Request => " . $action);
        }
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $this->data = $_GET;
                break;
            case 'POST':
                $this->data = $_POST;
                break;
        }
        call_user_func(array($this, $method));
    }

    private function requireAuth()
    {
        if (!(isset($this->data['token']))) {
            postcard_error_response("Authorization form is invalid");
            return false;
        }

        global $wpdb;
        $tokens_table_name = $wpdb->prefix . "pc_auth_tokens";
        $token_hash = hash("md5", $this->data['token']);

        $row = $wpdb->get_row("SELECT * from $tokens_table_name WHERE token='$token_hash'");
        if ($row && (@time() < strtotime($row->expires))) {
            $this->token_data = $row;
            return true;
        } else {
            postcard_error_response("Invalid or expired authorization");
            return false;
        }
    }

    private function requireFields($array = null)
    {
        foreach ($array as $field) {
            if (!isset($this->data[$field])) {
                postcard_error_response("Missing field '$field'");
            }
        }
    }

    private function requireFiles($array = null)
    {
        foreach ($array as $field) {
            if (!isset($_FILES[$field])) {
                postcard_error_response("Missing file '$field'");
            }
        }
    }

    /**
     * Base Calls
     */

    private function status()
    {
        $data = array(
            "url" => get_bloginfo('url'),
            "title" => get_bloginfo('title'),
            "description" => get_bloginfo('description')
        );
        postcard_success_response($data, "Welcome to the Wordpress Postcard Plugin API");
    }

    private function authenticate()
    {
        $this->requireFields(array("username", "password"));

        $credentials = array(
            "user_login" => $this->data['username'],
            "user_password" => $this->data['password']
        );

        $user = wp_signon($credentials);
        if (is_wp_error($user))
            postcard_error_response(strip_tags($user->get_error_message()));
        else {
            global $wpdb;
            $auth_token = hash("sha256", $this->data['username'] . date("y-m-d H:i:s", @time()));
            $data = array(
                "token" => hash("md5", $auth_token),
                "user_id" => $user->id,
                "expires" => date("Y-m-d H:i:s", strtotime("+10 years"))
            );

            $tokens_table_name = $wpdb->prefix . "pc_auth_tokens";
            $wpdb->insert($tokens_table_name, $data);

            postcard_success_response(array("token" => $auth_token), "successful authentication");
        }
    }

    /**
     * ADD section
     */

    private function post_add()
    {
        $this->requireAuth();
        $this->requireFields(array("date", "message"));
        global $wpdb;

        $postcard = array(
            "user_id" => $this->token_data->user_id,
            "date" => date("Y-m-d H:i:s", strtotime($this->data['date'])),
            "message" => $this->data['message']
        );
        $extras = array();

        if (isset($this->data['url'])) {
            $postcard['url'] = $this->data['url'];
        }

        $posts_table_name = $wpdb->prefix . "pc_postcards";
        $wpdb->insert($posts_table_name, $postcard);
        $postcard["id"] = $wpdb->insert_id;

        require_once("tasks.php");
        $tags = postcard_parse_message_for_tags($postcard);
        $extras['hashtags'] = $tags;
        if (isset($this->data["tags"])) {
            $pvt_tags = explode(",", $this->data["tags"]);
            $extras['private_tags'] = $pvt_tags;
            $tags = array_unique(array_merge($tags, $pvt_tags));
        } else {
            $extras['private_tags'] = array();
        }
        postcard_add_tags($tags, $postcard);

        $postcard['permalink'] = postcard_get_permalink($postcard["id"]);

        $postcard_data = array_merge($postcard, $extras);
        $postcard_data = apply_filters('postcard_new_content', $postcard_data);

        postcard_success_response($postcard_data, "Successfully added postcard");
    }

    /**
     * @description image dimensions are expected to match video dimensions and function as the "cover photo" of the video
     */
    private function post_add_with_media()
    {
        $this->requireAuth();
        $this->requireFields(array("date", "message"));
        $this->requireFiles(array("image"));

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $image_result = wp_handle_upload($_FILES['image'], array("test_form" => false));

        $video_result = NULL;
        if (isset($_FILES['video'])) {
            $video_result = wp_handle_upload($_FILES['video'], array("test_form" => false));
            $did_video_upload_succeed = (isset($video_result['file'])) ? true : false;
            if (!$did_video_upload_succeed) {
                postcard_error_response($video_result['error']);
            }
        }

        if ($image_result['file']) {
            global $wpdb;
            $image = wp_get_image_editor($image_result['file']);
            $image_size = NULL;
            $image_attachment_id = NULL;
            if (!is_wp_error($image)) {
                $image_size = $image->get_size();
                $image->set_quality(100);
                $image->resize(160, 160, true);
                $image->save();

                $wp_filetype = $image_result['type'];
                $filename = $image_result['file'];
                $wp_upload_dir = wp_upload_dir();
                $attachment = array(
                    'guid' => $wp_upload_dir['url'] . '/' . basename($filename),
                    'post_mime_type' => $wp_filetype,
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_author' => $this->token_data->user_id
                );
                $image_attachment_id = wp_insert_attachment($attachment, $filename);
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata( $image_attachment_id, $filename );
                wp_update_attachment_metadata($image_attachment_id, $attach_data);
            }

            $postcard = array(
                "user_id" => $this->token_data->user_id,
                "date" => date("Y-m-d H:i:s", strtotime($this->data['date'])),
                "message" => $this->data['message'],
                "image" => $image_result["url"],
                "width" => $image_size["width"],
                "height" => $image_size["height"],
            );
            $extras = array(
                "image_attachment_id" => $image_attachment_id,
            );

            if ($video_result != NULL) {
                $postcard["video"] = $video_result["url"];
                $wp_filetype = $video_result['type'];
                $filename = $video_result['file'];
                $wp_upload_dir = wp_upload_dir();
                $attachment = array(
                    'guid' => $wp_upload_dir['url'] . '/' . basename($filename),
                    'post_mime_type' => $wp_filetype,
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_author' => $this->token_data->user_id
                );
                $video_attachment_id = wp_insert_attachment($attachment, $filename);
                $extras["video_attachment_id"] = $video_attachment_id;
            }

            $posts_table_name = $wpdb->prefix . "pc_postcards";
            $wpdb->insert($posts_table_name, $postcard);
            $postcard["id"] = $wpdb->insert_id;

            require_once("tasks.php");
            $tags = postcard_parse_message_for_tags($postcard);
            $extras['hashtags'] = $tags;
            if (isset($this->data["tags"])) {
                $pvt_tags = explode(",", $this->data["tags"]);
                $extras['private_tags'] = $pvt_tags;
                $tags = array_unique(array_merge($tags, $pvt_tags));
            } else {
                $extras['private_tags'] = array();
            }
            postcard_add_tags($tags, $postcard);

            $postcard['permalink'] = postcard_get_permalink($postcard["id"]);

            $postcard_data = array_merge($postcard, $extras);
            $postcard_data = apply_filters('postcard_new_content', $postcard_data);

            postcard_success_response($postcard_data, "Successfully added postcard");
        } else {
            postcard_error_response($image_result['error']);
        }
    }

    private function post_get()
    {
        $this->requireFields(array("id"));
        require_once("postcard-functions.php");
        $result = postcard_get_by_id($this->data["id"]);
        $user = postcard_get_user_by_id($result->user_id);
        $result->user = $user;
        if ($result->image != NULL) {
            $result->thumbnail = substr_replace($result->image, "-160x160", -4, 0);
        }
        if ($result) {
            postcard_success_response($result, "Success getting postcard");
        } else {
            postcard_error_response("Error while trying to get postcard");
        }
    }

    private function post_search()
    {
        require_once("postcard-functions.php");
        if ( isset($this->data["tags"])) { //explode tags on the comma if it exists
            $this->data["tags"] = explode(",", $this->data["tags"]);
        }
        $results = postcard_get_collection($this->data);
        if (!is_array($results)) {
            postcard_error_response("Error while trying to get postcards");
        }
        foreach ($results as $result) {
            $user = postcard_get_user_by_id($result->user_id);
            $result->user = $user;
            if ($result->image != NULL) {
                $result->thumbnail = substr_replace($result->image, "-160x160", -4, 0);
            }
        }
        postcard_success_response($results, "Postcard search complete");
    }

    private function post_delete()
    {
        $this->requireAuth();
        $this->requireFields(array("id"));

        global $wpdb;
        $posts_table_name = $wpdb->prefix . "pc_postcards";
        $result = $wpdb->delete($posts_table_name, array("id" => $this->data['id']));

        if ($result)
            postcard_success_response(null, "Successfully deleted postcard");
        else
            postcard_error_response("Error while trying to delete postcard");
    }

    /*
     * USER section
     * */

    //get user by id
    private function user_get()
    {
        $this->requireFields(array("id"));
        require_once("postcard-functions.php");
        $user = postcard_get_user_by_id($this->data["id"]);
        if ($user) {
            postcard_success_response($user, "Postcard user found");
        } else {
            postcard_error_response("User not found");
        }
    }

    // profile picture
    private function user_picture()
    {
        $this->requireFields(array("id"));
        require_once("postcard-functions.php");
        $pic = postcard_profile_picture($this->data["id"]);
        header("Location: $pic");
        die();
    }

}