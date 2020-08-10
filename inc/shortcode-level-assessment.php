<?php
/**
 * [level-assessment trials_per_person="2" questions_total="7" questions_min="5"]
 */
function shortcode_level_assessment_func($atts) {
  global $wp, $la_config;
  if (isset($wp->query_vars['rest_route']) || is_admin()) return;

  $la_config = shortcode_atts( array(
    'trials_per_person' => TRIALS_PER_PERSON,
    'questions_total' => QUESTIONS_TOTAL,
    'questions_min' => QUESTIONS_MIN,
  ), $atts );

  if (
    isset($_POST['la_name']) ||
    (isset($_SESSION['identity']['name']) && $_SESSION['identity']['name'])
  ) {
    require('html-questions.php');
  } else {
    require('html-signup.php');
  }
}
add_shortcode( 'level-assessment', 'shortcode_level_assessment_func' );
