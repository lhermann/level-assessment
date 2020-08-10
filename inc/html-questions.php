<?php
/**
 * Template Name: Level Assessment Page
 *
 * @package WordPress
 * @theme Twenty_Twelve
 * @childtheme IMI
 * @since IMI 0.1
 */

global $post;

// Add specific CSS class by filter to the page body
add_filter( 'body_class', 'la_body_class' );
function la_body_class ( $classes ) {
  $classes[] = 'full-width';
  return $classes;
}

/*
 * Setup the Session and Variables
 */
$problem = false;

if( isset($_POST['la_name']) && $_POST['la_name'] != ""  ) { // If a new session is needed

  if( !la_database_query( 'setup' ) ) $problem = 'max_reached';
  $language = $_POST['la_language'];

} elseif( $_SESSION['identity']['ID'] ) { // If the old session is still used

  $language = $_SESSION['identity']['language'];

} else { // Otherwise someone is cheating

  $problem = 'cheating';
  $_SESSION['identity']['ID'] = null;
}


/*
 * Show an Error Page if there is a $problem
 */
if( $problem ) {
  get_header();
  print( '<main id="content" role="main" class="main"><section><div class="container">' );
  print( '<h1>'.__('Sorry!', 'imi-lang').'</h1>' );
  if( $problem == 'max_reached' ) print( '<p class="lead">'.__('You have reached the maximum number of trials.', 'imi-lang').'</p>' );
  if( $problem == 'cheating' ) print( '<p class="lead">'.__('We need your information first.', 'imi-lang').'</p>' );
  print( '<p><a href="'.get_permalink($post->ID).'" type="button" class="btn btn-default btn-lg"><span class="dashicons dashicons-arrow-left-alt"></span> '.__('Go back', 'imi-lang').'</a></p>' );
  print( '</div></section></main>' );
  get_footer();
  exit;
}

// setup the first two levels
setup_session_variable( $language, 0 );
//setup_session_variable( $language, $levels[1]->slug );
set_default ( $_SESSION['level_assessment_alert'], '' );
?>

<section class="level-assessment-page">

  <h1 class="entry-title text-center">Oi <?php echo strtok( $_SESSION['identity']['name'], ' ' ); ?> – <?php the_title(); ?></h1>
  <?php if( !$_SESSION['level_assessment_alert'] ): ?>
    <div id="LevelAssessmentAlert" class="alert alert-info alert-dismissible fade in" role="alert">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <p><strong>Por que fazer o teste de nivelamento?</strong><br/>Às vezes os conhecimentos que adquirimos no dia a dia (por meio de jogos, propagandas e na internet) nos fazem pensar que já sabemos todo o básico. O nível básico do inglês não se limita apenas a cores e cumprimentos. Testar seu inglês te ajudará a perceber se você realmente compreende um texto e já tem um conhecimento estruturado da língua inglesa ou se seus conhecimentos se concentram mais em vocabulário. Não perca tempo e teste agora seus conhecimentos!<br/>Para saber mais sobre cada nível, confira nossa seção "Cursos".</p>
      <p><button type="button" class="btn btn-primary" data-dismiss="alert"><?php _e( 'Close', 'imi-lang' ); ?></button></p>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-sm-12 text-center">

      <div class="choose-level-container text-center">
        <div class="choose-level-buttons">
          <?php
          $index_names = $_SESSION[$language]['index-names'];
          $index_level_buttons = get_level_button_index( $_SESSION[$language], $language );

          /*
           * Print all the Level Buttons
           */
          foreach( $_SESSION[$language]['index'] as $lvlkey => $lvlslug ) {

            // Button Color
            switch ( $index_level_buttons[$lvlkey] ) {
              case 0: $color = 'btn-locked'; break;
              case 1: $color = 'btn-primary'; break;
              case 2:
              case 3: $color = 'btn-success'; break;
            }

            // Bridge Color
            switch ( $index_level_buttons[$lvlkey] ) {
              case 0: $bridge = ''; break;
              case 1: $bridge = ''; break;
              case 2: $bridge = 'level-passed'; break;
              case 3: $bridge = 'level-failed'; break;
            }

            // Net Yet Sign
            if( $lvlkey !== 0 && $lvlkey === get_max_open_level( $language )+1 ) {
              printf( '<p %1$s class="button-level-%2$s button-notice %3$s"><button type="button" class="btn btn-default" disabled="disabled">%4$s</button></p>',
                'id="level-'.$lvlkey.'-notice"',
                $lvlkey,
                ( $index_level_buttons[$lvlkey-1] > 0 ? '' : 'hidden' ),
                sprintf( _x( 'Sorry, we are not offering %1$s yet!', '%1$s represents a certain language level', 'imi-lang' ), $index_names[$lvlkey] )
              );
            }

            // Tooltip
            if( $lvlkey === 0 ) $text = __( 'Start the test', 'imi-lang' );
            else $text = __( 'Try for next level', 'imi-lang' );
            $tooltip = sprintf( '<div id="la-button-tooltip-%1$s" class="la-button-tooltip tooltip left" %2$s role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner">%3$s</div></div>',
              $lvlkey,
              ( $color == 'btn-primary' ? '' : 'style="display: none;"' ),
              $text
            );

            // Render Level Button
            printf( '<p %1$s class="button-level-%2$s button-level %3$s"><button type="button" class="btn %4$s btn-lg" %5$s data-toggle="modal" data-target="#LevelAssessmentModal" data-language="%8$s" data-level="%2$s">%7$s%6$s</button></p>',
              'id="level-'.$lvlkey.'"',
              $lvlkey,
              $bridge,
              $color,
              ( in_array( $color, array('btn-locked', 'btn-danger') ) ? 'disabled="disabled"' : '' ),
              $index_names[$lvlkey],
              ( $lvlkey === get_max_open_level( $language ) ? '' : $tooltip ),
              $language
            );
          }
          ?>

        </div><!-- .choose-level-buttons -->
      </div><!-- .choose-level-container -->

    </div><!-- .col-sm-12 -->
    <div class="col-sm-12">
      <?php //var_dump( $level_index, $_SESSION[$language] ); ?>
    </div>
  </div><!-- .row -->


  <!-- Level Assessment Modal -->
  <div class="modal fade" id="LevelAssessmentModal" tabindex="-1" role="dialog" aria-labelledby="LALabel" aria-hidden="true">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <div id="LAProgressBar" class="progress">
        <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">0/7</div>
      </div>
      <h4 class="modal-title" id="LALabel"><?php _e( 'Loading...', 'imi-lang'); ?></h4>
      </div>
      <div id="LAModalBody" class="modal-body preloader-window">
      <div class="modal-body-inner"></div>
      </div>
      <div class="modal-footer">
      <span class="pull-left la-progress-saved hidden"><span class="dashicons dashicons-yes"></span><small><?php _e( 'Your progress has been saved and forwarded to the Secretaria Geral.', 'imi-lang'); ?></small></span>
      <button id="LACloseButton" type="button" class="btn btn-default" data-dismiss="modal"><?php _e( 'Close', 'imi-lang'); ?></button>
      <button id="LANextButton" type="button" class="btn btn-success" disabled="disabled"><?php _e( 'Next', 'imi-lang'); ?></button>
      </div>
    </div>
    </div>
  </div><!-- .modal -->

</section>
