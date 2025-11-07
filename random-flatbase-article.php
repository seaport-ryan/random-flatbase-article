<?php
/*
Plugin Name: Random Flatbase Article
Description: Redirects /random-article (or ?random_article=1) to a random Flatbase knowledge base article (post type "article").
Version: 1.0
Author: You
*/

//
// 1) Main redirect logic
//
function rfa_random_flatbase_article_redirect() {
    // Trigger on /random-article or ?random_article
    if ( get_query_var('random_article') || isset($_GET['random_article']) || strpos($_SERVER['REQUEST_URI'], '/random-article') !== false ) {

        // Get 1 random post of type 'article' (Flatbase KB entries)
        $random = get_posts(array(
            'post_type'      => 'article',
            'posts_per_page' => 1,
            'orderby'        => 'rand',
        ));

        if ( ! empty($random) ) {
            $url = get_permalink($random[0]->ID);
            wp_redirect($url);
            exit;
        } else {
            wp_die('No Flatbase articles found.');
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
// 3) Register the query var
//
function rfa_register_query_var($vars) {
    $vars[] = 'random_article';
    return $vars;
}
add_filter('query_vars', 'rfa_register_query_var');


// Shortcode: [surprise_article]
// Renders a "Surprise Me" button that links to /random-article
function rfa_surprise_article_shortcode($atts) {
    // Allow customizing the text via [surprise_article text="Something"]
    $atts = shortcode_atts(array(
        'text' => 'Surprise Me',
        'class' => '',
    ), $atts, 'surprise_article');

    // Use home_url() so it works on any domain / subdir
    $url = home_url('/random-article');

    // basic styling inline so it works anywhere
    $html  = '<a href="' . esc_url($url) . '" class="rfa-surprise-button ' . esc_attr($atts['class']) . '" style="display:inline-block; padding:0.6em 1.2em; background:#0073aa; color:#fff; text-decoration:none; border-radius:4px;">';
    $html .= esc_html($atts['text']);
    $html .= '</a>';

    return $html;
}
add_shortcode('surprise_article', 'rfa_surprise_article_shortcode');
