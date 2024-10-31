<?php echo nl2br(stripslashes(postcard_style_post_message($postcard["message"]))); ?>
<br/>
<?php
if (isset($postcard["url"])):?>
<a target="_blank" class="postcard-link" href="<?php echo $postcard["url"] ?>">Visit Link</a><?php endif;
if (isset($postcard["video"])): ?>
<video id="postcard-video-<?php echo $postcard["id"]; ?>" poster="<?php echo $postcard["image"]; ?>" width="<?php echo $postcard["width"]; ?>" height="<?php echo $postcard["height"]; ?>" class="video-js vjs-default-skin" controls loop preload="auto">
    <source src="<?php echo $postcard["video"]; ?>" type="video/mp4">
</video><?php
elseif (isset($postcard["image"])): ?>
    <img class="aligncenter size-full wp-image-<?php echo $postcard["image_attachment_id"]; ?>" src="<?php echo $postcard["image"]; ?>"><?php
endif;
