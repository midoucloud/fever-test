<?php
/*
Plugin Name: Pokemon Information
Description: Generate a custom post type for Pokemon and retrieve Pokemon information from PokeApi
Version: 1.0
Author: Pedro Blanco
*/
// Plugin Folder Path
if (! defined ('POKEINFO_PLUGIN_PATH'))
define ('POKEINFO_PLUGIN_PATH', plugin_dir_path ( __FILE__ ) );

// Plugin Root File
if (! defined ('POKEINFO_PLUGIN_FILE'))
	define ('POKEINFO_PLUGIN_FILE', plugin_basename ( __FILE__ ) );

class pokemon_info{

	/** plugin version number */
	const VERSION = '1.00';

	public function __construct() {
		//ejecuta instalación de tabla si no existe al activar plugin
		register_activation_hook ( POKEINFO_PLUGIN_FILE, array($this,'install_updgrade' ) );
		add_action( 'plugins_loaded', array($this,'install_updgrade' ) ); 
		add_action( 'init', array($this,'create_posttype_pokemon'));
		add_action('edit_form_after_title', array($this,'pokemon_post_infoxbox'));
		add_action('save_post', array($this,'save_pokemon_information'),100);
		add_action( 'delete_post', array($this,'delete_pokemon'),100);
		/* rest api calls init for json api */
		add_action( 'rest_api_init', function () {
			register_rest_route( 'pokemon/v2', '/id/(?P<pokemon_name>[a-zA-Z0-9-% ]+)/', array(
			    'methods' => 'GET',
			    'callback' => array($this,'rest_pk_data_id')
		  ));
		});
		add_action( 'rest_api_init', function () {
			register_rest_route( 'pokemon/v2', '/pokemon_list/(?P<pokemon_name>[a-zA-Z0-9-% ]+)/', array(
			    'methods' => 'GET',
			    'callback' => array($this,'rest_pk_data')
		  ));
		});
		add_action( 'rest_api_init', function () {
			register_rest_route( 'pokemon/v2', '/pokemon_list/', array(
			    'methods' => 'GET',
			    'callback' => array($this,'get_pokemon_list')
		  ));
		});

	}

