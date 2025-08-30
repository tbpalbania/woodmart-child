<?php
/**
 * Enqueue script and styles for child theme
 */
function woodmart_child_enqueue_styles() {
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'woodmart-style' ), woodmart_get_theme_info( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'woodmart_child_enqueue_styles', 10010 );


add_action( 'init', function() {
    update_option( 'wc_feature_woocommerce_brands_enabled', 'no' );
} );

/**
 * Load custom shortcodes
 */
require_once get_stylesheet_directory() . '/inc/shortcodes/init-shortcodes.php';