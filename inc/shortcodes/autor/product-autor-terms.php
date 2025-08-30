<?php
/**
 * Product Autor Terms Shortcode
 * 
 * Displays autor taxonomy terms assigned to the current product as styled cards
 * 
 * @package WoodmartChild
 * 
 * USAGE EXAMPLES:
 * 
 * Basic Usage (shows all authors):
 * [product_autor_terms]
 * 
 * Limit number of authors:
 * [product_autor_terms limit="2"]
 * [product_autor_terms limit="5"]
 * [product_autor_terms limit="1"]
 * 
 * Custom wrapper class:
 * [product_autor_terms class="custom-authors"]
 * 
 * Custom wrapper class with limit:
 * [product_autor_terms class="featured-authors" limit="3"]
 * 
 * Features:
 * - Displays author thumbnail, name, and description
 * - Responsive card layout with hover effects
 * - ACF thumbnail support with fallback
 * - Automatic ellipsis for long descriptions
 * - Links to author archive pages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Product_Autor_Terms_Shortcode {
    
    private static $instance = null;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode( 'product_autor_terms', [ $this, 'render_shortcode' ] );
        add_action( 'wp_head', [ $this, 'add_styles' ] );
    }
    
    /**
     * Render the shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_shortcode( $atts ) {
        // Only show on single product pages
        if ( ! is_singular( 'product' ) ) {
            return '';
        }
        
        $atts = shortcode_atts( [
            'limit' => 0, // 0 means no limit (get all)
            'class' => 'product-autor-terms'
        ], $atts, 'product_autor_terms' );
        
        $current_product_id = get_the_ID();
        
        // Prepare query args
        $query_args = [
            'orderby' => 'name',
            'order' => 'ASC'
        ];
        
        // Add limit only if specified and greater than 0
        if ( $atts['limit'] && intval( $atts['limit'] ) > 0 ) {
            $query_args['number'] = intval( $atts['limit'] );
        }
        
        // Get autor terms assigned to the current product
        $autor_terms = wp_get_post_terms( $current_product_id, 'autor', $query_args );
        
        if ( empty( $autor_terms ) || is_wp_error( $autor_terms ) ) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr( $atts['class'] ); ?>">
            <?php foreach ( $autor_terms as $term ) : ?>
                <?php
                $term_link = get_term_link( $term );
                $thumbnail_url = '';
                $thumbnail_alt = $term->name;
                
                // Get ACF thumbnail
                if ( function_exists( 'get_field' ) ) {
                    $thumbnail = get_field( 'thumbnail', 'autor_' . $term->term_id );
                    if ( $thumbnail ) {
                        if ( is_array( $thumbnail ) ) {
                            $thumbnail_url = $thumbnail['url'];
                            $thumbnail_alt = $thumbnail['alt'] ?: $term->name;
                        } else {
                            $thumbnail_url = wp_get_attachment_url( $thumbnail );
                            $thumbnail_alt = get_post_meta( $thumbnail, '_wp_attachment_image_alt', true ) ?: $term->name;
                        }
                    }
                }
                ?>
                <div class="autor-term-card">
                    <div class="autor-thumbnail" <?php if ( $thumbnail_url ) : ?>style="background-image: url('<?php echo esc_url( $thumbnail_url ); ?>');"<?php endif; ?>>
                        <?php if ( ! $thumbnail_url ) : ?>
                            <span class="autor-initials"><?php echo esc_html( strtoupper( substr( $term->name, 0, 1 ) ) ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="autor-content">
                        <a href="<?php echo esc_url( $term_link ); ?>" class="autor-name">
                            <h6><?php echo esc_html( $term->name ); ?></h6>
                        </a>
                        <?php if ( $term->description ) : ?>
                            <p class="autor-description"><?php echo esc_html( $term->description ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Add CSS styles to head
     */
    public function add_styles() {
        ?>
        <style>
            .product-autor-terms {
                display: flex;
                flex-direction: column;
                gap: 20px;
                margin: 20px 0;
            }
            
            .autor-term-card {
                display: flex;
                flex-direction: row;
                gap: 15px;
                align-items: center;
                padding: 15px;
                background: var(--wd-main-bg-color, #fff);
                border: 1px solid var(--wd-border-color, #e5e5e5);
                border-radius: var(--wd-brd-radius, 8px);
                transition: all 0.3s ease;
            }
            
            .autor-term-card:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                transform: translateY(-2px);
                border-color: var(--wd-primary-color, #83b735);
            }
            
            .autor-thumbnail {
                width: 60px;
                height: 60px;
                min-width: 60px;
                border-radius: 50%;
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                background-color: var(--wd-gray-300, #f5f5f5);
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                border: 2px solid var(--wd-main-bg-color, #fff);
            }
            
            .autor-initials {
                font-size: 24px;
                font-weight: bold;
                color: var(--wd-primary-color, #83b735);
                font-family: var(--wd-title-font);
            }
            
            .autor-content {
                display: flex;
                flex-direction: column;
                gap: 5px;
                flex: 1;
                min-width: 0; /* Allows text to wrap properly */
            }
            
            .autor-name {
                text-decoration: none;
                transition: color 0.3s ease;
            }
            
            .autor-name:hover {
                text-decoration: none;
            }
            
            .autor-name h6 {
                margin: 0;
                color: var(--wd-title-color, #333);
                font-size: 16px;
                font-weight: 600;
                line-height: 1.4;
                transition: color 0.3s ease;
                font-family: var(--wd-title-font);
            }
            
            .autor-name:hover h6 {
                color: var(--wd-primary-color, #83b735);
            }
            
            .autor-description {
                margin: 0;
                color: var(--wd-text-color, #777);
                font-size: 13px;
                line-height: 1.5;
                overflow: hidden;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                font-family: var(--wd-text-font);
            }
            
            /* Mobile responsiveness */
            @media (max-width: 768px) {
                .autor-thumbnail {
                    width: 50px;
                    height: 50px;
                    min-width: 50px;
                }
                
                .autor-term-card {
                    gap: 12px;
                    padding: 12px;
                }
                
                .autor-content {
                    gap: 4px;
                }
                
                .autor-name h6 {
                    font-size: 14px;
                }
                
                .autor-description {
                    font-size: 12px;
                }
                
                .autor-initials {
                    font-size: 20px;
                }
            }
            
            @media (max-width: 480px) {
                .autor-thumbnail {
                    width: 45px;
                    height: 45px;
                    min-width: 45px;
                }
                
                .autor-term-card {
                    gap: 10px;
                    padding: 10px;
                }
                
                .autor-initials {
                    font-size: 16px;
                }
            }
        </style>
        <?php
    }
}

// Initialize
Product_Autor_Terms_Shortcode::get_instance();

/**
 * Helper function
 */
function get_product_autor_terms( $atts = [] ) {
    return Product_Autor_Terms_Shortcode::get_instance()->render_shortcode( $atts );
}