<?php
/**
 * The main plugin file
 *
 * @package WordPress
 * @subpackage Level Assessment Plugin
 */

/*
Plugin Name: Level Assessment Plugin
Description: Creates a Level Assessment for the signup process for IMI (Instituto Missionário de Idiomas)
Plugin URI:  #
Version:     1.0.0
Author:      Lukas Hermann
Author URI:  http://lhermann.de/

Copyright 2014 Lukas Hermann (email : luke.antix@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*****************************************************
 * Main Plugin Settings                              *
 * (At least as long as there is no option page yet) *
 *****************************************************/

/*
 * Include Configuration file
 * @since 1.1
 */
require_once( 'conf.php' );

/*
 * Includes
 */
require_once('inc/acf-fields.php');
require_once('inc/shortcode-level-assessment.php');
require_once('inc/shortcode-courses.php');


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


/*
 * Controll which ones are the open levels from the available ones
 * eg.: 2 -> open: basic-1, basic-2, closed: intermediate-1 usw...
 * @since 1.0
 */
function get_max_open_level( $language = 'ingles' ) {
	//$levels = get_available_levels (); OBSOLET
	// enter how many levels are available per language
	$open_levels = array(
		'ingles' => MAX_LEVEL_ENGLISH,
		'espanhol' => MAX_LEVEL_SPANISH
	);
	return $open_levels[$language];
}
// total number of question per level
function la_questions_total() {
	return QUESTIONS_TOTAL;
}
// minmum correct answers required
function la_questions_min() {
	return QUESTIONS_MIN;
}
// get proper language name by slug
function get_language_name( $language_slug ) {
	$index = array (
		'ingles' => 'Inglês',
	);
	return $index[$language_slug];
}

/**
 * Load plugin textdomain.
 * @since 1.0.0
 */
