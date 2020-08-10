<?php
/**
 * Section: Teste de Nivelamento
 *
 * @package WordPress
 * @theme IMI
 * @since IMI 0.1
 */

/*
 *  Setup Session
 */
global $post;
$languages = get_terms( 'language', array(
  'orderby'    => 'id',
  'hide_empty' => 1 // this should be 1
  )
);
?>
<section id="<?php echo $post->post_name; ?>" class="section-<?php echo $post->post_name; ?>">
  <div class="container language-tabs">

    <div class="text-center">
      <h1><?php the_title(); ?></h1>
      <p css="lead"><?php _e( 'We need your name and email here so we can save the level you are legible to study.', 'imi-lang'); ?></p>
    </div>

    <?php if( count($languages) >= 1 ): ?>
      <ul class="nav nav-tabs" role="tablist">
        <?php foreach( $languages as $i => $tab ) {
          printf( '<li role="presentation" class="%2$s"><a class="btn btn-language %1$s" href="#%1$s" aria-controls="%1$s" role="tab" data-toggle="tab"><span>%3$s</span></a></li>',
            'language-'.$tab->slug,
            ( $i===0 ? 'active' : '' ),
            $tab->name
          );

        } ?>
      </ul>
    <?php endif; ?>

    <div class="tab-content">
    <?php foreach( $languages as $i => $content ): ?>

      <div role="tabpanel" class="row tab-pane tap-pane-languages <?php echo ( $i===0 ? 'active in' : ''); ?> <?php echo $content->slug; ?> fade" id="language-<?php echo $content->slug; ?>">
        <div class="col-sm-6 laentry-image text-center">
          <div class="laentry-image-inner center-block text-center">
            <h2 class="sr-only"><?php echo $content->name; ?></h2>
            <?php
              // map image
              printf( '<img src="%1$s" alt="%2$s">',
                plugin_dir_url(__DIR__).'/img/map-'.$content->slug.'.jpg',
                sprintf( __('Map of countries where %1$s is spoken', 'imi-lang'), $content->name )
              );
              // Subtitle
              printf( '<p class="lead"><span class="dashicons dashicons-admin-post"></span> '.__('Countries where %1$s is spoken.', 'imi-lang').'</p>',
                '<span>'.$content->name.'</span>'
              );
              //source
              printf( '<small>'.__('Source: %1$s', 'imi-lang').'</small>',
                '<a href="http://www.statsilk.com/maps/language-distribution-interactive-world-map" target="_blank" title="statsilk.com">statsilk.com</a>'
              );
            ?>
          </div>
        </div>
        <div class="col-sm-6 laentry-form">
          <form id="LAEntryForm-<?php echo $content->slug; ?>" class="la-form" action="<?= get_permalink( $post->ID ); ?>" method="post">
            <div class="form-group has-feedback">
              <label class="control-label" for="InputName-<?php echo $content->slug; ?>"><?php _e( 'Name', 'imi-lang'); ?></label>
              <input type="text" class="form-control" id="InputName-<?php echo $content->slug; ?>" name="la_name" placeholder="<?php _e( 'Full Name', 'imi-lang'); ?>" data-minlength="6" data-error="<?php _e( 'Please provide your full name', 'imi-lang'); ?>" required>
              <div class="help-block with-errors"></div>
            </div>
            <div class="form-group has-feedback">
              <label class="control-label" for="InputEmail1-<?php echo $content->slug; ?>"><?php _e( 'Email Address', 'imi-lang'); ?></label>
              <input type="email" class="form-control" id="InputEmail1-<?php echo $content->slug; ?>" name="la_email1" placeholder="<?php _e( 'Email', 'imi-lang'); ?>" data-error="<?php _e( 'Invalid Email', 'imi-lang'); ?>" required>
              <div class="help-block with-errors"></div>
            </div>
            <div class="form-group has-feedback">
              <label class="control-label" for="InputEmail2-<?php echo $content->slug; ?>"><?php _e( 'Confirm Email Address', 'imi-lang'); ?></label>
              <input type="email" class="form-control" id="InputEmail2-<?php echo $content->slug; ?>" name="la_email2" placeholder="<?php _e( 'Confirm Email', 'imi-lang'); ?>" data-match="#InputEmail1-<?php echo $content->slug; ?>" data-error="<?php _e( 'Invalid Email', 'imi-lang'); ?>" data-match-error="<?php _e( 'Emails don`t match', 'imi-lang'); ?>" required>
              <div class="help-block with-errors"></div>
            </div>
            <input type="hidden" name="la_language" value="<?php echo $content->slug; ?>">
            <button type="submit" class="btn btn-success"><?php _e( 'Start the Test', 'imi-lang'); ?></button>
            <?php
              if(defined('$_SESSION') && $_SESSION['identity']['language'] === $content->slug) print(
                '<strong>or</strong> <a type="button" class="btn btn-success" href="'.get_permalink( $post->ID ).'">'.__( 'Continue as', 'imi-lang' ).' '.$_SESSION['identity']['name'].'</a>'
              );
            ?>
          </form>
        </div>
      </div><!-- .row #language-<?php echo $content->slug; ?> -->

    <?php endforeach; ?>
    </div><!-- .tab-content -->

  </div><!-- .container -->
</section>
<!-- /section -->
