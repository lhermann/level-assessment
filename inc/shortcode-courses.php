<?php
/**
 * [level-assessment-courses title="Cursos"]
 */
function shortcode_level_assessment_courses_func($atts) {
  global $wp, $la_config;
  if (isset($wp->query_vars['rest_route']) || is_admin()) return;

  $la_config = shortcode_atts( array(
    'title' => 'Cursos',
  ), $atts );

  require('html-courses.php');
}
add_shortcode( 'level-assessment-courses', 'shortcode_level_assessment_courses_func' );
