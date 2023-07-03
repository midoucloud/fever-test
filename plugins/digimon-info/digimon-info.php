<?php
/*
Plugin Name: Digimon Information
Description: Generate a custom post type for Digimon and retrieve Digimon information from DApi
Version: 1.0
Author: Pedro Blanco
*/
// Plugin Folder Path
if (! defined ('DIGIINFO_PLUGIN_PATH'))
define ('DIGIINFO_PLUGIN_PATH', plugin_dir_path ( __FILE__ ) );

// Plugin Root File
if (! defined ('DIGIINFO_PLUGIN_FILE'))
	define ('DIGIINFO_PLUGIN_FILE', plugin_basename ( __FILE__ ) );

class digimon_info{

	/** plugin version number */
	const VERSION = '1.00';

	public function __construct() {
		//ejecuta instalación de tabla si no existe al activar plugin
		register_activation_hook ( DIGIINFO_PLUGIN_FILE, array($this,'install_updgrade' ) );
		add_action( 'plugins_loaded', array($this,'install_updgrade' ) ); 
		add_action( 'init', array($this,'create_posttype_digimon'));
		add_action('edit_form_after_title', array($this,'digimon_post_infoxbox'));
		add_action('save_post', array($this,'save_digimon_information'),100);
		add_action( 'delete_post', array($this,'delete_digimon'),100);
		/* rest api calls init for json api */
		add_action( 'rest_api_init', function () {
			register_rest_route( 'digimon/v2', '/id/(?P<digimon_name>[a-zA-Z0-9-% ]+)/', array(
			    'methods' => 'GET',
			    'callback' => array($this,'rest_digi_data_id')
		  ));
		});
		add_action( 'rest_api_init', function () {
			register_rest_route( 'digimon/v2', '/digimon_list/(?P<digimon_name>[a-zA-Z0-9-% ]+)/', array(
			    'methods' => 'GET',
			    'callback' => array($this,'rest_digi_data')
		  ));
		});
		add_action( 'rest_api_init', function () {
			register_rest_route( 'digimon/v2', '/digimon_list/', array(
			    'methods' => 'GET',
			    'callback' => array($this,'get_digimon_list')
		  ));
		});

	}

