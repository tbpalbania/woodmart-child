<?php
/**
 * Autor On Focus Shortcode
 * 
 * Displays top 5 autor terms based on product sales or featured products
 * Designed for homepage and global usage
 * 
 * @package WoodmartChild
 * 
 * USAGE EXAMPLES:
 * 
 * Basic Usage (shows top 5 autors by sales):
 * [autor-on-focus]
 * 
 * Custom number of autors (up to 12):
 * [autor-on-focus limit="3"]
 * [autor-on-focus limit="8"]
 * [autor-on-focus limit="10"]
 * [autor-on-focus limit="12"]
 * 
 * Custom title:
 * [autor-on-focus title="Featured Authors"]
 * 
 * Hide title:
 * [autor-on-focus show_title="false"]
 * 
 * Custom wrapper class:
 * [autor-on-focus class="custom-autors-grid"]
 * 
 * Show product count:
 * [autor-on-focus show_count="true"]
 * 
 * Features:
 * - Ranks autors by total product sales
 * - Falls back to featured products if no sales
 * - Works on any page (homepage, archives, etc.)
 * - Responsive grid layout
 * - ACF thumbnail support with fallback
 * - Links to autor archive pages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Autor_On_Focus_Shortcode {
    
    private static $instance = null;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode( 'autor-on-focus', [ $this, 'render_shortcode' ] );
        add_action( 'wp_head', [ $this, 'add_styles' ] );
    }
    
    /**
     * Get autors ranked by product sales
     */
    private function get_autors_by_sales( $limit = 5 ) {
        global $wpdb;
        
        // Query to get autor terms with their total sales
        $query = "
            SELECT 
                t.term_id,
                t.name,
                t.slug,
                tt.description,
                COALESCE(SUM(sales.total_sales), 0) as total_sales
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
            LEFT JOIN (
                SELECT 
                    post_id,
                    MAX(CAST(meta_value AS UNSIGNED)) as total_sales
                FROM {$wpdb->postmeta}
                WHERE meta_key = 'total_sales'
                GROUP BY post_id
            ) sales ON p.ID = sales.post_id
            WHERE 
                tt.taxonomy = 'autor'
                AND p.post_type = 'product'
                AND p.post_status = 'publish'
            GROUP BY t.term_id
            HAVING total_sales > 0
            ORDER BY total_sales DESC
            LIMIT %d
        ";
        
        $results = $wpdb->get_results( $wpdb->prepare( $query, $limit ) );
        
        // If we have enough results with sales, return them
        if ( count( $results ) >= $limit ) {
            return $results;
        }
        
        // If not enough results with sales, get autors with featured products
        return $this->get_autors_with_featured_products( $limit );
    }
    
    /**
     * Get autors that have featured products
     */
    private function get_autors_with_featured_products( $limit = 5 ) {
        global $wpdb;
        
        // Query to get autor terms that have featured products
        $query = "
            SELECT DISTINCT
                t.term_id,
                t.name,
                t.slug,
                tt.description,
                COUNT(DISTINCT p.ID) as featured_count
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE 
                tt.taxonomy = 'autor'
                AND p.post_type = 'product'
                AND p.post_status = 'publish'
                AND pm.meta_key = '_featured'
                AND pm.meta_value = 'yes'
            GROUP BY t.term_id
            ORDER BY featured_count DESC, t.name ASC
            LIMIT %d
        ";
        
        $featured_results = $wpdb->get_results( $wpdb->prepare( $query, $limit ) );
        
        // If we still don't have enough, just get any autors with products
        if ( count( $featured_results ) < $limit ) {
            $query = "
                SELECT DISTINCT
                    t.term_id,
                    t.name,
                    t.slug,
                    tt.description,
                    COUNT(DISTINCT p.ID) as product_count
                FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
                INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
                WHERE 
                    tt.taxonomy = 'autor'
                    AND p.post_type = 'product'
                    AND p.post_status = 'publish'
                GROUP BY t.term_id
                ORDER BY product_count DESC, t.name ASC
                LIMIT %d
            ";
            
            return $wpdb->get_results( $wpdb->prepare( $query, $limit ) );
        }
        
        return $featured_results;
    }
    
    /**
     * Get product count for an autor
     */
    private function get_autor_product_count( $term_id ) {
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'autor',
                    'field' => 'term_id',
                    'terms' => $term_id,
                ),
            ),
        );
        
        $query = new WP_Query( $args );
        return $query->found_posts;
    }
    
    /**
     * Render the shortcode
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'limit' => 5,
            'title' => 'Authors on Focus',
            'show_title' => 'true',
            'class' => 'autor-on-focus',
            'show_count' => 'false'
        ], $atts, 'autor-on-focus' );
        
        // Validate and constrain limit between 1 and 12
        $limit = intval( $atts['limit'] );
        $limit = max( 1, min( 12, $limit ) );
        
        // Get top autors
        $autors = $this->get_autors_by_sales( $limit );
        
        if ( empty( $autors ) ) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr( $atts['class'] ); ?>-wrapper">
            <?php if ( $atts['show_title'] === 'true' && $atts['title'] ) : ?>
                <h2 class="autor-on-focus-title"><?php echo esc_html( $atts['title'] ); ?></h2>
            <?php endif; ?>
            
            <div class="<?php echo esc_attr( $atts['class'] ); ?>-grid">
                <?php foreach ( $autors as $autor ) : ?>
                    <?php
                    $term_link = get_term_link( (int) $autor->term_id, 'autor' );
                    $thumbnail_url = '';
                    $thumbnail_alt = $autor->name;
                    
                    // Get ACF thumbnail
                    if ( function_exists( 'get_field' ) ) {
                        $thumbnail = get_field( 'thumbnail', 'autor_' . $autor->term_id );
                        if ( $thumbnail ) {
                            if ( is_array( $thumbnail ) ) {
                                $thumbnail_url = $thumbnail['url'];
                                $thumbnail_alt = $thumbnail['alt'] ?: $autor->name;
                            } else {
                                $thumbnail_url = wp_get_attachment_url( $thumbnail );
                                $thumbnail_alt = get_post_meta( $thumbnail, '_wp_attachment_image_alt', true ) ?: $autor->name;
                            }
                        }
                    }
                    
                    // Get product count if needed
                    $product_count = 0;
                    if ( $atts['show_count'] === 'true' ) {
                        $product_count = $this->get_autor_product_count( $autor->term_id );
                    }
                    ?>
                    <div class="autor-focus-card">
                        <a href="<?php echo esc_url( $term_link ); ?>" class="autor-focus-link">
                            <div class="autor-focus-thumbnail" <?php if ( $thumbnail_url ) : ?>style="background-image: url('<?php echo esc_url( $thumbnail_url ); ?>');"<?php endif; ?>>
                                <?php if ( ! $thumbnail_url ) : ?>
                                    <span class="autor-focus-initials"><?php echo esc_html( strtoupper( substr( $autor->name, 0, 2 ) ) ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="autor-focus-content">
                                <h3 class="autor-focus-name"><?php echo esc_html( $autor->name ); ?></h3>
                                <?php if ( $atts['show_count'] === 'true' && $product_count > 0 ) : ?>
                                    <span class="autor-focus-count"><?php echo sprintf( _n( '%d Book', '%d Books', $product_count, 'woodmart-child' ), $product_count ); ?></span>
                                <?php endif; ?>
                                <?php if ( ! empty( $autor->description ) ) : ?>
                                    <p class="autor-focus-description"><?php echo esc_html( wp_trim_words( $autor->description, 15, '...' ) ); ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
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
            .autor-on-focus-wrapper {
                margin: 40px 0;
            }
            
            .autor-on-focus-title {
                text-align: center;
                margin-bottom: 40px;
                font-size: 32px;
                font-weight: 700;
                color: var(--wd-primary-color, #333);
                font-family: var(--wd-text-font);
            }
            
            .autor-on-focus-grid {
                display: flex;
                flex-direction: column;
                gap: 20px;
                max-width: 1200px;
                margin: 0 auto;
            }
            
            .autor-focus-card {
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 15px;
                background: var(--wd-main-bg-color, #fff);
                border-radius: var(--wd-brd-radius, 8px);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                border: 1px solid var(--wd-border-color, #e5e5e5);
            }
            
            .autor-focus-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                border-color: var(--wd-primary-color, #83b735);
            }
            
            .autor-focus-link {
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 15px;
                width: 100%;
            }
            
            .autor-focus-thumbnail {
                width: 80px;
                height: 80px;
                min-width: 80px;
                border-radius: 50%;
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                background-color: var(--wd-gray-300, #f5f5f5);
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                transition: box-shadow 0.3s ease;
                border: 2px solid var(--wd-main-bg-color, #fff);
            }
            
            .autor-focus-card:hover .autor-focus-thumbnail {
                box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            }
            
            .autor-focus-initials {
                font-size: 28px;
                font-weight: bold;
                color: var(--wd-primary-color, #83b735);
                text-transform: uppercase;
                font-family: var(--wd-title-font);
            }
            
            .autor-focus-content {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            
            .autor-focus-name {
                margin: 0;
                font-size: 18px;
                font-weight: 600;
                color: var(--wd-title-color, #333);
                transition: color 0.3s ease;
                font-family: var(--wd-title-font);
            }
            
            .autor-focus-card:hover .autor-focus-name {
                color: var(--wd-primary-color, #83b735);
            }
            
            .autor-focus-count {
                display: inline-block;
                font-size: 14px;
                color: var(--wd-text-color, #777);
                margin-bottom: 8px;
                font-weight: 500;
                font-family: var(--wd-text-font);
            }
            
            .autor-focus-description {
                margin: 8px 0 0;
                font-size: 14px;
                line-height: 1.5;
                color: var(--wd-text-color, #777);
                font-family: var(--wd-text-font);
            }
            
            /* Responsive Design - Two column grid on tablets */
            @media (max-width: 1024px) {
                .autor-on-focus-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 20px;
                }
            }
            
            /* Mobile design - keep as list */
            @media (max-width: 768px) {
                .autor-on-focus-grid {
                    display: flex;
                    flex-direction: column;
                    gap: 20px;
                }
                
                .autor-on-focus-title {
                    font-size: 28px;
                    margin-bottom: 30px;
                }
                
                .autor-focus-thumbnail {
                    width: 70px;
                    height: 70px;
                    min-width: 70px;
                }
                
                .autor-focus-initials {
                    font-size: 24px;
                }
                
                .autor-focus-name {
                    font-size: 16px;
                }
                
                .autor-focus-description {
                    font-size: 13px;
                }
                
                .autor-focus-card {
                    padding: 12px;
                }
            }
            
            @media (max-width: 480px) {
                .autor-on-focus-grid {
                    gap: 15px;
                }
                
                .autor-focus-thumbnail {
                    width: 60px;
                    height: 60px;
                    min-width: 60px;
                }
                
                .autor-focus-initials {
                    font-size: 20px;
                }
                
                .autor-focus-name {
                    font-size: 15px;
                }
                
                .autor-focus-count {
                    font-size: 12px;
                }
                
                .autor-focus-description {
                    font-size: 12px;
                }
                
                .autor-focus-card {
                    padding: 10px;
                    gap: 12px;
                }
                
                .autor-focus-link {
                    gap: 12px;
                }
            }
        </style>
        <?php
    }
}

// Initialize
Autor_On_Focus_Shortcode::get_instance();

/**
 * Helper function
 */
function get_autor_on_focus( $atts = [] ) {
    return Autor_On_Focus_Shortcode::get_instance()->render_shortcode( $atts );
}