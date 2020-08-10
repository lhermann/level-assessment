<?php
/*
 * Redirect User to the Level Assessment Page
 * (http://wordpress.stackexchange.com/questions/64576/prevent-users-from-going-to-wordpress-profile-after-login)
 */
function la_login_redirect( $redirect_to, $request, $user ) {
	global $user;
	if ( isset( $user->roles ) && is_array( $user->roles ) ) {
		//check for subscriber
		if ( in_array( 'subscriber', $user->roles ) ) {
			// redirect them to level assessment admin page
			return admin_url( 'admin.php?page=level-assessment' );
		}
	}
	return $redirect_to;
}
add_filter("login_redirect", "la_login_redirect", 10, 3);

// remove Profile and Dashboard for Subscriber
add_action( 'admin_init', 'la_remove_menu_pages' );
function la_remove_menu_pages() {
	if( !defined( 'DOING_AJAX' ) ) {
		global $user_ID;
		if ( !current_user_can( 'edit_posts' ) ) {
			remove_menu_page( 'index.php' );
			remove_menu_page( 'profile.php' );
		}
 	}
}

// source: http://codex.wordpress.org/Creating_Options_Pages
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class LevelAssessmentTestPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_menu_page(
        	__( 'Level Assessment List', 'level-assessment' ),
        	__( 'Level Assessment', 'level-assessment' ),
        	'read',
        	'level-assessment',
        	array( $this, 'create_admin_page' ),
        	'dashicons-media-spreadsheet'
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
		//Create an instance of our package class...
		$la_list_table = new LA_List_Table();
		//Fetch, prepare, sort, and filter our data...
		$la_list_table->prepare_items();
		?>
			<div class="wrap">
				<h2><?php _e( 'Level Assessment List', 'level-assessment' ); ?></h2>
				<p>
					<a id="print-csv-button" type="button" name="print-csv" class="button" href="<?php echo plugins_url( 'download-as-csv.php', __FILE__ ).'?type=list'; ?>"><span class="dashicons dashicons-download"></span> <?php _e( 'Download as Spreadsheet', 'level-assessment' ); ?></a>
					<a id="print-page-button" type="button" name="print-page" class="button" href="javascript:window.print()"><span class="dashicons dashicons-admin-page"></span> <?php _e( 'Print', 'level-assessment' ); ?></a>
				</p>
				<?php $la_list_table->display() ?>
			</div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init() {
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {
    }
}

if( is_admin() )
    $la_page_test = new LevelAssessmentTestPage();



class LA_List_Table extends WP_List_Table {

   /**
	* Constructor, we override the parent to pass our own arguments
	* We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
	*/
	function __construct() {
		parent::__construct(
			array(
				'singular' => 'wp_list_la_result', //Singular label
				'plural'   => 'wp_list_la_results', //plural label, also this well be one of the table css class
				'ajax'     => false //We won't support Ajax for this table
			)
		);
	}

	/**
	 * Define the columns that are going to be used in the table
	 * @return array $columns, the array of columns to use with the table
	 */
    function get_columns(){
        $columns = array(
            'name'		=> __( 'Name', 'level-assessment' ),
            'email'		=> __( 'Email', 'level-assessment' ),
            'language'	=> __( 'Language', 'level-assessment' ),
            'level'		=> __( 'Level', 'level-assessment' ),
            'date'		=> __( 'Date', 'level-assessment' )
        );
        return $columns;
    }

	/**
	 * Decide which columns to activate the sorting functionality on
	 * @return array $sortable, the array of columns that can be sorted by the user
	 */
	public function get_sortable_columns() {
		return $sortable = array(
			'name'		=> array( 'name', true ),
			'email'		=> array( 'email', false ),
			'language'	=> array( 'language', false ),
			'level'		=> array( 'level', false ),
			'date'		=> array( 'time', false )
		);
	}

	function extra_tablenav( $which ) {

		// create array of semesters // Filtrar por semestre
		$diff = time() - strtotime('2015-06-01 00:00:00');
		$count = (int)ceil( $diff / strtotime('+6 month', 0) );
		$args = wp_parse_args( $_GET );

		$selected = isset( $args['semester'] ) ? $args['semester'] : false;
		$previous = isset( $args['semester'] ) ? $args['semester'] : date( 'Y' );
		?>
		<div class="alignleft actions">
			<form id="posts-filter" method="get" action="<?= $_SERVER['REQUEST_URI'] ?>">
					<?php foreach ( $args as $name => $value): ?>
						<input type="hidden" name="<?= $name ?>" value="<?= $value ?>">
					<?php endforeach; ?>
					<input type="hidden" name="privious_semester" value="<?= $previous ?>">
					<label for="filter-by-semester" class="screen-reader-text"><?= __( 'Filter by Semester', 'level-assessment' ) ?></label>
					<select name="semester" id="filter-by-semester">
						<option <?= $selected === '0' ? 'selected="selected"' : '' ?> value="0"><?= __( 'All Semesters', 'level-assessment' ) ?></option>
						<?php for ( $i=0; $i < $count; $i++):
								$year = date ( 'Y' , strtotime ( '-'.(6*$i).' month' ) );
								$term = $i%2 ? '2' : '1';

								printf( '<option %1$s value="%2$s-%3$s">%2$s/%3$s</option>',
									( $selected === $year.'-'.$term ? 'selected="selected"' : ( $i==0 && $selected === false ? 'selected="selected"' : '' ) ),
									$year,
									$term
								);
						endfor; ?>
					</select>
					<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filtrar">
			</form>
		</div>
		<?php
	}



	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 */
	function prepare_items() {
	   global $wpdb, $_wp_column_headers;
	   $screen = get_current_screen();

		/* -- Preparing your query -- */
	   		$table_name = $wpdb->prefix.'level_assessment';
			$query = "SELECT * FROM $table_name WHERE type = 'test'";

		/* -- Timeframe parameters -- */
			// get values
			if( isset( $_GET['semester'] ) ) {
				$semester = $_GET['semester'];
			} else {
				$semester = date( 'Y' ) . '-' . ( date( 'n' ) < 6 ? 1 : 2 );
			}

			// build query
			if( $semester !== '0' ) {
				$semester = explode( '-', $semester );
				$semester_start = $semester[0] . "-" . ( $semester[1] == 1 ? '01' : '06' ) . "-01 00:00:00";
				$semester_end = $semester[0] . "-" . ( $semester[1] == 1 ? '05' : '12' ) . "-31 23:59:59";
				$query.=" AND time BETWEEN '{$semester_start}' AND '{$semester_end}' ";
				// var_dump( $_POST, $semester, $semester_start, $semester_end );
			}

		/* -- Ordering parameters -- */
			//Parameters that are going to be used to order the result
			if( strlen ( !empty($_GET["orderby"]) ) >= 12 ) $_GET["orderby"]=false;
			if( strlen ( !empty($_GET["order"]) ) >= 12 ) $_GET["order"]=false;
			$orderby = ( !empty($_GET["orderby"])  ? $_GET["orderby"] : 'ASC' );
			$order = ( !empty($_GET["order"]) ? $_GET["order"] : '' );
			if( !empty($orderby) & !empty($order) ){
				$query .= ' ORDER BY '.$orderby.' '.$order;
			} else {
				$query .= ' ORDER BY name ASC';
			}

		/* -- Pagination parameters -- */
			//Number of elements in your table?
			$totalitems = $wpdb->query($query); //return the total number of affected rows
			//How many to display per page?
			$perpage = 50;
			//Which page is this?
			$paged = !empty($_GET["paged"]) ? $_GET["paged"] : '';
			//Page Number
			if(empty($paged) || !is_numeric($paged) || $paged<=0 || $_GET["semester"] != $_GET["privious_semester"] ) $paged=1;
			//How many pages do we have in total?
			$totalpages = ceil($totalitems/$perpage);
			//adjust the query to take pagination into account
			if(!empty($paged) && !empty($perpage)){
				$offset=($paged-1)*$perpage;
				$query.=' LIMIT '.(int)$offset.','.(int)$perpage;
			}

		/* -- Register the pagination -- */
			$this->set_pagination_args( array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $perpage,
			) );
			//The pagination links are automatically built according to those parameters

		/* -- Register the Columns -- */
			$columns	= $this->get_columns();
			$hidden		= array();
			$sortable	= $this->get_sortable_columns();
			$this->_column_headers = array($columns, $hidden, $sortable);
			//$columns = $this->get_columns();
			//$_wp_column_headers[$screen->id]=$columns;

		/* -- Fetch the items -- */
			$this->items = $wpdb->get_results($query);
	}

	/**
	 * Display the rows of records in the table
	 * @return string, echo the markup of the rows
	 */
	function display_rows() {

		//Get the records registered in the prepare_items method
		$records = $this->items;

		//Get the columns registered in the get_columns and get_sortable_columns methods
		list( $columns, $hidden ) = $this->get_column_info();

		//Loop for each record
		if( $records ) {
			foreach( $records as $i => $rec ) {

				//Open the line
				echo '<tr id="record_'.$rec->ID.'">';
				foreach ( $columns as $column_name => $column_display_name ) {

					//Style attributes for each col
					$class = sprintf( 'class="$column_name column-%1$s %2$s"',
						'column-'.$column_name,
						( $i & 1 ? '' : 'alternate' )
					);
					$style = "";
					if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
					$attributes = $class.$style;

					// Format Levels
					foreach( get_available_levels() as $level ) {
						if( $level->slug === $rec->level ) $rec->level = $level->name;
					}
					foreach( la_get_available_languages() as $language ) {
						if( $language->slug === $rec->language ) $rec->language = $language->name;
					}

					$time = date('j. M Y', strtotime($rec->time));

					//Display the cell
					switch ( $column_name ) {
						case 'name':  echo '<td '.$attributes.'><strong>'.stripslashes($rec->name).'</strong></td>';   break;
						case 'email': echo '<td '.$attributes.'><a href="mailto:'.stripslashes($rec->email).'">'.stripslashes($rec->email).'</a></td>'; break;
						case 'language': echo '<td '.$attributes.'>'.stripslashes($rec->language).'</td>'; break;
						case 'level': echo '<td '.$attributes.'>'.$rec->level.'</td>'; break;
						case 'date': echo '<td '.$attributes.'>'.$time.'</td>'; break;
					}
				}

				//Close the line
				echo'</tr>';
			}
		}
	}
}





