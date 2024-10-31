<meta property="og:title" content="<?php echo $postcard->message ?>"/>
<meta property="og:type" content="website"/>
<meta property="og:url" content="<?php echo postcard_get_permalink($postcard->id); ?>"/>
<meta property="og:site_name" content="<?php echo get_bloginfo('name'); ?>"/>
<meta property="og:description" content="<?php echo get_bloginfo('description'); ?>"/>
<?php if ($postcard->video): ?>
    <!-- Video -->
    <!-- Facebook Linter Info -->
    <meta property="og:image" content="<?php echo $postcard->image ?>"/>
    <meta property="og:video" content="<?php echo $postcard->video ?>"/>
    <meta property="og:video:width" content="<?php echo $video_width; ?>"/>
    <meta property="og:video:height" content="<?php echo $video_height; ?>"/>
    <!-- Twitter Card info -->
    <meta name="twitter:card" content="player">
    <meta name="twitter:site" content="@postcardsocial">
    <meta name="twitter:creator" content="@postcardsocial">
    <meta name="twitter:url" content="<?php echo postcard_get_permalink($postcard->id); ?>">
    <meta name="twitter:title" content="<?php echo get_bloginfo('name') ?>">
    <meta name="twitter:description" content="<?php echo $postcard->message ?>">
    <meta name="twitter:image:src" content="<?php echo $postcard->image ?>">
    <meta name="twitter:player" content="<?php echo $video_player_url ?>">
    <meta name="twitter:player:width" content="<?php echo $video_width ?>">
    <meta name="twitter:player:height" content="<?php echo $video_height ?>">
    <meta name="twitter:player:stream" content="<?php echo $postcard->video ?>">
    <meta name="twitter:player:stream:content_type" content="video/mp4;">
<?php elseif ($postcard->image): ?>
    <!-- Image -->
    <!-- FB Meta -->
    <meta property="og:image" content="<?php echo $postcard->image ?>"/>
    <!-- TW Meta -->
    <meta name="twitter:card" content="photo"/>
    <meta name="twitter:image:src" content="<?php echo $postcard->image ?>"/>
<?php endif;