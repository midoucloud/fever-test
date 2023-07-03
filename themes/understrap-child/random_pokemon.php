<?php 
/** Template Name: Random Pokemon */


$post = get_posts([
    'post_type'      => 'pokemon',
    'orderby'        => 'rand',
    'posts_per_page' => 1, 
]);

if (!empty($post)) wp_redirect(urldecode(get_permalink($post[0])));

?>