<?php
/*
 * Template Name: Pokemon template
 * Template Post Type: pokemon
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();
$container = get_theme_mod( 'understrap_container_type' );
?>

<div class="wrapper" id="single-wrapper">

	<div class="<?php echo esc_attr( $container ); ?>" id="content" tabindex="-1">

		<div class="row">

			<?php
			// Do the left sidebar check and open div#primary.
			get_template_part( 'global-templates/left-sidebar-check' );
			?>

			<main class="site-main" id="main">
				<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">

					<header class="entry-header">

						<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

						<div class="entry-meta">

							<?php understrap_posted_on(); ?>

						</div><!-- .entry-meta -->

					</header><!-- .entry-header -->

					<?php echo get_the_post_thumbnail( $post->ID, 'large' ); ?>

					<div class="entry-content">

						<?php
						//the_content();

						echo "<br>Aditional information</b>";
						$data = $digimon_info->get_digimon_info(strtolower(get_the_title()));
						$html = '<ul id="digimon-info"><li><b>Type</b>: '.ucfirst($data->digimon_type).'</li>';
						$html .= '<li><b>Number</b>: '.$data->id.'</li>';
						echo $html;
						understrap_link_pages();

						$skills = $digimon_info->get_digimon_skillset(strtolower(get_the_title())); 
						if($skills){
						?>
						<br><h3>Move list</h3>
						<table class="table table-responsive table-hover">
						  <thead>
						    <tr>
						      <th scope="col">Name</th>
						      <th scope="col">Description</th>
						    </tr>
						  </thead>
						  <tbody>
						  	<?php
						  	foreach($skills as $s){
						  	?>
						  	<tr>
						  		<td><?php echo ucfirst(str_replace('-','',$s->skill_name));?></td>
						  		<td><?php echo $s->skill_description;?></td>
						  	</tr>
						  	<?php
						  	}
						  	?>
						  </tbody>
						</table>
						<?php
						}
						?>

					</div><!-- .entry-content -->

					<footer class="entry-footer">

						<?php understrap_entry_footer(); ?>

					</footer><!-- .entry-footer -->

				</article><!-- #post-<?php the_ID(); ?> -->
	

			</main>

			<?php
			// Do the right sidebar check and close div#primary.
			get_template_part( 'global-templates/right-sidebar-check' );

			?>

		</div><!-- .row -->

	</div><!-- #content -->

</div><!-- #single-wrapper -->

<?php
get_footer();
?>
<script>
function get_pk_id(){
 jQuery.getJSON("<?php echo get_site_url().'/wp-json/pokemon/v2/id/'.strtolower(get_the_title()); ?>", function(result){
      console.log(result);
      jQuery('#pkd_button').hide();
      jQuery('#old_number').html("<b>Pokedex old number:</b> "+result);
      jQuery('#old_number').show();
  });
}
</script>
