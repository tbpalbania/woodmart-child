<?php
/**
 * Related Products by Author Shortcode
 * 
 * Displays products from the same author(s) as the current product
 * Uses WooCommerce's default product loop for consistency
 * 
 * @package WoodmartChild
 * 
 * USAGE EXAMPLES:
 * 
 * Basic Usage (shows 4 related products):
 * [related_products_by_author]
 * 
 * Custom number of products:
 * [related_products_by_author limit="6"]
 * [related_products_by_author limit="8"]
 * 
 * Custom columns:
 * [related_products_by_author columns="3"]
 * [related_products_by_author columns="5"]
 * 
 * Custom title:
 * [related_products_by_author title="More Books by This Author"]
 * 
 * Hide title:
 * [related_products_by_author show_title="false"]
 * 
 * Order by:
 * [related_products_by_author orderby="date"]
 * [related_products_by_author orderby="price"]
 * [related_products_by_author orderby="rand"]
 * [related_products_by_author orderby="sales"]
 * 
 * Exclude current product:
 * [related_products_by_author exclude_current="true"]
 * 
 * Combined parameters:
 * [related_products_by_author limit="6" columns="3" title="Author's Collection" orderby="sales"]
 * 
 * Features:
 * - Uses WooCommerce's native product loop
 * - Responsive grid layout
 * - Compatible with Woodmart's product grid styles
 * - Excludes current product by default
 * - Supports all WooCommerce product display features
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Related_Products_By_Author_Shortcode {
    
    private static $instance = null;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode( 'related_products_by_author', [ $this, 'render_shortcode' ] );
    }
    
    /**
     * Get products by the same author(s)
     */
    private function get_products_by_author( $product_id, $args = array() ) {
        // Get autor terms for current product
        $autor_terms = wp_get_post_terms( $product_id, 'autor', array(
            'fields' => 'ids'
        ) );
        
        if ( empty( $autor_terms ) || is_wp_error( $autor_terms ) ) {
            return new WP_Query(); // Return empty WP_Query instead of array
        }
        
        // Default query args
        $defaults = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 4,
            'orderby' => 'date',
            'order' => 'DESC',
            'post__not_in' => array( $product_id ),
            'tax_query' => array(
                array(
                    'taxonomy' => 'autor',
                    'field' => 'term_id',
                    'terms' => $autor_terms,
                    'operator' => 'IN'
                )
            )
        );
        
        // Merge with provided args
        $query_args = wp_parse_args( $args, $defaults );
        
        // Handle orderby special cases
        if ( $query_args['orderby'] === 'price' ) {
            $query_args['meta_key'] = '_price';
            $query_args['orderby'] = 'meta_value_num';
        } elseif ( $query_args['orderby'] === 'sales' ) {
            $query_args['meta_key'] = 'total_sales';
            $query_args['orderby'] = 'meta_value_num';
        }
        
        // Get products
        $products = new WP_Query( $query_args );
        
        return $products;
    }
    
    /**
     * Render the shortcode
     */
    public function render_shortcode( $atts ) {
        // Only show on single product pages by default
        $current_product_id = get_the_ID();
        
        // If not on a product page, check if product_id is provided
        if ( ! is_singular( 'product' ) && empty( $atts['product_id'] ) ) {
            return '';
        }
        
        // Parse attributes
        $atts = shortcode_atts( array(
            'limit' => 4,
            'columns' => 4,
            'orderby' => 'date',
            'order' => 'DESC',
            'title' => __( 'More from this Author', 'woodmart-child' ),
            'show_title' => 'true',
            'exclude_current' => 'true',
            'product_id' => $current_product_id,
            'class' => 'related-products-by-author'
        ), $atts, 'related_products_by_author' );
        
        // Validate columns
        $columns = absint( $atts['columns'] );
        $columns = max( 1, min( 6, $columns ) );
        
        // Build query args
        $query_args = array(
            'posts_per_page' => absint( $atts['limit'] ),
            'orderby' => $atts['orderby'],
            'order' => strtoupper( $atts['order'] )
        );
        
        // Handle current product exclusion
        if ( $atts['exclude_current'] !== 'false' ) {
            $query_args['post__not_in'] = array( $atts['product_id'] );
        }
        
        // Get products
        $products = $this->get_products_by_author( $atts['product_id'], $query_args );
        
        if ( ! $products->have_posts() ) {
            return '';
        }
        
        // Start output buffering
        ob_start();
        
        // Set up WooCommerce loop
        global $woocommerce_loop;
        $woocommerce_loop['columns'] = $columns;
        
        ?>
        <div class="<?php echo esc_attr( $atts['class'] ); ?>-wrapper">
            <?php if ( $atts['show_title'] === 'true' && ! empty( $atts['title'] ) ) : ?>
                <h2 class="<?php echo esc_attr( $atts['class'] ); ?>-title"><?php echo esc_html( $atts['title'] ); ?></h2>
            <?php endif; ?>
            
            <div class="woocommerce">
                <?php
                // Use Woodmart's products element if available
                if ( function_exists( 'woodmart_products_tabs_template' ) ) {
                    echo '<div class="products elements-grid woodmart-products-holder woodmart-spacing-20 pagination-pagination align-items-start row grid-columns-' . $columns . '">';
                } else {
                    woocommerce_product_loop_start();
                }
                
                while ( $products->have_posts() ) {
                    $products->the_post();
                    
                    // Use Woodmart's product template if available
                    if ( function_exists( 'woodmart_get_product_thumbnail' ) ) {
                        woodmart_get_product_thumbnail();
                        wc_get_template_part( 'content', 'product' );
                    } else {
                        wc_get_template_part( 'content', 'product' );
                    }
                }
                
                if ( function_exists( 'woodmart_products_tabs_template' ) ) {
                    echo '</div>';
                } else {
                    woocommerce_product_loop_end();
                }
                ?>
            </div>
        </div>
        
        <?php
        // Reset post data
        wp_reset_postdata();
        
        // Add inline styles
        $this->add_inline_styles( $atts['class'] );
        
        return ob_get_clean();
    }
    
    /**
     * Add inline CSS styles
     */
    private function add_inline_styles( $class ) {
        static $styles_added = false;
        
        if ( $styles_added ) {
            return;
        }
        
        $styles_added = true;
        
        ?>
        <style>
            .<?php echo esc_attr( $class ); ?>-wrapper {
                margin: 40px 0;
            }
            
            .<?php echo esc_attr( $class ); ?>-title {
                text-align: center;
                margin-bottom: 30px;
                font-size: 28px;
                font-weight: 600;
                color: var(--wd-title-color, #333);
                font-family: var(--wd-title-font);
                position: relative;
                padding-bottom: 15px;
            }
            
            .<?php echo esc_attr( $class ); ?>-title:after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 40px;
                height: 2px;
                background-color: var(--wd-primary-color, #83b735);
            }
            
            /* Ensure proper grid spacing */
            .<?php echo esc_attr( $class ); ?>-wrapper .products {
                margin: 0 -10px;
            }
            
            .<?php echo esc_attr( $class ); ?>-wrapper .product {
                padding: 0 10px;
                margin-bottom: 30px;
            }
            
            /* Override Woodmart specific styles if needed */
            .<?php echo esc_attr( $class ); ?>-wrapper .woodmart-products-holder .product-grid-item {
                margin-bottom: 30px;
            }
            
            /* Responsive adjustments */
            @media (max-width: 991px) {
                .<?php echo esc_attr( $class ); ?>-title {
                    font-size: 24px;
                    margin-bottom: 25px;
                }
                
                .<?php echo esc_attr( $class ); ?>-wrapper .products.columns-4 .product,
                .<?php echo esc_attr( $class ); ?>-wrapper .products.columns-5 .product,
                .<?php echo esc_attr( $class ); ?>-wrapper .products.columns-6 .product {
                    width: 50%;
                }
            }
            
            @media (max-width: 767px) {
                .<?php echo esc_attr( $class ); ?>-title {
                    font-size: 20px;
                    margin-bottom: 20px;
                }
                
                .<?php echo esc_attr( $class ); ?>-wrapper {
                    margin: 30px 0;
                }
            }
            
            @media (max-width: 575px) {
                .<?php echo esc_attr( $class ); ?>-wrapper .products.columns-3 .product,
                .<?php echo esc_attr( $class ); ?>-wrapper .products.columns-4 .product,
                .<?php echo esc_attr( $class ); ?>-wrapper .products.columns-5 .product,
                .<?php echo esc_attr( $class ); ?>-wrapper .products.columns-6 .product {
                    width: 100%;
                }
            }
        </style>
        <?php
    }
}

// Initialize
Related_Products_By_Author_Shortcode::get_instance();

/**
 * Helper function
 */
function get_related_products_by_author( $atts = array() ) {
    return Related_Products_By_Author_Shortcode::get_instance()->render_shortcode( $atts );
}