add_action( 'plugins_loaded', 'la_load_textdomain' );
function la_load_textdomain() {
	load_plugin_textdomain( 'level-assessment', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Include Admin Page
 * @since 1.0
 */
require_once( 'la-admin-page.php' );

/**
 * Include Init Actions and Hooks
 * @since 1.1
 */
require_once( 'la-init.php' );


/**
 * Set a variable as default only if it is empty
 * @source http://codereview.stackexchange.com/questions/13558/best-practice-in-php-for-if-the-variable-is-empty-set-a-default-value
 * Note: the ampersand (&) passes a reference to the original (the original variable is changed, therefore no 'return' is needed)
 * @since 1.0
 */
function set_default ( &$var, $default ) {
	if ( empty($var) ) $var = $default;
};

/*
 * Array with all available levels
 * They are the same for every language
 * See get_first_closed_level(); for the currently maximum open level
 * @since 1.0
 */
function get_available_tax ( $taxonomy = 'level', $key = NULL, $value = NULL ) {
	$levels = get_terms( $taxonomy, array(
		'orderby'    => 'id',
		'hide_empty' => 0
	) );
	// return the right thing
	if( isset($key) ) {
		if( isset($value) ) return $levels[$key]->$value;
		return $levels[$key];
	}
	return $levels;
}
function get_available_levels ( $key = NULL, $value = NULL ) {
	$levels = get_terms( 'level', array(
		'orderby'    => 'id',
		'hide_empty' => 0
	) );
	// return the right thing
	if( isset($key) ) {
		if( isset($value) ) return $levels[$key]->$value;
		return $levels[$key];
	}
	return $levels;
}
function get_level_index( $mode = NULL, $level_array = false ) {
	$levels = ( $level_array ? $level_array : get_available_levels() );
	$return = array();
	if( $mode == 'names' ) {
		foreach ( $levels as $key => $level ) {
			$return[$key] = $level->name;
		}
	} else {
		foreach ( $levels as $key => $level ) {
			$return[$key] = $level->slug;
		}
	}
	return $return;
}
function la_get_available_languages( $key = NULL, $value = NULL ) {
	$languages = get_terms( 'language', array(
		'orderby'    => 'id',
		'hide_empty' => 0
	) );
	// return the right thing
	if( isset($key) ) {
		if( isset($value) ) return $languages[$key]->$value;
		return $languages[$key];
	}
	return $languages;
}

/*
 * To set up the session variable for any given language and level
 * @since 1.0
 */
function setup_session_variable( $language = false, $level = false, $force = false ) {
	if ( !isset($_SESSION) ) return;
	if ( isset( $_SESSION[$language][$level] ) && !$force ) return;

	$questions_total 	= QUESTIONS_TOTAL; // total number of question per level
	$questions_min		= QUESTIONS_MIN; // minmum correct questions to advance

	$level_array = get_available_levels();
	$index = get_level_index( $level_array );
	$index_names = get_level_index( 'names', $level_array );

	/*
	 * Get the Sign Up Information Object ID
	 */
	$args = array(
		'post_type' => 'lainfo',
		'tax_query' => array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'language',
				'field'    => 'slug',
				'terms'    => $language,
			),
			array(
				'taxonomy' => 'level',
				'field'    => 'slug',
				'terms'    => $index[$level],
			)
		),
		'posts_per_page' => 1
	);
	$the_query = new WP_Query( $args );
	$info_object_id = $the_query->posts[0]->ID;

	if( $level < get_max_open_level($language) ) { // Open Levels

		$step = -1;

		// Get the questions
		$args = array(
			'post_type' => 'laq',
			'tax_query' => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'language',
					'field'    => 'slug',
					'terms'    => $language,
				),
				array(
					'taxonomy' => 'level',
					'field'    => 'slug',
					'terms'    => $index[$level+1],
				)
			),
			'orderby' => 'rand',
			'posts_per_page' => $questions_total
		);
		$the_query = new WP_Query( $args );
		$questions = $the_query->posts;

		/*
		 * get correct answers
		 * the correct answers will be stored in $correct_answers
		 */
		$correct_answers = array();
		foreach ( $questions as $key => $post ) {
			$temp = get_field( 'correct_answers', $post->ID );
			$correct_answers[$key] = (int)$temp[0];
		}
	} elseif ( $level == get_max_open_level($language) ) { // Last Open Level

		$step = QUESTIONS_TOTAL;
		$questions = false;
		$correct_answers = array_fill( 0, $questions_total, false );

	} else { // Closed Levels

		$step = 404;

	}

	/*
	 * Build Session
	 */
	if( !isset( $_SESSION[$language]['index'] ) ) {
		$_SESSION[$language]['index'] = $index;
		$_SESSION[$language]['index-names'] = $index_names;
		$_SESSION[$language]['current-level'] = 0;
		$_SESSION[$language]['current-step'] = -1;
		$_SESSION[$language]['progress-level'] = 0;
		$_SESSION[$language]['progress-step'] = -1;
	}

	$_SESSION[$language][$level] = array(
		'step'				=> $step,
		'questions' 		=> $questions,
		'correct_answers' 	=> $correct_answers,
		'chosen_answers' 	=> array_fill( 0, $questions_total, false ),
		'level_passed'		=> false,
		'info_object_id'	=> $info_object_id
	);
	set_default ( $_SESSION['count'], 0 );
	//var_dump( $_SESSION );
}

/*
 * Get the key of the object with the given slug
 */
function search_for_slug( $slug, $terms ) {
   foreach ($terms as $key => $term) {
       if ($term->slug === $slug) return $key;
   }
   return null;
}

/**
 * Returns the number of correct results
 */
function compare_results( $correct_answers, $chosen_answers ) {
	$i = 0;
	foreach( $correct_answers as $key => $value ) {
		if( $chosen_answers[$key] == $value ) $i++;
	}
	return $i;
}


/*
 * Ajax Functions
 * @since 1.0
 */
add_action( 'wp_ajax_get_la_question', 'get_la_question' );
add_action( 'wp_ajax_nopriv_get_la_question', 'get_la_question' );

