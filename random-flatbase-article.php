<?php
/*
Plugin Name: Random Flatbase Article
Description: Redirects /random-article (or ?random_article=1) to a random Flatbase knowledge base article (post type "article"). Supports category filtering.
Version: 1.3
Author: You
*/

//
// 1) Main redirect logic
//
function rfa_random_flatbase_article_redirect() {
    // Should we run?
    $is_random = get_query_var('random_article')
                 || isset($_GET['random_article'])
                 || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/random-article') !== false);

    if ( ! $is_random ) {
        return;
    }

    // Optional category from URL: ?kb_cat=slug or ?category=slug
    $requested_cat = '';
    if ( get_query_var('kb_cat') ) {
        $requested_cat = get_query_var('kb_cat');
    } elseif ( isset($_GET['kb_cat']) ) {
        $requested_cat = sanitize_text_field($_GET['kb_cat']);
    } elseif ( isset($_GET['category']) ) {
        $requested_cat = sanitize_text_field($_GET['category']);
    }

    $args = array(
        'post_type'      => 'article',
        'posts_per_page' => 1,
        'orderby'        => 'rand',
    );

    // If a category was requested, try known taxonomies
    if ( ! empty($requested_cat) ) {
        $tax_query = array('relation' => 'OR');

        // Your URL shows /article-category/... so check that first
        if ( taxonomy_exists('article-category') ) {
            $tax_query[] = array(
                'taxonomy' => 'article-category',
                'field'    => 'slug',
                'terms'    => $requested_cat,
            );
        }

        // Common variant
        if ( taxonomy_exists('article_category') ) {
            $tax_query[] = array(
                'taxonomy' => 'article_category',
                'field'    => 'slug',
                'terms'    => $requested_cat,
            );
        }

        // Fallback to built-in categories
        if ( taxonomy_exists('category') ) {
            $tax_query[] = array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => $requested_cat,
            );
        }

        // Only add if we actually added something
        if ( count($tax_query) > 1 ) {
            $args['tax_query'] = $tax_query;
        }
    }

    $random_article = get_posts($args);

    if ( ! empty($random_article) ) {
        wp_redirect( get_permalink($random_article[0]->ID) );
        exit;
    } else {
        if ( ! empty($requested_cat) ) {
            wp_die('No articles found in category: ' . esc_html($requested_cat));
        } else {
            wp_die('No articles found.');
        }
    }
}
add_action('template_redirect', 'rfa_random_flatbase_article_redirect');


//
// 2) Make /random-article a real URL (rewrite)
//
function rfa_add_random_article_rewrite() {
    add_rewrite_rule('^random-article/?$', 'index.php?random_article=1', 'top');
}
add_action('init', 'rfa_add_random_article_rewrite');


//
// 3) Register query vars
//
function rfa_register_query_var($vars) {
    $vars[] = 'random_article';
    $vars[] = 'kb_cat';
    return $vars;
}
add_filter('query_vars', 'rfa_register_query_var');


//
// 4) Shortcode: [surprise_article text="..." category="brake"]
//
function rfa_surprise_article_shortcode($atts) {
    $atts = shortcode_atts(array(
        'text'     => 'Surprise Me',
        'class'    => '',
        'category' => '',
    ), $atts, 'surprise_article');

    $url = home_url('/random-article');

    if ( ! empty($atts['category']) ) {
        $url = add_query_arg('kb_cat', urlencode($atts['category']), $url);
    }

    $html  = '<a href="' . esc_url($url) . '" class="rfa-surprise-button ' . esc_attr($atts['class']) . '" style="display:inline-block; padding:0.6em 1.2em; background:#0073aa; color:#fff; text-decoration:none; border-radius:4px;">';
    $html .= esc_html($atts['text']);
    $html .= '</a>';

    return $html;
}
add_shortcode('surprise_article', 'rfa_surprise_article_shortcode');

