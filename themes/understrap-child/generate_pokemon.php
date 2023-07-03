<?php
/** Template Name: Generate Pokemon */
	if(current_user_can( 'edit_posts' )){ 

		$post_id = $pokemon_info->generate_random_pokemon();
		if (!empty($post)) wp_redirect(urldecode(get_permalink($post_id)));

	}else{
		$post = get_posts([
		    'post_type'      => 'pokemon',
		    'orderby'        => 'rand',
		    'posts_per_page' => 1, 
		]);

		if (!empty($post)) wp_redirect(urldecode(get_permalink($post[0])));


	}
?>