	/* Install tables needed when plugin is activated */
	public function install_updgrade() {
		global $wpdb;
		$installed_ver = get_option( "pokemon_info_version" );

		if ( $installed_ver != self::VERSION ) {
			// install default settings, terms, etc
			if (!function_exists('dbDelta')) {
				include_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			}

			$table_pokemon = $wpdb->prefix . 'pokemon';
			$query1 = "CREATE TABLE IF NOT EXISTS $table_pokemon (
				  `id` int(11) NOT NULL,
				  `pokemon_name` varchar(100) DEFAULT NULL,
				  `pokemon_description` text DEFAULT NULL,
				  `primary_type` varchar(30) DEFAULT NULL,
				  `second_type` varchar(30) DEFAULT NULL,
				  `weight` double DEFAULT NULL,
				  `pokedex_order` int(11) DEFAULT NULL,
				  `post_id` bigint(20) unsigned NOT NULL,
				  `image_url` varchar(255) DEFAULT NULL,
				  KEY `pokemon_ibfk_1` (`post_id`),
				  KEY `id` (`id`),
				  CONSTRAINT `pk_pokemon_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `pk_posts` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
			dbDelta( $query1 );

			$table_pokemon_moves = $wpdb->prefix . 'pokemon_moves';
			$query2 = "CREATE TABLE IF NOT EXISTS $table_pokemon_moves (
					  `move_name` varchar(50) NOT NULL,
					  `move_type` varchar(50) DEFAULT NULL,
					  `move_description` text DEFAULT NULL,
					  PRIMARY KEY (`move_name`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
			dbDelta( $query2 );

			$table_pokemon_moves_rel = $wpdb->prefix . 'pokemon_moves_rel';
			$query3 = "CREATE TABLE IF NOT EXISTS $table_pokemon_moves_rel (
				  `pokemon_id` int(11) DEFAULT NULL,
				  `move_name` varchar(50) DEFAULT NULL,
				  UNIQUE KEY `pokemon_id` (`pokemon_id`,`move_name`),
				  KEY `pk_pokemon_moves_rel_ibfk_2` (`move_name`),
				  CONSTRAINT `pk_pokemon_moves_rel_ibfk_1` FOREIGN KEY (`pokemon_id`) REFERENCES `$table_pokemon` (`id`) ON DELETE CASCADE,
				  CONSTRAINT `pk_pokemon_moves_rel_ibfk_2` FOREIGN KEY (`move_name`) REFERENCES `$table_pokemon_moves` (`move_name`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
			dbDelta( $query3 );

			update_option( "pokemon_info_version", self::VERSION);
		}
	}

	/* Add pokemon post type to wordpress */
	public function create_posttype_pokemon() {
	    $labels = array(
	        'name'                => _x( 'Pokemon', 'Post Type General Name', 'understrap' ),
	        'singular_name'       => _x( 'Pokemon', 'Post Type Singular Name', 'understrap' ),
	        'menu_name'           => __( 'Pokemon', 'understrap' ),
	        'parent_item_colon'   => __( 'Parent Pokemon', 'understrap' ),
	        'all_items'           => __( 'All Pokemon', 'understrap' ),
	        'view_item'           => __( 'View Pokemon', 'understrap' ),
	        'add_new_item'        => __( 'Add New Pokemon', 'understrap' ),
	        'add_new'             => __( 'Add New', 'understrap' ),
	        'edit_item'           => __( 'Edit Pokemon', 'understrap' ),
	        'update_item'         => __( 'Update Pokemon', 'understrap' ),
	        'search_items'        => __( 'Search Pokemon', 'understrap' ),
	        'not_found'           => __( 'Not Found', 'understrap' ),
	        'not_found_in_trash'  => __( 'Not found in Trash', 'understrap' ),
	    );
	      
	// Set other options for Custom Post Type
	      
	    $args = array(
	        'label'               => __( 'Pokemon', 'understrap' ),
	        'description'         => __( 'Pokemon information', 'understrap' ),
	        'labels'              => $labels,
	        // Features this CPT supports in Post Editor
	        'supports'            => array( 'title', 'editor', 'thumbnail'),
	        // You can associate this CPT with a taxonomy or custom taxonomy. 
	        //'taxonomies'          => array( 'types' ),
	        /* A hierarchical CPT is like Pages and can have
	        * Parent and child items. A non-hierarchical CPT
	        * is like Posts.
	        */
	        'hierarchical'        => false,
	        'public'              => true,
	        'show_ui'             => true,
	        'show_in_menu'        => true,
	        'show_in_nav_menus'   => true,
	        'show_in_admin_bar'   => true,
	        'menu_position'       => 5,
	        'can_export'          => true,
	        'has_archive'         => true,
	        'exclude_from_search' => false,
	        'publicly_queryable'  => true,
	        'capability_type'     => 'post',
	        'show_in_rest' => true,
	  
	    );
	      
	    // Registering your Custom Post Type
	    register_post_type( 'Pokemon', $args );
	}

	/* Short information for user when creating a new pokemon */
	public function pokemon_post_infoxbox(){
	  global $post;

	  // confirm if the post_type is 'pokemon'
	  if ($post->post_type!== 'pokemon')
	    return;

	   // here goes your message
	   echo '<div>Simply enter the name of the Pokemon and the text you wish to enter and the system will complete the rest of the data.</div>';
	}

	/* perform api call to pokeApi */
	public function call_pokeapi($method){
		$url = 'https://pokeapi.co/api/v2/'.$method;

		$ch = curl_init();

        $timeout = 5;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($http_code != 200) {
            return json_encode('An error has occured.');
        }

        return $data;
    }

    /* get pokemon data from pokeApi */
    public function get_pokemon($pokemon_name){
    	$method = 'pokemon/'.$pokemon_name;
        return $this->call_pokeapi($method);
    }

    /* get pokemon move data from pokeApi */
    public function get_pokemon_move($move){
    	$method = 'move/'.$move; 
        return $this->call_pokeapi($method);
    }

    /* Download pokemon photo and attach to the post */
    public function download_pokemon_image($post_id,$image_url){
    	global $wpdb;

		$upload_dir = wp_upload_dir();
		$image_data = file_get_contents( $image_url );
		$filename = basename( $image_url );

		if ( wp_mkdir_p( $upload_dir['path'] ) ) {
		  $file = $upload_dir['path'] . '/' . $filename;
		}
		else {
		  $file = $upload_dir['basedir'] . '/' . $filename;
		}

		file_put_contents( $file, $image_data );

		$wp_filetype = wp_check_filetype( $filename, null );

		$attachment = array(
		  'post_mime_type' => $wp_filetype['type'],
		  'post_title' => sanitize_file_name( $filename ),
		  'post_content' => '',
		  'post_status' => 'inherit'
		);

		$attach_id = wp_insert_attachment( $attachment, $file );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		set_post_thumbnail( $post_id, $attach_id ); 
    }

    /* save pokemon and adds data from the pokeApi */
	public function save_pokemon_information(){
		global $wpdb,$post;
		
		if(isset($_POST) && isset($_POST['post_title'])){
			if (isset($post) && $post->post_type == 'pokemon'){ 
				$post_title = $_POST['post_title']; 
				$post_id = get_the_ID(); 
				$post_content = get_the_content();

				$pokemon_name = strtolower($post_title); 
				$data = json_decode($this->get_pokemon($pokemon_name),true);
				if(isset($data['id'])){
					$primary_type = $data['types'][0]['type']['name'];
					if(isset($data['types'][1]['type']['name'])){$second_type=$data['types'][1]['type']['name'];}else{$second_type=null;}
					$data_save = array(
						'id' => $data['id'],
						'pokemon_name' => $data['name'],
						'pokemon_description' => $post_content,
						'primary_type' => $primary_type,
						'second_type' => $second_type,
						'weight' => $data['weight'],
						'pokedex_order' => $data['order'],
						'post_id' => $post_id
					);
					$table_pokemon = $wpdb->prefix . 'pokemon';
					$wpdb->get_results("SELECT * FROM ".$table_pokemon." WHERE post_id = ".$post_id); 
					if($wpdb->num_rows == 0){
						$wpdb->insert($table_pokemon, $data_save);
					}else{
						unset($data_save['post_id']);
						$wpdb->update($table_pokemon, $data_save,array('post_id' => $post_id));
					}
					$this->save_pokemon_moves($data['moves'],$data['id']);

					$image_url = $data['sprites']['other']['official-artwork']['front_default']; 
					$post_thumbnail = get_the_post_thumbnail_url($post_id); 
					if(strlen($post_thumbnail)==0 && strlen($image_url)>0){
						$this->download_pokemon_image($post_id,$image_url);
						$wordpress_image_url = wp_get_attachment_url( get_post_thumbnail_id($post_id), 'thumbnail' ); error_log($wordpress_image_url);
						$wpdb->update($table_pokemon, array('image_url'=>$wordpress_image_url),array('post_id' => $post_id));
					}
				} //if data['id']
			} //if post_type
		}
	}

	/* Generate a random pokemon, if already exist return post_id */
	public function generate_random_pokemon(){
		global $wpdb,$post;

		$random_pokemon = rand(1,1000);

		$check_pokemon = $this->get_pokemon_info_by_id($random_pokemon);

		if($check_pokemon){
			return $check_pokemon->post_id;
		}else{
			$data = json_decode($this->get_pokemon($random_pokemon),true);


			$my_post = array(
			  'post_title'    => ucfirst($data['name']) ,
			  'post_content'  => '',
			  'post_status'   => 'publish',
			  'post_type' => 'pokemon',
			  'post_author'   => 1,
			);
			// Insert the post into the database
			$post_id = wp_insert_post( $my_post );

			if(isset($data['id'])){
				$primary_type = $data['types'][0]['type']['name'];
				if(isset($data['types'][1]['type']['name'])){$second_type=$data['types'][1]['type']['name'];}else{$second_type=null;}
				$data_save = array(
					'id' => $data['id'],
					'pokemon_name' => $data['name'],
					'pokemon_description' => '',
					'primary_type' => $primary_type,
					'second_type' => $second_type,
					'weight' => $data['weight'],
					'pokedex_order' => $data['order'],
					'post_id' => $post_id
				);
				$table_pokemon = $wpdb->prefix . 'pokemon';
				$wpdb->get_results("SELECT * FROM ".$table_pokemon." WHERE post_id = ".$post_id); 
				if($wpdb->num_rows == 0){
					$wpdb->insert($table_pokemon, $data_save);
				}else{
					unset($data_save['post_id']);
					$wpdb->update($table_pokemon, $data_save,array('post_id' => $post_id));
				}
				$this->save_pokemon_moves($data['moves'],$data['id']); //save pokemon moves into a new table and associate to the pokemon
				/* Download pokemon picture to Wordpress */
				$image_url = $data['sprites']['other']['official-artwork']['front_default']; 
				$post_thumbnail = get_the_post_thumbnail($post_id); 
				if(strlen($post_thumbnail)==0 && strlen($image_url)>0){
					$this->download_pokemon_image($post_id,$image_url);
					$wordpress_image_url = wp_get_attachment_url( get_post_thumbnail_id($post_id), 'thumbnail' ); 
					$wpdb->update($table_pokemon, array('image_url'=>$wordpress_image_url),array('post_id' => $post_id));
				}
				return $post_id;
			}
		} //$check_pokemon


	}

	/* Save new moves into database, first check if exist, save move information and associate pokemon with moves */
	public function save_pokemon_moves($moves,$pokemon_id){
		global $wpdb;
		$table_pokemon_moves = $wpdb->prefix . 'pokemon_moves';
		$table_pokemon_moves_rel = $wpdb->prefix . 'pokemon_moves_rel';

		$moves_names_string = '';
		$moves_names_array = array();

		$query_insert_pk_mv_rel = "INSERT IGNORE INTO $table_pokemon_moves_rel VALUES ";
		for($m=0;$m<count($moves);$m++){
			if($m>0){$moves_names_string .= ','; $query_insert_pk_mv_rel .= ',';}
			$moves_names_string .= '"'.$moves[$m]['move']['name'].'"';
			$query_insert_pk_mv_rel .= '('.$pokemon_id.',"'.$moves[$m]['move']['name'].'")'; 
			$moves_names_array[$moves[$m]['move']['name']] = false;
		}


		$query_moves = "SELECT move_name FROM $table_pokemon_moves WHERE move_name IN('.$moves_names_string.')";
		$results = $wpdb->get_results($query_moves, ARRAY_A);
		if(is_array($results) && count($results)>0){
			foreach ($results as $move){
				$moves_names_array[$move['move_name']] = true;
			}
		}

		/* INSERT new moves into db */
		$api_calls = 0;
		$query_insert = "INSERT IGNORE INTO $table_pokemon_moves VALUES";
		
		foreach($moves_names_array as $move=>$status){
			if($status==false){
				$move_info = json_decode($this->get_pokemon_move($move),true);
				if(isset($move_info['name']) && $move_info>0){
					$move_description = "";
					for($i=0; $i<count($move_info['flavor_text_entries']);$i++){
						//look for last english description
						if($move_info['flavor_text_entries'][$i]['language']['name']=='en'){
							$move_description = $move_info['flavor_text_entries'][$i]['flavor_text'];
						}
					}
					if($api_calls>0){$query_insert .= ',';}
					$query_insert .= '("'.$move_info['name'].'","'.$move_info['type']['name'].'","'.$move_description.'")';
				}
				$api_calls++;
			}
		}
		if($api_calls>0){$results = $wpdb->get_results($query_insert);}

		$wpdb->get_results($query_insert_pk_mv_rel); //insert relation between pkm and related moves

	}

	public function get_pokemon_list(){ 
		global $wpdb,$post;
		$table_pokemon = $wpdb->prefix . 'pokemon';
		$results = $wpdb->get_results("SELECT pokedex_order as pokedex,pokemon_name FROM $table_pokemon ORDER BY pokedex_order ASC");

		if($wpdb->num_rows > 0){
			return $results;
		}
	}

	public function get_pokemon_by_type($data){ 
		global $wpdb,$post;
		$primary_type = $data['primary_type'];
		$second_type = $data['second_type'];

		$table_pokemon = $wpdb->prefix . 'pokemon';
		$results = $wpdb->get_results("SELECT pokemon_name, image_url FROM $table_pokemon WHERE primary_type = '$primary_type' AND second_type = '$second_type' ORDER BY RAND() LIMIT 5");

		if($wpdb->num_rows > 0){
			return $results;
		}
	}

	public function get_pokemon_info($pokemon_name){ 
		global $wpdb,$post;
		$table_pokemon = $wpdb->prefix . 'pokemon';
		$results = $wpdb->get_results("SELECT * FROM $table_pokemon WHERE pokemon_name = '$pokemon_name'");

		if($wpdb->num_rows > 0){
			return $results[0];
		}
	}

	public function get_pokemon_info_by_id($id){ 
		global $wpdb,$post;
		$table_pokemon = $wpdb->prefix . 'pokemon';
		$results = $wpdb->get_results("SELECT * FROM $table_pokemon WHERE id = $id");

		if($wpdb->num_rows > 0){
			return $results[0];
		}
	}

	public function get_pokemon_moveset($pokemon_name){ 
		global $wpdb,$post;
		$table_pokemon = $wpdb->prefix . 'pokemon';
		$table_pokemon_moves = $wpdb->prefix . 'pokemon_moves';
		$table_pokemon_moves_rel = $wpdb->prefix . 'pokemon_moves_rel';
		$results = $wpdb->get_results("SELECT m.* FROM $table_pokemon p INNER JOIN $table_pokemon_moves_rel pm ON p.id = pm.pokemon_id INNER JOIN $table_pokemon_moves m ON pm.move_name = m.move_name WHERE p.pokemon_name = '$pokemon_name'");

		return $results;
	}

	public function delete_pokemon($post_id){
		global $wpdb,$post;
		//delete the pokemon row with the associated post
		if (isset($post->post_type) && $post->post_type == 'pokemon'){ 
			$table_pokemon = $wpdb->prefix . 'pokemon'; error_log('Borrar '.$post_id);
			$wpdb->delete($table_pokemon,array('post_id'=>$post_id));
		}
	}

	/* rest calls */
	public function rest_pk_data($data){
		$pokemon_info = new pokemon_info();

		$info = $pokemon_info->get_pokemon_info($data['pokemon_name']);
		return $info;
	}

	public function rest_pk_data_id($data){
		$pokemon_info = new pokemon_info();

		$info = $pokemon_info->get_pokemon_info($data['pokemon_name']);
		return $info->id;
	}



}

$pokemon_info = new pokemon_info();

?>