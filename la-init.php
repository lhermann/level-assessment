<?php
add_action('wp_enqueue_scripts', 'level_assessment_enqueue_scripts');
function level_assessment_enqueue_scripts() {
	// css
	if ( !wp_script_is( 'bootstrap' ) ) {
		wp_register_style( 'bootstrap', plugins_url( 'css/bootstrap.min.css', __FILE__ ), false );
		wp_enqueue_style( 'bootstrap' );
    }
	wp_register_style( 'level-assessment', plugins_url( 'css/la_style.css', __FILE__ ), array( 'bootstrap' ) );
	wp_enqueue_style( 'level-assessment' );
	// js
	if ( !wp_script_is( 'bootstrap' ) ) {
		wp_register_script( 'bootstrap', plugins_url( 'js/bootstrap.min.js', __FILE__ ), array( 'jquery' ), false, true );
		wp_enqueue_script( 'bootstrap' );
    }
	wp_register_script( 'bootstrap-form-validator', plugins_url( 'js/validator.js', __FILE__ ), array( 'jquery', 'bootstrap' ), false, true );
	wp_enqueue_script( 'bootstrap-form-validator' );
	wp_register_script( 'level-assessment-js', plugins_url( 'js/la_script.js', __FILE__ ), array( 'jquery', 'bootstrap' ), false, true );
	wp_enqueue_script( 'level-assessment-js' );
}

// Add ajaxurl
function la_footer_ajaxurl() {
    echo "<script type='text/javascript'> var ajaxurl='".admin_url('admin-ajax.php')."'; </script>";
}
add_action('wp_footer', 'la_footer_ajaxurl');

// Admin
function joelmedia_admin_scripts() {
	wp_enqueue_style( 'la_admin_css', plugins_url( 'css/la_admin.css', __FILE__ ), array( 'colors' ) );
};
add_action( 'admin_enqueue_scripts', 'joelmedia_admin_scripts' );

//ini_set('session.cookie_lifetime',84600);
//ini_set('session.gc_maxlifetime',84600);


/**
 * Register a level assessment questions post type.
 *
 * @link http://codex.wordpress.org/Function_Reference/register_post_type
 * @since 1.0
 */
add_action( 'init', 'register_level_assessment_questions' );
function register_level_assessment_questions() {
	setup_session();

	$labels = array(
		'name'               => _x( 'Level Assessment Questions', 'post type general name', 'level-assessment' ),
		'singular_name'      => _x( 'Level Assessment Question', 'post type singular name', 'level-assessment' ),
		'menu_name'          => _x( 'Level Assessment Questions', 'admin menu', 'level-assessment' ),
		'name_admin_bar'     => _x( 'Question', 'add new on admin bar', 'level-assessment' ),
		'add_new'            => _x( 'Add New', 'level assessment question', 'level-assessment' ),
		'add_new_item'       => __( 'Add New Question', 'level-assessment' ),
		'new_item'           => __( 'New Question', 'level-assessment' ),
		'edit_item'          => __( 'Edit Question', 'level-assessment' ),
		'view_item'          => __( 'View Question', 'level-assessment' ),
		'all_items'          => __( 'All Questions', 'level-assessment' ),
		'search_items'       => __( 'Search Questions', 'level-assessment' ),
		'parent_item_colon'  => __( 'Parent Questions:', 'level-assessment' ),
		'not_found'          => __( 'No questions found.', 'level-assessment' ),
		'not_found_in_trash' => __( 'No questions found in Trash.', 'level-assessment' )
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'exclude_from_search'=> true,
		'show_in_nav_menus'	 => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'menu_icon'			 => 'dashicons-editor-help',
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'question' ),
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title' ),
		'taxonomies' 		 => array( 'language', 'level' )
	);

	register_post_type( 'laq', $args );

	$labels = array(
		'name'               => _x( 'Level Information', 'post type general name', 'level-assessment' ),
		'singular_name'      => _x( 'Level Information', 'post type singular name', 'level-assessment' ),
		'menu_name'          => _x( 'Level Information', 'admin menu', 'level-assessment' ),
		'name_admin_bar'     => _x( 'Information', 'add new on admin bar', 'level-assessment' ),
		'add_new'            => _x( 'Add New', 'level assessment question', 'level-assessment' ),
		'add_new_item'       => __( 'Add New Information', 'level-assessment' ),
		'new_item'           => __( 'New Information', 'level-assessment' ),
		'edit_item'          => __( 'Edit Information', 'level-assessment' ),
		'view_item'          => __( 'View Information', 'level-assessment' ),
		'all_items'          => __( 'All Information', 'level-assessment' ),
		'search_items'       => __( 'Search Information', 'level-assessment' ),
		'parent_item_colon'  => __( 'Parent Information:', 'level-assessment' ),
		'not_found'          => __( 'No Information found.', 'level-assessment' ),
		'not_found_in_trash' => __( 'No Information found in Trash.', 'level-assessment' )
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'exclude_from_search'=> true,
		'show_in_nav_menus'	 => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'menu_icon'			 => 'dashicons-info',
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'info' ),
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor' ),
		'taxonomies' 		 => array( 'age', 'language', 'level' )
	);

	register_post_type( 'lainfo', $args );
}