	/* Install tables needed when plugin is activated */
	public function install_updgrade() {
		global $wpdb;
		$installed_ver = get_option( "digimon_info_version" );

		if ( $installed_ver != self::VERSION ) {
			// install default settings, terms, etc
			if (!function_exists('dbDelta')) {
				include_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			}

			$table_digimon = $wpdb->prefix . 'digimon';
			$query1 = "CREATE TABLE IF NOT EXISTS $table_digimon (
				  `id` int(11) NOT NULL,
				  `digimon_name` varchar(100) DEFAULT NULL,
				  `digimon_description` text DEFAULT NULL,
				  `digimon_type` varchar(30) DEFAULT NULL,
				  `post_id` bigint(20) unsigned NOT NULL,
				  `image_url` varchar(255) DEFAULT NULL,
				  KEY `digimon_ibfk_1` (`post_id`),
				  KEY `id` (`id`),
				  CONSTRAINT `pk_digimon_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `pk_posts` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
			dbDelta( $query1 );

			$table_digimon_skills = $wpdb->prefix . 'digimon_skills';
			$query2 = "CREATE TABLE IF NOT EXISTS $table_digimon_skills (
					  `skill_name` varchar(50) NOT NULL,
					  `skill_description` text DEFAULT NULL,
					  PRIMARY KEY (`skill_name`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
			dbDelta( $query2 );

			$table_digimon_skills_rel = $wpdb->prefix . 'digimon_skills_rel';
			$query3 = "CREATE TABLE IF NOT EXISTS $table_digimon_skills_rel (
				  `digimon_id` int(11) DEFAULT NULL,
				  `skill_name` varchar(50) DEFAULT NULL,
				  UNIQUE KEY `digimon_id` (`digimon_id`,`skill_name`),
				  KEY `pk_digimon_skills_rel_ibfk_2` (`skill_name`),
				  CONSTRAINT `pk_digimon_skills_rel_ibfk_1` FOREIGN KEY (`digimon_id`) REFERENCES `$table_digimon` (`id`) ON DELETE CASCADE,
				  CONSTRAINT `pk_digimon_skills_rel_ibfk_2` FOREIGN KEY (`skill_name`) REFERENCES `$table_digimon_skills` (`skill_name`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
			dbDelta( $query3 );

			update_option( "digimon_info_version", self::VERSION);
		}
	}

	/* Add digimon post type to wordpress */
	public function create_posttype_digimon() {
	    $labels = array(
	        'name'                => _x( 'digimon', 'Post Type General Name', 'understrap' ),
	        'singular_name'       => _x( 'digimon', 'Post Type Singular Name', 'understrap' ),
	        'menu_name'           => __( 'digimon', 'understrap' ),
	        'parent_item_colon'   => __( 'Parent digimon', 'understrap' ),
	        'all_items'           => __( 'All digimon', 'understrap' ),
	        'view_item'           => __( 'View digimon', 'understrap' ),
	        'add_new_item'        => __( 'Add New digimon', 'understrap' ),
	        'add_new'             => __( 'Add New', 'understrap' ),
	        'edit_item'           => __( 'Edit digimon', 'understrap' ),
	        'update_item'         => __( 'Update digimon', 'understrap' ),
	        'search_items'        => __( 'Search digimon', 'understrap' ),
	        'not_found'           => __( 'Not Found', 'understrap' ),
	        'not_found_in_trash'  => __( 'Not found in Trash', 'understrap' ),
	    );
	      
	// Set other options for Custom Post Type
	      
	    $args = array(
	        'label'               => __( 'digimon', 'understrap' ),
	        'description'         => __( 'digimon information', 'understrap' ),
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
	    register_post_type( 'digimon', $args );
	}

	/* Short information for user when creating a new digimon */
	public function digimon_post_infoxbox(){
	  global $post;

	  // confirm if the post_type is 'digimon'
	  if ($post->post_type!== 'digimon')
	    return;

	   // here goes your message
	   echo '<div>Simply enter the name of the digimon and the text you wish to enter and the system will complete the rest of the data.</div>';
	}

	/* perform api call to pokeApi */
	public function call_dapi($method){
		$url = 'https://digi-api.com/api/v1/'.$method;

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

    /* get digimon data from pokeApi */
    public function get_digimon($digimon_name){
    	$method = 'digimon/'.$digimon_name;
        return $this->call_dapi($method);
    }

    /* get digimon move data from pokeApi */
    public function get_digimon_skill($skill){
    	$method = 'skill/'.$skill; 
        return $this->call_dapi($method);
    }

    /* Download digimon photo and attach to the post */
    public function download_digimon_image($post_id,$image_url){
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

    /* save digimon and adds data from the pokeApi */
	public function save_digimon_information(){
		global $wpdb,$post;
		
		if(isset($_POST) && isset($_POST['post_title'])){
			if (isset($post) && $post->post_type == 'digimon'){ 
				$post_title = $_POST['post_title']; 
				$post_id = get_the_ID(); 
				$post_content = get_the_content();

				$digimon_name = strtolower($post_title); 
				$data = json_decode($this->get_digimon($digimon_name),true);
				if(isset($data['id'])){
					$digimon_type = $data['types'][0]['type'];
					$data_save = array(
						'id' => $data['id'],
						'digimon_name' => $data['name'],
						'digimon_type' => $digimon_type,
						'digimon_description' => $post_content,
						'post_id' => $post_id
					);
					$table_digimon = $wpdb->prefix . 'digimon';
					$wpdb->get_results("SELECT * FROM ".$table_digimon." WHERE post_id = ".$post_id); 
					if($wpdb->num_rows == 0){
						$wpdb->insert($table_digimon, $data_save);
					}else{
						unset($data_save['post_id']);
						$wpdb->update($table_digimon, $data_save,array('post_id' => $post_id));
					}
					$this->save_digimon_skills($data['skills'],$data['id']);

					$image_url = $data['images'][0]['href']; 
					$post_thumbnail = get_the_post_thumbnail_url($post_id); 
					if(strlen($post_thumbnail)==0 && strlen($image_url)>0){
						$this->download_digimon_image($post_id,$image_url);
						$wordpress_image_url = wp_get_attachment_url( get_post_thumbnail_id($post_id), 'thumbnail' ); error_log($wordpress_image_url);
						$wpdb->update($table_digimon, array('image_url'=>$wordpress_image_url),array('post_id' => $post_id));
					}
				} //if data['id']
			} //if post_type
		}
	}

	/* Generate a random digimon, if already exist return post_id */
	public function generate_random_digimon(){
		global $wpdb,$post;

		$random_digimon = rand(1,1000);

		$check_digimon = $this->get_digimon_info_by_id($random_digimon);

		if($check_digimon){
			return $check_digimon->post_id;
		}else{
			$data = json_decode($this->get_digimon($random_digimon),true);


			$my_post = array(
			  'post_title'    => ucfirst($data['name']) ,
			  'post_content'  => '',
			  'post_status'   => 'publish',
			  'post_type' => 'digimon',
			  'post_author'   => 1,
			);
			// Insert the post into the database
			$post_id = wp_insert_post( $my_post );

			if(isset($data['id'])){
				$digimon_type = $data['types'][0]['type'];
				$data_save = array(
					'id' => $data['id'],
					'digimon_name' => $data['name'],
					'digimon_type' => $digimon_type,
					'digimon_description' => $post_content,
					'post_id' => $post_id
				);
				$table_digimon = $wpdb->prefix . 'digimon';
				$wpdb->get_results("SELECT * FROM ".$table_digimon." WHERE post_id = ".$post_id); 
				if($wpdb->num_rows == 0){
					$wpdb->insert($table_digimon, $data_save);
				}else{
					unset($data_save['post_id']);
					$wpdb->update($table_digimon, $data_save,array('post_id' => $post_id));
				}
				$this->save_digimon_skills($data['moves'],$data['id']); //save digimon moves into a new table and associate to the digimon
				/* Download digimon picture to Wordpress */
				$image_url = $data['images'][0]['href']; 
				$post_thumbnail = get_the_post_thumbnail($post_id); 
				if(strlen($post_thumbnail)==0 && strlen($image_url)>0){
					$this->download_digimon_image($post_id,$image_url);
					$wordpress_image_url = wp_get_attachment_url( get_post_thumbnail_id($post_id), 'thumbnail' ); 
					$wpdb->update($table_digimon, array('image_url'=>$wordpress_image_url),array('post_id' => $post_id));
				}
				return $post_id;
			}
		} //$check_digimon


	}

	/* Save new moves into database, first check if exist, save move information and associate digimon with moves */
	public function save_digimon_skills($skills,$digimon_id){
		global $wpdb;
		$table_digimon_skills = $wpdb->prefix . 'digimon_skills';
		$table_digimon_skills_rel = $wpdb->prefix . 'digimon_skills_rel';

		/* INSERT new moves into db */
		$query_insert = "INSERT IGNORE INTO $table_digimon_skills VALUES";
		$query_insert_pk_mv_rel = "INSERT IGNORE INTO $table_digimon_skills_rel VALUES ";

		$wpdb->get_results($query_insert);

		for($m=0;$m<count($skills);$m++){
			if($m>0){
				$query_insert .= ','; 
				$query_insert_pk_mv_rel .= ',';
			}
			$query_insert .= '("'.$skills[$m]['skill'].'","'.$skills[$m]['description'].'")'; 
			$query_insert_pk_mv_rel .= '('.$digimon_id.',"'.$skills[$m]['skill'].'")'; 
		}

		$wpdb->get_results($query_insert);
		error_log($query_insert);

		$wpdb->get_results($query_insert_pk_mv_rel); //insert relation between pkm and related moves
		error_log($query_insert_pk_mv_rel);

	}

	public function get_digimon_list(){ 
		global $wpdb,$post;
		$table_digimon = $wpdb->prefix . 'digimon';
		$results = $wpdb->get_results("SELECT id, digimon_name FROM $table_digimon ORDER BY id ASC");

		if($wpdb->num_rows > 0){
			return $results;
		}
	}

	public function get_digimon_by_type($data){ 
		global $wpdb,$post;
		$digimon_type = $data['digimon_type'];

		$table_digimon = $wpdb->prefix . 'digimon';
		$results = $wpdb->get_results("SELECT digimon_name, image_url FROM $table_digimon WHERE digimon_type = '$digimon_type' ORDER BY RAND() LIMIT 5");

		if($wpdb->num_rows > 0){
			return $results;
		}
	}

	public function get_digimon_info($digimon_name){ 
		global $wpdb,$post;
		$table_digimon = $wpdb->prefix . 'digimon';
		$results = $wpdb->get_results("SELECT * FROM $table_digimon WHERE digimon_name = '$digimon_name'");

		if($wpdb->num_rows > 0){
			return $results[0];
		}
	}

	public function get_digimon_info_by_id($id){ 
		global $wpdb,$post;
		$table_digimon = $wpdb->prefix . 'digimon';
		$results = $wpdb->get_results("SELECT * FROM $table_digimon WHERE id = $id");

		if($wpdb->num_rows > 0){
			return $results[0];
		}
	}

	public function get_digimon_skillset($digimon_name){ 
		global $wpdb,$post;
		$table_digimon = $wpdb->prefix . 'digimon';
		$table_digimon_skills = $wpdb->prefix . 'digimon_skills';
		$table_digimon_skills_rel = $wpdb->prefix . 'digimon_skills_rel';
		$results = $wpdb->get_results("SELECT m.* FROM $table_digimon p INNER JOIN $table_digimon_skills_rel pm ON p.id = pm.digimon_id INNER JOIN $table_digimon_skills m ON pm.skill_name = m.skill_name WHERE p.digimon_name = '$digimon_name'");

		return $results;
	}

	public function delete_digimon($post_id){
		global $wpdb,$post;
		//delete the digimon row with the associated post
		if (isset($post->post_type) && $post->post_type == 'digimon'){ 
			$table_digimon = $wpdb->prefix . 'digimon'; error_log('Borrar '.$post_id);
			$wpdb->delete($table_digimon,array('post_id'=>$post_id));
		}
	}

	/* rest calls */
	public function rest_digi_data($data){
		$digimon_info = new digimon_info();

		$info = $digimon_info->get_digimon_info($data['digimon_name']);
		return $info;
	}

	public function rest_digi_data_id($data){
		$digimon_info = new digimon_info();

		$info = $digimon_info->get_digimon_info($data['digimon_name']);
		return $info->id;
	}



}

$digimon_info = new digimon_info();

?>