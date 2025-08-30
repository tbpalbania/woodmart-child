<?php
/**
 * Initialize all custom shortcodes for Woodmart Child Theme
 * 
 * @package WoodmartChild
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Include and initialize all shortcode files
 */
function woodmart_child_init_shortcodes() {
    // Define shortcode directory
    $shortcodes_dir = get_stylesheet_directory() . '/inc/shortcodes/';
    
    // Include Autor shortcodes
    $autor_shortcodes = array(
        'autor-on-focus.php',
        'product-autor-terms.php'
    );
    
    foreach ( $autor_shortcodes as $shortcode_file ) {
        $file_path = $shortcodes_dir . 'autor/' . $shortcode_file;
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        }
    }
    
    // Include Single Product shortcodes
    $single_product_shortcodes = array(
        'related-products-by-author.php'
    );
    
    foreach ( $single_product_shortcodes as $shortcode_file ) {
        $file_path = $shortcodes_dir . 'single-product/' . $shortcode_file;
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        }
    }
    
    // Hook for adding more shortcodes in the future
    do_action( 'woodmart_child_after_shortcodes_init' );
}

// Initialize shortcodes when WordPress initializes
add_action( 'init', 'woodmart_child_init_shortcodes', 10 );

/**
 * Register shortcodes with Visual Composer/WPBakery if available
 */