// hook into the init action and call register_laq_taxonomies when it fires
add_action( 'init', 'register_laq_taxonomies', 0 );

// create two taxonomies, genres and writers for the post type 'laq'
function register_laq_taxonomies() {
	// Add new taxonomy 'age', NOT hierarchical (like tags)
	$labels = array(
		'name'                       => _x( 'Age Groups', 'taxonomy general name', 'level-assessment' ),
		'singular_name'              => _x( 'Age Group', 'taxonomy singular name', 'level-assessment' ),
		'search_items'               => __( 'Search Age Groups', 'level-assessment' ),
		'popular_items'              => __( 'Popular Age Groups', 'level-assessment' ),
		'all_items'                  => __( 'All Age Groups', 'level-assessment' ),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => __( 'Edit Age Group', 'level-assessment' ),
		'update_item'                => __( 'Update Age Group', 'level-assessment' ),
		'add_new_item'               => __( 'Add New Age Group', 'level-assessment' ),
		'new_item_name'              => __( 'New Age Group Name', 'level-assessment' ),
		'separate_items_with_commas' => __( 'Separate age groups with commas', 'level-assessment' ),
		'add_or_remove_items'        => __( 'Add or remove age groups', 'level-assessment' ),
		'choose_from_most_used'      => __( 'Choose from the most used age groups', 'level-assessment' ),
		'not_found'                  => __( 'No age groups found.', 'level-assessment' ),
		'menu_name'                  => __( 'Age Groups', 'level-assessment' )
	);
	$args = array(
		'hierarchical'          => false,
		'labels'                => $labels,
		'show_ui'               => true,
		'show_admin_column'     => true,
		//'update_count_callback' => '_update_post_term_count',
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'age' ),
	);
	register_taxonomy( 'age', 'lainfo', $args );

	// Add new taxonomy 'language', NOT hierarchical (like tags)
	$labels = array(
		'name'                       => _x( 'Languages', 'taxonomy general name', 'level-assessment' ),
		'singular_name'              => _x( 'Language', 'taxonomy singular name', 'level-assessment' ),
		'search_items'               => __( 'Search Languages', 'level-assessment' ),
		'popular_items'              => __( 'Popular Languages', 'level-assessment' ),
		'all_items'                  => __( 'All Languages', 'level-assessment' ),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => __( 'Edit Language', 'level-assessment' ),
		'update_item'                => __( 'Update Language', 'level-assessment' ),
		'add_new_item'               => __( 'Add New Language', 'level-assessment' ),
		'new_item_name'              => __( 'New Language Name', 'level-assessment' ),
		'separate_items_with_commas' => __( 'Separate languages with commas', 'level-assessment' ),
		'add_or_remove_items'        => __( 'Add or remove languages', 'level-assessment' ),
		'choose_from_most_used'      => __( 'Choose from the most used languages', 'level-assessment' ),
		'not_found'                  => __( 'No languages found.', 'level-assessment' ),
		'menu_name'                  => __( 'Languages', 'level-assessment' ),
	);
	$args = array(
		'hierarchical'          => false,
		'labels'                => $labels,
		'show_ui'               => true,
		'show_admin_column'     => true,
		//'update_count_callback' => '_update_post_term_count',
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'language' ),
	);
	register_taxonomy( 'language', 'laq', $args );
	register_taxonomy( 'language', 'lainfo', $args );

	// Add new taxonomy 'level', NOT hierarchical (like tags)
	$labels = array(
		'name'              		 => _x( 'Levels', 'taxonomy general name', 'level-assessment' ),
		'singular_name'     		 => _x( 'Level', 'taxonomy singular name', 'level-assessment' ),
		'search_items'               => __( 'Search Levels', 'level-assessment' ),
		'popular_items'              => __( 'Popular Levels', 'level-assessment' ),
		'all_items'                  => __( 'All Levels', 'level-assessment' ),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => __( 'Edit Level', 'level-assessment' ),
		'update_item'                => __( 'Update Level', 'level-assessment' ),
		'add_new_item'               => __( 'Add New Level', 'level-assessment' ),
		'new_item_name'              => __( 'New Level Name', 'level-assessment' ),
		'separate_items_with_commas' => __( 'Separate levels with commas', 'level-assessment' ),
		'add_or_remove_items'        => __( 'Add or remove levels', 'level-assessment' ),
		'choose_from_most_used'      => __( 'Choose from the most used levels', 'level-assessment' ),
		'not_found'                  => __( 'No levels found.', 'level-assessment' ),
		'menu_name'                  => __( 'Levels', 'level-assessment' ),
	);
	$args = array(
		'hierarchical'      => false,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'level' ),
	);
	register_taxonomy( 'level', 'laq' , $args );
	register_taxonomy( 'level', 'lainfo' , $args );
}


