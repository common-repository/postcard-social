<?php

if (isset($_POST['delete_id'])):
    $result = postcard_delete_by_id($_POST['delete_id']);
    ?>
    <div id="message" class="updated below-h2">
        <?php if ($result): ?>
            <p>Deleted postcard successfully</p>
        <?php else: ?>
            <p>Couldn't delete postcard</p>
        <?php endif; ?>
    </div>
<?php
endif;

if (isset($_POST['postcard-edit-id'])){
    global $wpdb;
    $postcard = array(
        "date" => date("Y-m-d H:i:s", strtotime($_POST['postcard-edit-date'])),
        "message" => $_POST['postcard-edit-message'],
    );

    if (isset($_POST['postcard-edit-url']) && $_POST['postcard-edit-url'] != "") {
       $postcard["url"] = $_POST['postcard-edit-url'];
    }

    if (isset($_POST['postcard-edit-image']) && $_POST['postcard-edit-image'] != "") {
       $postcard["image"] = $_POST['postcard-edit-image'];
    }

    if (isset($_POST['postcard-edit-video']) && $_POST['postcard-edit-video'] != "") {
       $postcard["video"] = $_POST['postcard-edit-video'];
    }

    $posts_table_name = $wpdb->prefix . "pc_postcards";
    $result = $wpdb->update($posts_table_name, $postcard, array("id" => $_POST['postcard-edit-id']));
    ?>
    <div id="message" class="updated below-h2">
        <?php if ($result): ?>
            <p>Updated postcard successfully</p>
        <?php else: ?>
            <p>Couldn't update postcard</p>
        <?php endif; ?>
    </div>
<?php
}


$api_endpoint = postcard_get_api_endpoint();
?>
    <style>
        div.postcard-listing {
            border: 1px solid #ABABAB;
            background: #EFEFEF;
            margin: 10px;
            padding: 10px;
            font-weight: lighter;
            font-size: 18px;
        }

        dl {
            clear: both;
            margin-top: 10px;
        }

        dt {
            font-weight: bold;
            margin: 20px 0 10px 0;
        }

        img.postcard-image {
            max-width: 480px;
        }

        ol.postcard-options {
            list-style-type: none;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }

        button.postcard-delete, button.postcard-edit, button.postcard-save {
            float: left;
            border: none;
            border-radius: 4px;
            background-color: #ff6437;
            color: #ffffff;
            padding: 12px;
            cursor: pointer;
        }

        button:hover {
            opacity: 0.9;
        }

        button:active {
            opacity: 0.8;
        }

        div.postcard-edit-area {
            display: none;
        }

        .postcard-edit-area input, .postcard-edit-area textarea {
            width: 85%;
        }

    </style>
    <script>
        jQuery(document).ready(function () {
            jQuery("button.postcard-edit").on("click", function () {
                jQuery(".postcard-edit-area").show();
                jQuery(".postcard-listings").hide();
                var postcard_id = jQuery(this).data("id");
                jQuery.get("<?php echo $api_endpoint; ?>&endpoint=post/get&id=" + postcard_id, function (data, status) {
                    if (data.success == true) {
                        var postcard = data.payload;
                        //console.log("postcard", postcard);
                        jQuery("#postcard-edit-id").val(postcard.id);
                        jQuery("#postcard-edit-date").val(postcard.date);
                        jQuery("#postcard-edit-message").val(postcard.message);
                        jQuery("#postcard-edit-url").val(postcard.url);
                        jQuery("#postcard-edit-image").val(postcard.image);
                        jQuery("#postcard-edit-video").val(postcard.video);
                        // jQuery("#postcard-edit-tags").val(postcard.tags);
                    }
                });
            });
        });
    </script>
    <div class="postcard-edit-area postcard-listing">
        <h2>Edit Postcard</h2>

        <form method="post">
            <input type="hidden" id="postcard-edit-id" name="postcard-edit-id"/>
            <button class="postcard-save">Save</button>
            <dl>
                <dt>Date</dt>
                <dd>
                    <input id="postcard-edit-date" name="postcard-edit-date" type="text"/>
                </dd>

                <dt>Message</dt>
                <dd>
                    <textarea id="postcard-edit-message" name="postcard-edit-message">
                    </textarea>
                </dd>

                <dt>Link</dt>
                <dd>
                    <input id="postcard-edit-url" name="postcard-edit-url" type="text"/>
                </dd>

                <dt>Image</dt>
                <dd>
                    <input id="postcard-edit-image" name="postcard-edit-image" type="text"/>
                </dd>

                <dt>Video</dt>
                <dd>
                    <input id="postcard-edit-video" name="postcard-edit-video" type="text"/>
                </dd>
                <!--
                <dt>Tags</dt>
                <dd>
                    <input id="postcard-edit-tags" name="postcard-edit-tags" type="text"/>
                </dd>
                -->
            </dl>
        </form>
    </div>

    <div class="postcard-listings">
        <h2>Postcard Listings</h2>
        <?php
        $collection = postcard_get_collection(array("limit" => 50));
        foreach ($collection as $postcard):
            ?>
            <div class="postcard-listing">
                <ol class="postcard-options">
                    <li>
                        <form method="post">
                            <input type="hidden" name="delete_id" value="<?php echo $postcard->id; ?>"/>
                            <button class="postcard-delete">Delete</button>
                        </form>
                        <button class="postcard-edit" data-id="<?php echo $postcard->id; ?>">Edit</button>
                    </li>
                </ol>
                <dl>
                    <dt>Posted By</dt>
                    <dd class="postcard-name"><?php echo get_the_author_meta("display_name", $postcard->user_id); ?></dd>

                    <dt>Date</dt>
                    <dd><?php echo $postcard->date; ?></dd>

                    <?php if ($postcard->message): ?>
                        <dt>Message</dt>
                        <dd><?php echo $postcard->message; ?></dd>
                    <?php endif; ?>

                    <?php if ($postcard->url): ?>
                        <dt>Link</dt>
                        <dd><?php echo $postcard->url; ?></dd>
                    <?php endif; ?>

                    <?php if ($postcard->image): ?>
                        <dt>Image</dt>
                        <dd><img class="postcard-image" src="<?php echo $postcard->image; ?>"/></dd>
                    <?php endif; ?>

                    <?php if ($postcard->video): ?>
                        <dt>Video</dt>
                        <dd><?php echo $postcard->video; ?></dd>
                    <?php endif; ?>


                    <?php
                    $tags = postcard_get_tags_for_id($postcard->id);
                    if (count($tags) > 0):
                        ?>
                        <dt>Tags</dt>
                        <dd><?php
                            echo implode(", ", $tags);
                            ?>
                        </dd>
                    <?php
                    endif;
                    ?>

                </dl>
            </div>
        <?php
        endforeach;
        ?>
    </div>
<?php
