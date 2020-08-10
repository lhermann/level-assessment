<?php
/**
 * Section: Cursos
 *
 * @package WordPress
 * @theme IMI
 * @since IMI 0.1
 */
global $post, $la_config;

$languages = get_terms( 'language', array(
  'orderby'    => 'id',
  'hide_empty' => 1 // this should be 1
  )
);
$ages = get_available_tax( 'age' );
$args = array(
  'posts_per_page'  => -1,
  'order'       => 'ASC',
  'post_type'     => 'lainfo'
);
$lainfo_array = get_posts( $args );
foreach ( $lainfo_array as $i => $lainfo ) {
  $current_age = get_field( 'age', $lainfo->ID );
  //print('<pre>');print_r( $current_age );print('</pre>');
  $slug = ( is_array($current_age) ? $current_age[0]->slug : $current_age->slug ); // IIS bugfix
  $lainfo_array[$i]->age = $slug;
}


?>
<section id="<?= $post->post_name; ?>" class="section-<?= $post->post_name; ?> la-section-cursos">
  <div class="fadba-flagg"></div>
  <div class="section-heading inline-div">
    <h1><?= $la_config['title'] ?></h1>
    <?php if( count($languages) > 0 ): ?>
      <div class="select-language" data-toggle="buttons">
        <?php foreach( $languages as $i => $radio ) {
          printf( '<label class="btn btn-language %1$s %2$s"><input type="radio" name="options" id="%3$s" autocomplete="off" %4$s><span>%5$s<span></label>',
            'language-'.$radio->slug,
            ( $i===0 ? 'active' : '' ),
            'option-'.$radio->slug,
            ( $i===0 ? 'checked' : '' ),
            $radio->name
          );
        } ?>
      </div><!-- .select-language -->
      <?php if(false): ?><button id="select-language-other" type="button" class="btn btn-default" data-toggle="modal" data-target="#LanguagePollModal"><?php _e( 'Interested in another language?', 'imi-lang' ); ?></button><?php endif;  //TODO OBSOLETE remove ?>
    <?php endif; ?>
  </div>

  <div class="row">
    <div class="col-md-3 age-group">

      <div id="select-age" class="row" >
        <div class="select-age-group col-xs-12 col-sm-7 col-md-12" data-toggle="buttons">
          <?php foreach ( $ages as $i => $age ) {
            // button
            printf( '<label class="btn btn-lg btn-block %1$s %2$s"><input type="radio" name="options" data-value="%1$s" autocomplete="off" %3$s>%4$s</label>',
              'tab-'.$age->slug,
              ( $i===0 ? 'active' : '' ),
              ( $i===0 ? 'checked' : '' ),
              $age->name
            );
          }; ?>
        </div>
        <div class="col-xs-12 col-sm-5 col-md-12">
          <?php foreach ( $ages as $i => $age ) {
            // image
            printf( '<div id="%1$s" class="age-group-image text-center %2$s"><img src="%3$s" alt="%4$s"></div>',
              'tab-'.$age->slug.'-image',
              ( $i!==0 ? 'hidden' : '' ),
              get_bloginfo('template_directory').'/img/cursos-'.$age->slug.'.png',
              'Picture '.$age->name
            );
          }; ?>
        </div>
      </div><!-- .select-age-group -->

    </div><!-- .col-md-3 -->
    <div class="col-md-9 la-info-tabs-container">

      <?php foreach ( $ages as $i => $age ) {
        printf( '<div id="%1$s" class="panel-group la-info-tabs %2$s" role="tabpanel">',
          'tab-'.$age->slug,
          ( $i!==0 ? 'hidden' : '' )
        );
        print( '<h2 class="sr-only">'.$age->name.'</h2>' );

        // Navigation Tabs
        print( '<ul class="nav nav-tabs" role="tablist">' );
        $first = true;
        foreach ( $lainfo_array as $i => $lainfo ) {
          if( $lainfo->age !== $age->slug ) continue;
          printf( '<li role="presentation" class="%1$s"><a href="#%2$s" aria-controls="%2$s" role="tab" data-toggle="tab">%3$s</a></li>',
            ( $first ? 'active' : '' ),
            $age->slug.'-'.$lainfo->post_name,
            '<span class="dashicons dashicons-plus-alt"></span> '.$lainfo->post_title
          );
          $first = false;
        }
        print( '</ul>' );

        // Tap Panes
        print( '<div class="tab-content">' );
        $first = true;
        foreach ( $lainfo_array as $i => $lainfo ) {
          if( $lainfo->age !== $age->slug ) continue;

          $alt_time_string = get_field( 'alternative_time', $lainfo->ID );
          if( $alt_time_string && false ) { //TODO OBSOLETE remove
            $better_time = sprintf( '<div id="la-better-time" class="modal-meta">%1$s<button type="button" class="btn btn-link btn-xs" data-toggle="collapse" data-target="#%2$s" aria-expanded="false" aria-controls="%2$s">%3$s</button>',
              __( 'The suggested times are not possible for you?', 'level-assessment' ),
              'BetterTimeCollapse-'.$lainfo->post_name,
              __( 'Tell us a better time', 'level-assessment' )
            );
            $better_time .= sprintf( '<div id="%1$s" class="collapse la-better-time-container"><div class="panel panel-default"><div class="panel-body la-better-time-inner">%2$s</div></div></div>',
              'BetterTimeCollapse-'.$lainfo->post_name,
              get_better_time_content( $lainfo->ID, $lainfo->post_name, $languages[0]->slug, $alt_time_string )
            );
            $better_time .= '</div>';
          } else {
            $better_time = '';
          }



          printf( '<div role="tabpanel" class="tab-pane fade %1$s" id="%2$s">%3$s%4$s%5$s</div>',
            ( $first ? 'in active' : '' ),
            $age->slug.'-'.$lainfo->post_name,
            '<h3 class="sr-only">'.$lainfo->post_title.'</h3>',
            $lainfo->post_content,
            $better_time
          );
          $first = false;
        }
        print( '</div>' );


        print( '</div><!-- #tab-'.$age->slug.'.panel-group -->' );
      }
      print( "<script type='text/javascript'> var activeTab = '#tab-".$ages[0]->slug."'; </script>" );
      ?>

    </div><!-- .col-md-9 -->
  </div><!-- .row -->
</section>
<!-- /section -->