/*
 * Add Taxonomy Filter to Admin List for Taxonomies 'level' and 'language'
 *
 * Source: https://wordpress.org/support/topic/add-taxonomy-filter-to-admin-list-for-my-custom-post-type
 * @since 1.0
 */
function restrict_laq_by_level() {
	global $typenow;
	$post_type = 'laq'; // change HERE
	$taxonomy = 'level'; // change HERE
	if ($typenow == $post_type) {
		$selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
		$info_taxonomy = get_taxonomy($taxonomy);
		wp_dropdown_categories(array(
			'show_option_all' => __("Show All {$info_taxonomy->label}"),
			'taxonomy' => $taxonomy,
			'name' => $taxonomy,
			'orderby' => 'name',
			'selected' => $selected,
			'show_count' => true,
			'hide_empty' => true,
		));
	};
}
add_action('restrict_manage_posts', 'restrict_laq_by_level');

function restrict_laq_by_language() {
	global $typenow;
	$post_type = 'laq'; // change HERE
	$taxonomy = 'language'; // change HERE
	if ($typenow == $post_type) {
		$selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
		$info_taxonomy = get_taxonomy($taxonomy);
		wp_dropdown_categories(array(
			'show_option_all' => __("Show All {$info_taxonomy->label}"),
			'taxonomy' => $taxonomy,
			'name' => $taxonomy,
			'orderby' => 'name',
			'selected' => $selected,
			'show_count' => true,
			'hide_empty' => true,
		));
	};
}
add_action('restrict_manage_posts', 'restrict_laq_by_language');

function convert_id_to_term_in_query( $query ) {
	global $pagenow;
	$post_type = 'laq'; // change HERE
	$taxonomies = array( 'level', 'language' ); // change HERE
	$q_vars = &$query->query_vars;
	foreach ( $taxonomies as $taxonomy ) {
		if ($pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $post_type && isset($q_vars[$taxonomy]) && is_numeric($q_vars[$taxonomy]) && $q_vars[$taxonomy] != 0) {
			$term = get_term_by('id', $q_vars[$taxonomy], $taxonomy);
			$q_vars[$taxonomy] = $term->slug;
		}
	}
}
add_filter('parse_query', 'convert_id_to_term_in_query');


/*
 * Remove 'Levels' and 'Languages' standard meta box when editing a post of the post type 'laq'
 * @since 1.0
 */
if (is_admin()) :
	function laq_remove_meta_boxes() {
		remove_meta_box('tagsdiv-age', 'lainfo', 'side');
		remove_meta_box('tagsdiv-level', 'laq', 'side');
		remove_meta_box('tagsdiv-level', 'lainfo', 'side');
		remove_meta_box('tagsdiv-language', 'laq', 'side');
		remove_meta_box('tagsdiv-language', 'lainfo', 'side');
	}
	add_action( 'admin_menu', 'laq_remove_meta_boxes' );
endif;


/*
 * Create the Database when the Plugin is activated
 * @since 1.0
 */
register_activation_hook( __FILE__, 'la_db_install' );
function la_db_install ( $table_name ) {
	global $wpdb;
	$table_name = $wpdb->prefix.'level_assessment';
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $table_name (
		ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		type varchar(20) DEFAULT 'test' NOT NULL,
		name tinytext NOT NULL,
		email tinytext NOT NULL,
		language varchar(20) DEFAULT '' NOT NULL,
		level varchar(20) DEFAULT '' NOT NULL,
		value tinytext NOT NULL,
		UNIQUE KEY ID (ID)
	) $charset_collate;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	add_option( "la_db_version", "1.0" );
	return true;
}
