<?php
/**
 * Plugin Name: Hierarchical tags
 * Description: Add support hierarchical tags for posts. Output for tags for post: <code>[tags]</code>
 * Version:     1.0.1
 * Author:      Aleksey Tikhomirov
 * Author URI:  http://rwsite.ru
 *
 * Requires at least: 4.6
 * Tested up to: 6.3
 * Requires PHP: 8.0+
 */

defined( 'ABSPATH' ) or die( 'Nothing here!' );

if(class_exists('HierarchicalTags')){
    return;
}

class HierarchicalTags
{
    public function __construct()
    {
        $this->add_actions();
    }

    public function add_actions(){

        add_shortcode('tags', [$this, 'show']);

        add_action('init', [$this, 'register_taxonomy']);
        add_filter('get_the_tags', [__CLASS__, 'get_the_tags']);

        add_action('admin_init', [$this, 'wp_term_importer']);
    }


    /**
     * Fix error when request to simple tag. Use in feeds and some plugins and themes
     *
     * @return WP_Term[]|false|WP_Error
     */
    public static function get_the_tags($terms = null)
    {
        return get_the_terms(get_the_ID() ?? 0, 'post_tag');
    }

    /**
     * Register taxonomy. Hierarchical tags.
     */
    public function register_taxonomy()
    {
        register_taxonomy(
            'post_tag',
            'post',
            array(
                'labels'                => [
                    'name'                       => __( 'Tags', 'theme' ),
                    'singular_name'              => __( 'Tag', 'theme' ),
                    'search_items'               => __( 'Search tag', 'theme' ),
                    'popular_items'              => __( 'Popular tags' ),
                    'all_items'                  => __( 'All tags', 'theme' ),
                    'parent_item'                => null,
                    'parent_item_colon'          => null,
                    'edit_item'                  => __( 'Edit hierarchical tag', 'theme' ),
                    'update_item'                => __( 'Update', 'theme' ),
                    'add_new_item'               => __( 'Add new', 'theme' ),
                    'new_item_name'              => __( 'Name of tag', 'theme' ),
                    'separate_items_with_commas' => __( 'Separete with comma', 'theme' ),
                    'add_or_remove_items'        => __( 'Add or Remove tag', 'theme' ),
                    'menu_name'                  => __( 'Tags', 'theme' )
                ],
                'hierarchical'          => true,
                'query_var'             => 'tag',
                'rewrite'               => [
                    'hierarchical' => true,
                    'slug'         => get_option( 'tag_base' ) ? get_option( 'tag_base' ) : 'tag',
                    'with_front'   => ! get_option( 'tag_base' ) || 'tags',
                    'ep_mask'      => EP_TAGS,
                ],
                'public'                => true,
                'show_ui'               => true,
                'show_admin_column'     => true,
                'capabilities'          => [
                    'manage_terms' => 'manage_post_tags',
                    'edit_terms'   => 'edit_post_tags',
                    'delete_terms' => 'delete_post_tags',
                    'assign_terms' => 'assign_post_tags',
                ],
                'show_in_rest'          => true,
                'rest_base'             => 'tags',
                'rest_controller_class' => 'WP_REST_Terms_Controller',
            )
        );
    }


    /**
     * Show custom tags
     */
    public function show($atts)
    {

        $terms = get_the_terms( get_the_ID(), 'post_tag' );
        if (empty( $terms ) || is_wp_error( $terms )) {
            return;
        }

        $classes = 'post-tags clearfix ';
        $classes .= 'post-share-class';
        ?>
        <div class="<?php esc_attr_e($classes);?>">
            <span class="terms-label"><i class="fa fa-tags"></i></span>
            <?php
            foreach ($terms as $term) {
                $link = get_term_link( $term, 'post_tag' );
                if (is_wp_error( $link )) {
                    continue;
                }
                echo '<a href="'. esc_url( $link ) .'" rel="tag">' .$term->name. '</a>';
            }
            ?>
        </div>
        <?php
        unset( $terms );
    }

    public static function wp_term_importer(){

        if(!isset($_GET['convert_terms_taxonomy'])){
            return;
        }

        $terms = get_terms( ['taxonomy' => 'customtags','hide_empty' => false,] );
        echo '<pre>'; var_dump($terms); echo '</pre>';
        foreach ($terms as $term){
            $term->taxonomy = 'post_tag';
            self::update_term($term->term_id, $term->taxonomy);
        }
        $terms = get_terms( ['taxonomy' => 'customtags','hide_empty' => false,] );
        echo '<pre>'; var_dump($terms); echo '</pre>';
        wp_die();
    }

    protected static function update_term($term_id, $new_taxonomy)
    {
        global $wpdb;
        $update = $wpdb->update(
            $wpdb->prefix . 'term_taxonomy',
            ['taxonomy' => $new_taxonomy],
            ['term_taxonomy_id' => $term_id],
            ['%s'],
            ['%d']
        );
        return $update;
    }
}

new HierarchicalTags();