class LevelAssessmentTimePage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        add_submenu_page(
        	'level-assessment',
        	__( 'List of requested times', 'level-assessment' ),
        	__( 'Requested Times', 'level-assessment' ),
        	'edit_posts',
        	'requested-times-list',
        	array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
		//Create an instance of our package class...
		$la_time_table = new LA_Time_Table();
		//Fetch, prepare, sort, and filter our data...
		$la_time_table->prepare_items();
        ?>
        <div class="wrap">
        	<h2><?php _e( 'List of requested times', 'level-assessment' ); ?></h2>
        	<a id="print-csv-button" type="button" name="print-csv" class="button" href="<?php echo plugins_url( 'download-as-csv.php', __FILE__ ).'?type=time'; ?>"><span class="dashicons dashicons-download"></span> <?php _e( 'Download as Spreadsheet', 'level-assessment' ); ?></a>
        	<a id="print-page-button" type="button" name="print-page" class="button" href="javascript:window.print()"><span class="dashicons dashicons-admin-page"></span> <?php _e( 'Print', 'level-assessment' ); ?></a>
        	<?php $la_time_table->display() ?>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init() {
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {
    }
}

if( is_admin() )
    $la_page_time = new LevelAssessmentTimePage();



class LA_Time_Table extends WP_List_Table {

   /**
	* Constructor, we override the parent to pass our own arguments
	* We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
	*/
	function __construct() {
	   parent::__construct( array(
	  'singular'=> 'wp_list_la_time', //Singular label
	  'plural'	=> 'wp_list_la_times', //plural label, also this well be one of the table css class
	  'ajax'	=> false //We won't support Ajax for this table
	  ) );
	}

	/**
	 * Define the columns that are going to be used in the table
	 * @return array $columns, the array of columns to use with the table
	 */
    function get_columns(){
        $columns = array(
            'name'		=> __( 'Name', 'level-assessment' ),
            'email'		=> __( 'Email', 'level-assessment' ),
            'language'	=> __( 'Language', 'level-assessment' ),
            'level'		=> __( 'Level', 'level-assessment' ),
            'request'	=> __( 'Requested Time', 'level-assessment' ),
            'date'		=> __( 'Date', 'level-assessment' )
        );
        return $columns;
    }

	/**
	 * Decide which columns to activate the sorting functionality on
	 * @return array $sortable, the array of columns that can be sorted by the user
	 */
	public function get_sortable_columns() {
		return $sortable = array(
			'name'		=> array( 'name', false ),
			'email'		=> array( 'email', false ),
			'language'	=> array( 'language', false ),
			'level'		=> array( 'level', false ),
			'request'	=> array( 'value', false ),
			'date'		=> array( 'time', false )
		);
	}



	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 */
	function prepare_items() {
	   global $wpdb, $_wp_column_headers;
	   $screen = get_current_screen();

	   /* -- Preparing your query -- */
	   		$table_name = $wpdb->prefix.'level_assessment';
			$query = "SELECT * FROM $table_name WHERE type = 'time'";

	   /* -- Ordering parameters -- */
			//Parameters that are going to be used to order the result
			if( strlen ( !empty($_GET["orderby"]) ) >= 12 ) $_GET["orderby"]=false;
			if( strlen ( !empty($_GET["order"]) ) >= 12 ) $_GET["order"]=false;
			$orderby = ( !empty($_GET["orderby"])  ? $_GET["orderby"] : 'ASC' );
			$order = ( !empty($_GET["order"]) ? $_GET["order"] : '' );
			if( !empty($orderby) & !empty($order) ){
				$query .= ' ORDER BY '.$orderby.' '.$order;
			} else {
				$query .= ' ORDER BY ID DESC';
			}

	   /* -- Pagination parameters -- */
			//Number of elements in your table?
			$totalitems = $wpdb->query($query); //return the total number of affected rows
			//How many to display per page?
			$perpage = 50;
			//Which page is this?
			$paged = !empty($_GET["paged"]) ? $_GET["paged"] : '';
			//Page Number
			if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
			//How many pages do we have in total?
			$totalpages = ceil($totalitems/$perpage);
			//adjust the query to take pagination into account
			if(!empty($paged) && !empty($perpage)){
				$offset=($paged-1)*$perpage;
				$query.=' LIMIT '.(int)$offset.','.(int)$perpage;
			}

	   /* -- Register the pagination -- */
			$this->set_pagination_args( array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $perpage,
			) );
			//The pagination links are automatically built according to those parameters

	   /* -- Register the Columns -- */
			$columns	= $this->get_columns();
			$hidden		= array();
			$sortable	= $this->get_sortable_columns();
			$this->_column_headers = array($columns, $hidden, $sortable);
			//$columns = $this->get_columns();
			//$_wp_column_headers[$screen->id]=$columns;

	   /* -- Fetch the items -- */
			$this->items = $wpdb->get_results($query);
	}

	/**
	 * Display the rows of records in the table
	 * @return string, echo the markup of the rows
	 */
	function display_rows() {

		//Get the records registered in the prepare_items method
		$records = $this->items;

		//Get the columns registered in the get_columns and get_sortable_columns methods
		list( $columns, $hidden ) = $this->get_column_info();

		//Loop for each record
		if( $records ) {
			foreach( $records as $i => $rec ) {

				//Open the line
				echo '<tr id="record_'.$rec->ID.'">';
				foreach ( $columns as $column_name => $column_display_name ) {

					//Style attributes for each col
					$class = sprintf( 'class="$column_name column-%1$s %2$s"',
						'column-'.$column_name,
						( $i & 1 ? '' : 'alternate' )
					);
					$style = "";
					if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
					$attributes = $class.$style;

					// Format Levels
					foreach( get_available_levels() as $level ) {
						if( $level->slug === $rec->level ) $rec->level = $level->name;
					}
					foreach( la_get_available_languages() as $language ) {
						if( $language->slug === $rec->language ) $rec->language = $language->name;
					}

					$time = date('j. M Y', strtotime($rec->time));

					//Display the cell
					switch ( $column_name ) {
						case 'name':  echo '<td '.$attributes.'><strong>'.stripslashes($rec->name).'</strong></td>';   break;
						case 'email': echo '<td '.$attributes.'><a href="mailto:'.stripslashes($rec->email).'">'.stripslashes($rec->email).'</a></td>'; break;
						case 'language': echo '<td '.$attributes.'>'.stripslashes($rec->language).'</td>'; break;
						case 'level': echo '<td '.$attributes.'>'.$rec->level.'</td>'; break;
						case 'request': echo '<td '.$attributes.'>'.$rec->value.'</td>'; break;
						case 'date': echo '<td '.$attributes.'>'.$time.'</td>'; break;
					}
				}

				//Close the line
				echo'</tr>';
			}
		}
	}
}