function woodmart_child_register_vc_shortcodes() {
    if ( ! function_exists( 'vc_map' ) ) {
        return;
    }
    
    // Register Autor On Focus shortcode with Visual Composer
    vc_map( array(
        'name' => __( 'Autor On Focus', 'woodmart-child' ),
        'base' => 'autor-on-focus',
        'category' => __( 'Woodmart Child', 'woodmart-child' ),
        'description' => __( 'Display top authors by sales', 'woodmart-child' ),
        'icon' => 'icon-wpb-woocommerce',
        'params' => array(
            array(
                'type' => 'textfield',
                'heading' => __( 'Number of Authors', 'woodmart-child' ),
                'param_name' => 'limit',
                'value' => '5',
                'description' => __( 'Number of authors to display (1-12)', 'woodmart-child' )
            ),
            array(
                'type' => 'textfield',
                'heading' => __( 'Title', 'woodmart-child' ),
                'param_name' => 'title',
                'value' => 'Authors on Focus',
                'description' => __( 'Section title', 'woodmart-child' )
            ),
            array(
                'type' => 'dropdown',
                'heading' => __( 'Show Title', 'woodmart-child' ),
                'param_name' => 'show_title',
                'value' => array(
                    __( 'Yes', 'woodmart-child' ) => 'true',
                    __( 'No', 'woodmart-child' ) => 'false'
                ),
                'std' => 'true'
            ),
            array(
                'type' => 'dropdown',
                'heading' => __( 'Show Product Count', 'woodmart-child' ),
                'param_name' => 'show_count',
                'value' => array(
                    __( 'No', 'woodmart-child' ) => 'false',
                    __( 'Yes', 'woodmart-child' ) => 'true'
                ),
                'std' => 'false'
            ),
            array(
                'type' => 'textfield',
                'heading' => __( 'Custom CSS Class', 'woodmart-child' ),
                'param_name' => 'class',
                'value' => 'autor-on-focus',
                'description' => __( 'Add custom CSS class', 'woodmart-child' )
            )
        )
    ) );
    
    // Register Product Autor Terms shortcode with Visual Composer
    vc_map( array(
        'name' => __( 'Product Author Terms', 'woodmart-child' ),
        'base' => 'product_autor_terms',
        'category' => __( 'Woodmart Child', 'woodmart-child' ),
        'description' => __( 'Display authors for current product', 'woodmart-child' ),
        'icon' => 'icon-wpb-woocommerce',
        'params' => array(
            array(
                'type' => 'textfield',
                'heading' => __( 'Limit', 'woodmart-child' ),
                'param_name' => 'limit',
                'value' => '0',
                'description' => __( 'Number of authors to show (0 for all)', 'woodmart-child' )
            ),
            array(
                'type' => 'textfield',
                'heading' => __( 'Custom CSS Class', 'woodmart-child' ),
                'param_name' => 'class',
                'value' => 'product-autor-terms',
                'description' => __( 'Add custom CSS class', 'woodmart-child' )
            )
        )
    ) );
    
    // Register Related Products by Author shortcode with Visual Composer
    vc_map( array(
        'name' => __( 'Related Products by Author', 'woodmart-child' ),
        'base' => 'related_products_by_author',
        'category' => __( 'Woodmart Child', 'woodmart-child' ),
        'description' => __( 'Display products from the same author', 'woodmart-child' ),
        'icon' => 'icon-wpb-woocommerce',
        'params' => array(
            array(
                'type' => 'textfield',
                'heading' => __( 'Number of Products', 'woodmart-child' ),
                'param_name' => 'limit',
                'value' => '4',
                'description' => __( 'Number of products to display', 'woodmart-child' )
            ),
            array(
                'type' => 'dropdown',
                'heading' => __( 'Columns', 'woodmart-child' ),
                'param_name' => 'columns',
                'value' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6'
                ),
                'std' => '4',
                'description' => __( 'Number of columns in the grid', 'woodmart-child' )
            ),
            array(
                'type' => 'textfield',
                'heading' => __( 'Title', 'woodmart-child' ),
                'param_name' => 'title',
                'value' => 'More from this Author',
                'description' => __( 'Section title', 'woodmart-child' )
            ),
            array(
                'type' => 'dropdown',
                'heading' => __( 'Show Title', 'woodmart-child' ),
                'param_name' => 'show_title',
                'value' => array(
                    __( 'Yes', 'woodmart-child' ) => 'true',
                    __( 'No', 'woodmart-child' ) => 'false'
                ),
                'std' => 'true'
            ),
            array(
                'type' => 'dropdown',
                'heading' => __( 'Order By', 'woodmart-child' ),
                'param_name' => 'orderby',
                'value' => array(
                    __( 'Date', 'woodmart-child' ) => 'date',
                    __( 'Price', 'woodmart-child' ) => 'price',
                    __( 'Random', 'woodmart-child' ) => 'rand',
                    __( 'Sales', 'woodmart-child' ) => 'sales',
                    __( 'Title', 'woodmart-child' ) => 'title'
                ),
                'std' => 'date',
                'description' => __( 'How to order the products', 'woodmart-child' )
            ),
            array(
                'type' => 'dropdown',
                'heading' => __( 'Order', 'woodmart-child' ),
                'param_name' => 'order',
                'value' => array(
                    __( 'Descending', 'woodmart-child' ) => 'DESC',
                    __( 'Ascending', 'woodmart-child' ) => 'ASC'
                ),
                'std' => 'DESC'
            ),
            array(
                'type' => 'dropdown',
                'heading' => __( 'Exclude Current Product', 'woodmart-child' ),
                'param_name' => 'exclude_current',
                'value' => array(
                    __( 'Yes', 'woodmart-child' ) => 'true',
                    __( 'No', 'woodmart-child' ) => 'false'
                ),
                'std' => 'true',
                'description' => __( 'Exclude the current product from the list', 'woodmart-child' )
            ),
            array(
                'type' => 'textfield',
                'heading' => __( 'Custom CSS Class', 'woodmart-child' ),
                'param_name' => 'class',
                'value' => 'related-products-by-author',
                'description' => __( 'Add custom CSS class', 'woodmart-child' )
            )
        )
    ) );
}
add_action( 'vc_before_init', 'woodmart_child_register_vc_shortcodes' );

/**
 * Register shortcodes with Elementor if available
 */
function woodmart_child_register_elementor_widgets() {
    // Check if Elementor is active
    if ( ! did_action( 'elementor/loaded' ) ) {
        return;
    }
    
    // Hook for registering Elementor widgets
    add_action( 'elementor/widgets/widgets_registered', function() {
        // Future implementation for Elementor widgets
    });
}
add_action( 'init', 'woodmart_child_register_elementor_widgets' );