function get_la_question() {
	/** Read $_POST **/
	$data = $_POST['data'];
	$_language = $data['language'];

	/** Interact with Session **/
	if ( !isset($_SESSION) ) session_start();
	$_SESSION['time'] = time();
	if( $data['interaction'] == 'next' ) {
		$_lvl = (int)$_SESSION[$_language]['current-level'];
	} else {
		$_lvl = (int)$data['level'];
	}
	setup_session_variable( $_language, $_lvl ); // Setup the level (only if not existing)
	$_la = $_SESSION[$_language];
	$_step = $_la[$_lvl]['step'];

	/*
	 *	Part 1: Process Information
	 */

	/** prevent cheating **/
	/* You cannot have the questions for a higher level if you didn't pass the lower one. */
	if( $_lvl >= 1 && !$_la[$_lvl-1]['level_passed'] ) $you_forgot_something = true;

	$_la['step-before'] = $_step;

	/** increasing $_step **/
	$show_saved_info = false;
	switch( $data['interaction'] ) {
		case 'next':
			if( $_step == -1 ) {

				/*
				 * BEFORE: Instruction Screen
				 * Next: Start with the first question
				 */
				$_step = 0;

			} elseif ( 0 <= $_step && $_step < QUESTIONS_TOTAL-1 ) {

				/*
				 * BEFORE: Previous Question Screen
				 * ACTION: Save the Answer
				 */
				if( isset( $data['chosenAnswer'] ) ) $_la[$_lvl]['chosen_answers'][$_step] = (int)$data['chosenAnswer'];
				/* NEXT: Advance to next Question */
				$_step++;

			} elseif( $_step == QUESTIONS_TOTAL-1 ) {

				/*
				 * BEFORE: Last Question Screen
				 * ACTION: Evaluate answers
				 */
				if( isset( $data['chosenAnswer'] ) ) $_la[$_lvl]['chosen_answers'][$_step] = (int)$data['chosenAnswer'];
				// Calculate result
				$i = compare_results( $_la[$_lvl]['correct_answers'], $_la[$_lvl]['chosen_answers'] );
				$percent = round( $i/QUESTIONS_TOTAL*100 );
				// Compare Result
				if( QUESTIONS_MIN <= $i ) {
					$_la[$_lvl]['level_passed'] = true;
					$_la['progress-level'] = $_la['progress-level']+1;
					$_la['progress-step'] = -1;
				}
				// save to database
				la_database_query( 'update', $_la['index-names'][$_la['progress-level']], $percent.'%' );
				$show_saved_info = true;

				/* NEXT: Show Level Evaluation Screen */
				$_step = QUESTIONS_TOTAL;

			} elseif( $_step == QUESTIONS_TOTAL ) {

				/*
				 * BEFORE: Level Evaluation Screen
				 * NO NEXT: This is the last screen
				 * (NEXT: Advance to Next Level's Instruction Screen OBSOLET)
				 */
				/*if( $_la[$_lvl]['level_passed'] ) {
					$_step = -1;
					$_lvl = $_lvl+1;
					setup_session_variable( $_language, $_lvl ); // Setup the level (only if not existing)
					$_la[$_lvl] = $_SESSION[$_language][$_lvl];
				} else {
					// Don't advance (that would be cheating)
				}*/

			}
			break;
		case 'signup':
			/*
			 * Signup Information were requested
			 * ACTION: Show Signup Information
			 */
			$_step = 100;
			/* NO NEXT */
			break;
		case 'open':
		default:
			/*
			 * Modal Opened
			 * ACTION: Show last open screen
			 */
			break;
	}

	/** Exception for the last open level ... it will jump trait to the sign up info **/
	//if( $_lvl == MAX_LEVEL_ENGLISH ) $_step = QUESTIONS_TOTAL;

	$_la[$_lvl]['step'] = $_step; // write it to the session

	$_la['step-after'] = $_step;

	/*
	 *	Part 2: Create Responce
	 */
	$next_btn = array(
		'enable' => true,
		'unlock' => true
	);
	//$enable_next_btn = true;
	//$unlock_next_btn = true;

	if( isset( $you_forgot_something ) ) { // If someone tries to cheat

		/** Cheat Prevention **/
		$title = __( 'Did you forget something?', 'level-assessment' );
		$print = '<div class="clearfix">';
		$print .= '<div class="la-completed pull-left" style="background-color: red;"><span class="dashicons dashicons-lock"></span></div>';
		$print .= '<p class="lead" style="margin: 48px 0 0 200px;">';
		$print .= sprintf( _x( 'Hey my friend!<br>You should first do %1$s.', '%1$s represents a level', 'level-assessment' ), $_la['index-names'][$_lvl-1] );
		$print .= '</p></div>';

		$next_btn['enable'] = $next_btn['unlock'] = false;

	} elseif( $_step == -1 ) { // before the first question

		/** Instruction Screen **/
		$title = __( 'Instructions', 'level-assessment' );
		$print = '<div class="text-center level-instruction-screen">';
		$print .= sprintf( '<h3>'.__( 'Testing your knowlege of %1$s', 'level-assessment' ).'</h3>', '<span class="label label-primary">'.$_la['index-names'][$_lvl].'</span>' );
		if( $data['interaction'] !== 'next' ) {
			$print .= '<p>Preste muita atenção às perguntas!</p>';
			$print .= '<p>Cada pergunta foi elaborada de forma a testar conhecimentos mais profundos da língua inglesa. Certifique-se de considerar cada pergunta com cuidado.</p>';
		}
		$print .= sprintf( '<p class="lead">'.'Você deverá acertar pelo menos %1$s das %2$s questões para passar de nível.'.'</p>', QUESTIONS_MIN, QUESTIONS_TOTAL );
		$print .= '</div>';

	} elseif( $_step == 100 ) { // after passed or failed, Signup info

		/** Level Signup Information **/

		$title = __( 'Information to Sign Up', 'level-assessment' );

		$print = '<div class="text-center"><h3>'.sprintf( __( 'How to sign up for %1$s', 'level-assessment' ), '<span class="label label-success">'.$_la['index-names'][$_lvl].'</span>' ).'</h3></div>';
		$info_object_screen_content = get_field( 'level_assessment_screen', $_la[$_lvl]['info_object_id'] );
		$print .= '<div class="la-info">'.$info_object_screen_content.'</div>';

		/** Choose better time panel **/
		if( $better_time ) {
			$print .= '<div id="la-better-time" class="modal-meta">';
			$print .= __( 'The suggested times are not possible for you?', 'level-assessment' );
			$print .= '<button type="button" class="btn btn-link btn-xs" data-toggle="collapse" data-target="#BetterTimeCollapse" aria-expanded="false" aria-controls="BetterTimeCollapse">';
			$print .= __( 'Tell us a better time', 'level-assessment' );
			$print .= '</button>';
			// $print .= '<div id="BetterTimeCollapse" class="collapse la-better-time-container"><div class="panel panel-default"><div class="panel-body la-better-time-inner">';
			// $print .= get_better_time_content( $_la[$_lvl]['info_object_id'], $_la['index'][$_lvl] );
			// $print .= '</div></div></div></div>';
		}

		$next_btn['enable'] = $next_btn['unlock'] = false;

	} elseif( $_step == QUESTIONS_TOTAL ) { // just after the last question, passed or failed

		/** Passed or Failed **/
		// Conditions
		// 1. Current Level is shown
		// 2. Lower than current Level is shown
		if( isset($_la[$_lvl+1]) ) $topmost_lvl = false;
		else $topmost_lvl = true;

		$print = '<div class="clearfix">';

		// $print .= '<div style="margin-left: 160px;">';

		/**
		 * Display the score first
		 */
		$i = compare_results( $_la[$_lvl]['correct_answers'], $_la[$_lvl]['chosen_answers'] );
		$evaluation = sprintf( __('You answered %1$s correctly and made %2$s!', 'level-assessment' ),
			sprintf( '<strong>'._n( '%s question', '%s questions', $i, 'level-assessment' ).'</strong>', $i ),
			sprintf( '<strong>'._n( '%s mistake', '%s mistakes', QUESTIONS_TOTAL-$i, 'level-assessment' ).'</strong>', QUESTIONS_TOTAL-$i )

		);
		switch ( $_la[$_lvl]['level_passed'] ) {
			case true:   $print .= '<div class="alert alert-success text-center">' . $evaluation . '</div>'; break;
			case false:  $print .= '<div class="alert alert-danger text-center">' . $evaluation . '</div>'; break;
		}

		/**
		 * Print the Icon
		 */
		switch ( $_la[$_lvl]['level_passed'] ) {
			case true:   $print .= '<div class="la-completed pull-left"><span class="dashicons dashicons-yes"></span></div>'; break;
			case false:  $print .= '<div class="la-completed pull-left" style="background-color: red;"><span class="dashicons dashicons-no"></span></div>'; break;
		}

		if( $_la[$_lvl]['level_passed'] && $_lvl === MAX_LEVEL_ENGLISH ) { // OBSOLET

			$title = __( 'Congratulations!', 'level-assessment' );
			$print .= '<p class="lead level-responce-screen">';
			$print .= sprintf( _x( 'Congratulations!<br>You passed %1$s! This is the highest level we offer by now.', '<br> is a line break', 'level-assessment' ), '<span class="label label-success">'.$_la['index-names'][$_lvl].'</span>' );
			$print .= '</p>';
			//$print .= '<p><button id="LASignupButton" type="button" class="btn btn-success" data-level="'.$_lvl.'">'.sprintf( __( 'I want to Sign Up for %1$s', 'level-assessment' ), $_la['index-names'][$_lvl] ).'</button></p>';

		} elseif( $_la[$_lvl]['level_passed'] && !$topmost_lvl ) {

			$title = __( 'Congratulations!', 'level-assessment' );
			$print .= '<p class="lead level-responce-screen">';
			$print .= sprintf( _x( '%1$s would be boring to you, you should study a higher level!', '<br> is a line break', 'level-assessment' ), '<span class="label label-success label-unstress">'.$_la['index-names'][$_lvl].'</span>' );
			$print .= '</p>';
			$print .= '<p><button id="LASignupButton" type="button" class="btn btn-success" data-level="'.$_lvl.'">'.sprintf( __( 'I want to Sign Up for %1$s', 'level-assessment' ), $_la['index-names'][$_lvl] ).'</button></p>';

		} elseif( $_la[$_lvl]['level_passed'] ) {

			$title = __( 'Congratulations!', 'level-assessment' );
			$print .= '<p class="lead level-responce-screen">';
			$print .= sprintf( _x( 'Congratulations!<br>You have the knowlege of %1$s, you should study %2$s.', '<br> is a line break', 'level-assessment' ), '<span class="label label-success label-unstress">'.$_la['index-names'][$_lvl].'</span>', '<span class="label label-primary">'.$_la['index-names'][$_lvl+1].'</span>' );
			$print .= '</p>';
			//$print .= '<p><button id="LASignupButton" type="button" class="btn btn-default" data-level="'.$_lvl.'">'.sprintf( __( 'I want to Sign Up for %1$s', 'level-assessment' ), $_la['index-names'][$_lvl] ).'</button></p>';

		} elseif( $_la[$_lvl]['questions'] === false ) {

			$title = __( 'Congratulations!', 'level-assessment' );
			$print .= '<p class="lead level-responce-screen">';
			$print .= sprintf( _x( '%1$s is our highest level by now. Sign up! You will learn someting!', '<br> is a line break', 'level-assessment' ), '<span class="label label-success label-unstress">'.$_la['index-names'][$_lvl].'</span>' );
			$print .= '</p>';
			$print .= '<p><button id="LASignupButton" type="button" class="btn btn-success" data-level="'.$_lvl.'">'.sprintf( __( 'I want to Sign Up for %1$s', 'level-assessment' ), $_la['index-names'][$_lvl] ).'</button></p>';

		} else {

			$title = __( 'Sorry!', 'level-assessment' );
			$print .= '<p class="lead level-responce-screen">';
			$print .= sprintf( _x( 'You should really study %1$s, because you are not yet ready for %2$s.', '<br> is a line break', 'level-assessment' ), '<span class="label label-success label-unstress">'.$_la['index-names'][$_lvl].'</span>', '<span class="label label-default label-unstress">'.$_la['index-names'][$_lvl+1].'</span>' );
			$print .= '</p>';
			$print .= '<p><button id="LASignupButton" type="button" class="btn btn-success" data-level="'.$_lvl.'">'.sprintf( __( 'I want to Sign Up for %1$s', 'level-assessment' ), $_la['index-names'][$_lvl] ).'</button></p>';

		}

		// Sign Up Button
		/*$alter = ( $_la[$_lvl]['level_passed'] && $_lvl !== MAX_LEVEL_ENGLISH && $topmost_lvl ? true : false );
		$temp_lvl = $_lvl;
		//$temp_lvl = ( $alter ? $_lvl+1 : $_lvl ); OBSOLET
		if( $alter ) {
			$label = sprintf( __( 'I want to Sign Up for %1$s anyways!', 'level-assessment' ), $_la['index-names'][$temp_lvl] );
		} else {
			$label = sprintf( __( 'I want to Sign Up for %1$s', 'level-assessment' ), $_la['index-names'][$temp_lvl] );
		}
		$print .= sprintf( '<p><button id="LASignupButton" type="button" class="btn %1$s" data-level="%2$s">%3$s</button></p>',
			( $alter ? 'btn-default' : 'btn-success' ),
			$temp_lvl,
			$label
		);*/

		// close container
		$print .= '</div>';

		// Next Button
		$next_btn['enable'] = $next_btn['unlock'] = false;
		/*if( $_la[$_lvl]['level_passed'] && $_lvl == $_la['progress-level']-1 && $_la['progress-step'] == -1 && $_lvl !== MAX_LEVEL_ENGLISH ) {
			$next_btn['enable'] = $next_btn['unlock'] = false;
		}*/

	} else { // display a question

		/** Print Question **/
		$title = __( 'Question', 'level-assessment' ).' '.( $_step+1 ).'/'.QUESTIONS_TOTAL;
		$post = $_la[$_lvl]['questions'][$_step];
		$print = get_question_content( $post );
		// Next Button
		$next_btn['enable'] = false;

	};

	/** Progress Bar **/
	if( $_step <= 0 ) {
		$progressbar = 0;
		$progressbar_label = '0/'.QUESTIONS_TOTAL;
	} elseif ( $_step <= QUESTIONS_TOTAL ) {
		$progressbar = round( $_step/QUESTIONS_TOTAL*100 );
		$progressbar_label = $_step.'/'.QUESTIONS_TOTAL;
	} else {
		$progressbar = 100;
		$progressbar_label = ( $_la[$_lvl]['level_passed'] ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no"></span>' );
	}

	// Build Level Index (so javascript knows where we are)
	$_la[$_lvl]['step'] = (int)$_step;
	$level_index = get_level_button_index( $_la, $_language );

	/** Level Indicator (added to $title) **/
	$title .= '<div class="level-indicator">';
	foreach( $_la['index'] as $i => $slug ) {

		switch ($level_index[$i]) {
			case 0: $color = 'label-default'; break;
			case 1: $color = 'label-primary'; break;
			case 2:
			case 3: $color = 'label-success'; break;
		}

		$title .= sprintf( '<span id="level-inidcator-%1$s" class="label %2$s %3$s">%4$s</span> ',
			$slug,
			$color,
			( $_lvl == $i ? '' : 'label-unstress' ),
			$_la['index-names'][$i]
		);
	}
	$title .= '</div>';


	/*
	 *	Part 3: Deliver Responce
	 */
	/** Update Session **/
	$_la['current-level'] = (int)$_lvl;
	$_la['current-step'] = $_la[$_lvl]['step'] = (int)$_step;
	if( $_la['current-level'] == $_la['progress-level'] ) $_la['progress-step'] = (int)$_step;
	$_SESSION[$_language] = $_la;

	/** echo json array **/
	echo json_encode(
		array(
			'step' 				=> $_step,				// (int)
			'level-index'		=> $level_index,		// (array) status of each level
			'title' 			=> $title,				// (html) modal title
			'print' 			=> $print,				// (html) modal content
			'progressbar'		=> $progressbar,		// (int) Progress Bar Percentage
			'progressbar-label' => $progressbar_label,	// (html) Progress Bar Label
			'enable-next-btn'	=> $next_btn['enable'],	// (bool) Next Button: clickable?
			'unlock-next-btn'	=> $next_btn['unlock'],	// (bool) Next Button: unlocked?
			'show-saved-info'	=> $show_saved_info,	// (bool) Show a little text that tells that the progress has been saved
			'debug'				=> $_la					// (array) pls delete me
		)
	);

    wp_die();
}


/*
 * Modal Content for one question
 * @since 1.0
 */
function get_question_content( $post = false ) {
	$fields = get_fields( $post->ID );
	$output = '';
	if ( $fields['type_of_question'] == 'choice' ):

		$output .= '<div class="text-center"><small>Escolha a alternativa correta.</small></div>';
		$output .= '<p class="laq-question lead">'.$fields['question'].'</p>';
		$output .= '<div id="LAChoice" class="btn-multiple-choice" data-toggle="buttons">';
		for ($i = 1; $i <= 5; $i++) {
			$option = $fields['option_'.$i];
			if ( empty( $option ) ) continue;
			$output .= sprintf( '<label class="btn laq-choice btn-lg btn-block"><input type="radio" name="multiple-choice" id="%1$s" data-value="%2$s" autocomplete="off">%3$s</input></label>',
				'option'.$i,
				$i,
				$option
			);
		}
		$output .= '</div>';

	elseif ( $fields['type_of_question'] == 'blank' ):

		$dropdown = '<select id="LAChoice" class="laq-dropdown"><option value="0">'.__( 'Choose...', 'level-assessment' ).'</option>';
		for ($i = 1; $i <= 5; $i++) {
			$option = $fields['option_'.$i];
			if ( empty( $option ) ) continue;
			$dropdown .= '<option value="'.$i.'">'.$option.'</option>';
		}
		$dropdown .= '</select>';
		$print = str_replace( "%%blank%%", $dropdown, $fields['question'] );

		$output .= '<p class="laq-question lead">';
		$output .= str_replace( "%%blank%%", $dropdown, $fields['question'] );
		$output .= '</p>';

	endif;

	return $output;
}

/*
 * Build Level Index (so javascript knows where we are)
 * 0 = locked
 * 1 = current
 * 2 = passed
 * 3 = failed
 *
 * @since: 1.2
 */
function get_level_button_index( $_la, $lang ) {

	$return = array_fill( 0, count( $_la['index'] ), 0 );

	foreach( $_la['index'] as $i => $slug ) {
		if( $i <= $_la['progress-level'] ) {
			if( !isset($_la[$i]) ) {
				$return[$i] = 1;
			} elseif( $_la[$i]['level_passed'] ) { // Passed Level
				$return[$i] = 2;
			} elseif( $i == get_max_open_level( $lang ) ) { // Final level
				$return[$i] = 2;
			} elseif( $_la[$i]['step'] < QUESTIONS_TOTAL ) { // Level with active test
				$return[$i] = 1;
			} else {
				$return[$i] = 3;
			}
		}
	}

	return $return;
}


/*
 * Get the Content for the Better Time Select thing
 * @since 1.0
 */
add_action( 'wp_ajax_la_better_time', 'get_better_time_content' );
add_action( 'wp_ajax_nopriv_la_better_time', 'get_better_time_content' );
function get_better_time_content( $object_id = NULL, $level_slug = NULL, $language_slug = NULL, $alt_time_string = NULL ) {
	if( isset( $_POST['action'] ) && $_POST['action'] == 'la_better_time' ) {

		// save results in database
		$return = la_database_query( 'time' );

		// write to session
		$_SESSION[ $_SESSION['identity']['language'] ][ $_POST['data']['level'] ]['better_time'] = $_POST['data']['value'];

		// return a json variable that says okay
		echo json_encode( array( 'status' => $return ) );
		wp_die();


	} else {

		if( $_SESSION['identity']['language'] ) {

			$alt_time_string = get_field( 'alternative_time', $object_id );
			if( $alt_time_string ) {
				$alt_time_array = explode( PHP_EOL, $alt_time_string );
				$preselected = ( isset( $_SESSION[ $_SESSION['identity']['language'] ][ $level_slug ]['better_time'] ) ? $_SESSION[ $_SESSION['identity']['language'] ][ $level_slug ]['better_time'] : '' );
				$print = '<form class="form-inline"><div class="form-group"><label for="SelectTimes">'.__( 'Which time fits you better?', 'level-assessment' ).'</label> &nbsp; <select id="LASelectTimes" class="form-control" data-level="'.$level_slug.'">';
				$print .= '<option>'.__( 'Select ...', 'level-assessment').'</option>';
				foreach( $alt_time_array as $option ) {
					$option = str_replace("\r", '', $option);
					$selected = ( $preselected==$option ? ' selected="selected"' : '' );
					$print .= '<option value="'.$option.'"'.$selected.'>'.$option.'</option>';
				}
				$print .= '</select> <span class="dashicons dashicons-yes'.( $preselected ? '' : ' hidden').'"></span><span class="preloader preloader-box hidden"></span></div></form>';
				return $print;
			} else {
				return false;
			}

		} else {

			if( !isset($alt_time_string) ) $alt_time_string = get_field( 'alternative_time', $object_id );
			if( $alt_time_string ) {
				$alt_time_array = explode( PHP_EOL, $alt_time_string );
				$preselected = false;
				$print = '<form id="'.$level_slug.'-form" class="better-time-form form-inline"><div class="form-group"><label for="SelectTimes">'.__( 'Which time fits you better?', 'level-assessment' ).'</label> &nbsp; <select id="LASelectTimes" class="form-control" data-level="'.$level_slug.'">';
				foreach( $alt_time_array as $option ) {
					$option = str_replace("\r", '', $option);
					$selected = ( $preselected==$option ? ' selected="selected"' : '' );
					$print .= '<option value="'.$option.'"'.$selected.' required>'.$option.'</option>';
				}
				$print .= '</select></div>';
				$print .= sprintf( ' <div class="form-group"><label for="BetterTimeEmail" class="sr-only">%1$s</label><input type="email" class="form-control" id="BetterTimeEmail" placeholder="%2$s" required></div>',
					__( 'Email Address', 'imi-lang'),
					__( 'Email', 'imi-lang')
				);
				$print .= ' <button type="submit" class="btn btn-success">'.__( 'Submit', 'level-assessment').'</button>';
				$print .= '</form>';

				return $print;
			} else {
				return false;
			}
			//var_dump(  ); die();

		}

	}
}


/*
 * Update Session with any given values
 * @since 1.0
 */
add_action( 'wp_ajax_update_session', 'update_session' );
add_action( 'wp_ajax_nopriv_update_session', 'update_session' );
function update_session() {
	session_start();
	foreach( $_POST['data'] as $key => $value ) {
		$_SESSION[$key] = $value;
	}
	//echo json_encode( $_SESSION );
    wp_die();
}


/*
 * Setup / Start the Session
 * @since 1.0
 */
function setup_session() {
	if (!isset($_SESSION)) session_start();
	// If a new session is needed
	if (isset($_POST['la_name']) && $_POST['la_name'] != "") {
		unset($_SESSION);
		session_destroy();
		session_start();
	}

	set_default($_SESSION['count'], 0);
	$_SESSION['count']++;
	$_SESSION['time'] = time();
	set_default ( $_SESSION['identity'], array(
		'ID'		=> '',
		'name'		=> '',
		'email'		=> '',
		'language' 	=> '',
		'level'		=> ''
	));
}


/*
 * Principal function to interact with the level assessment database
 * @since 1.0
 * @update 1.2
 */
function la_database_query( $operation, $level = NULL, $value = NULL ) {
	global $wpdb, $_SESSION;
	$return = true;
	$table_name = $wpdb->prefix.'level_assessment';
	//var_dump( 'POST variable', $_POST );

	switch( $operation ) {
		case 'setup':

			if( isset( $_POST['la_name'] ) ) {
				// check if email occures already within the last 2 months
				$email = $_POST['la_email1'];
				$language = $_POST['la_language'];
				$query = $wpdb->prepare( "SELECT *
						FROM $table_name
						WHERE email = %s
						AND language = %s
						AND time > %s;",
						$email,
						$language,
						date( 'Y-m-d H:i:s', strtotime("-2 months") )
					);
				$rows = $wpdb->get_results( $query, ARRAY_A );

				if( count($rows) >= TRIALS_PER_PERSON ) return false; // exceeded number of tries
				// put identity into database
				$first_level = get_available_levels( 0 );
				$wpdb->insert(
					$table_name,
					array(
						'time' 		=> current_time('mysql'),
						'type' 		=> 'test',
						'name' 		=> $_POST['la_name'],
						'email' 	=> $_POST['la_email1'],
						'language' 	=> $_POST['la_language'],
						'level' 	=> $first_level->name,
						'value'		=> '0%'
					)
				);
				// save identity in session
				$_SESSION['identity'] = array(
					'ID'		=> $wpdb->insert_id,
					'name'		=> $_POST['la_name'],
					'email'		=> $_POST['la_email1'],
					'language' 	=> $_POST['la_language'],
					'level'		=> $first_level->slug
				);
			}

			break;
		case 'update':

			// update level with given id
			$wpdb->update(
				$table_name,
				array(
					'level' => $level,
					'value' => ( isset( $value ) ? $value : '' )
				),
				array(
					'ID' 	=> $_SESSION['identity']['ID']
				)
			);
			// update session
			$_SESSION['identity']['level'] = $level;

			break;
		case 'time':

			//var_dump( $_POST );
			$data = $_POST['data'];
			$update = $wpdb->update(
				$table_name,
				array(
					'value' => $data['value']
				),
				array(
					'type' 		=> 'time',
					'email'		=> $_SESSION['identity']['email'],
					'language'	=> $_SESSION['identity']['language'],
					'level' 	=> $data['level']
				)
			);
			if( !$update ) {
				$wpdb->insert(
					$table_name,
					array(
						'time' 		=> current_time('mysql'),
						'type' 		=> 'time',
						'name' 		=> $_SESSION['identity']['name'],
						'email' 	=> $_SESSION['identity']['email'],
						'language' 	=> $_SESSION['identity']['language'],
						'level' 	=> $data['level'],
						'value' 	=> $data['value']
					)
				);
			}

			break;
		default:
			break;
	}
	return $return;
}
