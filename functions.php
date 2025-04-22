<?php
add_action( 'wp_enqueue_scripts', 'enqueue_divi_styles' );
function enqueue_divi_styles() {
    wp_enqueue_style( 'divi-style', get_template_directory_uri().'/style.css' );
}

/*add SVG Support*/
function wpcontent_svg_mime_type( $mimes = array() ) {
  $mimes['svg']  = 'image/svg+xml';
  $mimes['svgz'] = 'image/svg+xml';
  return $mimes;
}
add_filter( 'upload_mimes', 'wpcontent_svg_mime_type' ); 

/*add tags to blog filter*/
function custom_register_divi_blog_module() {
    if ( class_exists('ET_Builder_Module') ) {
        // Verify this file path is correct
        include_once get_stylesheet_directory() . '/includes/builder/module/BlogTagFilter.php';
        
        if (class_exists('ET_Builder_Module_Blog_Tag_Filter')) {
            $custom_blog = new ET_Builder_Module_Blog_Tag_Filter();
            remove_shortcode('et_pb_blog'); // Uncomment to replace the default blog module
            add_shortcode('et_pb_blog_tag_filter', [$custom_blog, '_shortcode_callback']);
        } else {
            error_log('ET_Builder_Module_Blog_Tag_Filter class not found.');
        }
    }
}

/* add projects to search */
add_action( 'pre_get_posts', function() {
  unset( $_GET['et_pb_searchform_submit'] );
}, 1 );
add_filter( 'relevanssi_pre_excerpt_content', 'rlv_shortcode_attribute', 8 );
add_filter( 'relevanssi_post_content', 'rlv_shortcode_attribute', 8 );

add_filter( 'relevanssi_post_content', 'rlv_remove_menu', 8 );
add_filter( 'relevanssi_pre_excerpt_content', 'rlv_remove_menu', 8 );
function rlv_remove_menu( $content ) {
    $content = preg_replace( '~\[et_pb_text admin_label="Main Menu.*?\[/et_pb_text\]~ims', '', $content );
	$content = preg_replace( '~\[et_pb_text admin_label="Category Navigation.*?\[/et_pb_text\]~ims', '', $content );
    $content = preg_replace( '~\[et_pb_text admin_label="Footer Menu.*?\[/et_pb_text\]~ims', '', $content );
    return $content;
}

function rlv_shortcode_attribute( $content ) {
	return preg_replace( '/\[et_pb_blurb.*?title="(.*?)".*?\]/im', '\1 ', $content );
}


/* Add Excerpts to Projects */
if (!function_exists('pac_misc_filter_portfolio_output')):
    function pac_misc_filter_portfolio_output($output, $render_slug, $module)
    {
        // Return If Frontend Builder
        if (function_exists('et_fb_is_enabled') && et_fb_is_enabled()) {
            return $output;
        }
        // Return If Backend Builder
        if (function_exists('et_builder_bfb_enabled') && et_builder_bfb_enabled()) {
            return $output;
        }
        // Return If Admin/Ajax and Output Array/Empty
        if (is_admin() || wp_doing_ajax() || is_array($output)) {
            return $output;
        }
        // Return If Not Slug Match
        if ('et_pb_portfolio' !== $render_slug && 'et_pb_filterable_portfolio' !== $render_slug) {
            return $output;
        }
        // Return If Empty
        if (empty($output)) {
            return $output;
        }
        // Show/Hide Excerpts
        $show_excerpts = true;
        // Allow Formatted Excerpt
        $show_formatted_excerpt = true;
        // Set Excerpts Words Limit
        $excerpt_limit = 10;
        // Set Excerpts Trim
        $excerpt_more = '...';
        // Show/Hide Readmore Button
        $show_readmore_button = true;
        // Set Readmore Button Text
        $readmore_button_text = 'View Tool';
        $dom = new DOMDocument('1.0', 'UTF-8');
        if (function_exists('mb_convert_encoding')) {
            $dom->loadHTML(mb_convert_encoding($output, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $dom->encoding = 'utf-8';
        } else {
            $dom->loadHTML('<?xml version="1.0" encoding="UTF-8"?>'."\n".$output, LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        }
        $dom_xpath = new DOMXPath($dom);
        $nodes = $dom_xpath->query('//div[contains(@id,"post-")]');
        if (isset($nodes->length) && 0 !== $nodes->length) {
            foreach ($nodes as $node) {
                $node_id = $node->getAttribute('id');
                $parent_ele = $dom->getElementById($node_id);
                $poject_id = str_replace('post-', '', $node_id);
                $excerpt = get_the_excerpt($poject_id);
                $link = get_the_permalink($poject_id);
                // Show Excerpt
                if (!empty($excerpt) && $show_excerpts) {
                    if ($show_formatted_excerpt) {
                        $excerpt_ele = $dom->createElement('div');
                        $excerpt_ele->setAttribute('class', 'et_pb_portfolio_excerpt');
                        $excerpt_ele->appendChild($dom->createCDATASection($excerpt));
                        $parent_ele->appendChild($excerpt_ele);
                    } else {
                        $excerpt = wp_trim_words(wp_strip_all_tags($excerpt), $excerpt_limit, $excerpt_more);
                        $excerpt_ele = $dom->createElement('p', $excerpt);
                        $excerpt_ele->setAttribute('class', 'et_pb_portfolio_excerpt');
                        $parent_ele->appendChild($excerpt_ele);
                    }
                }
                // Show Button
                if ($show_readmore_button) {
                    $btn_ele = $dom->createElement('a', $readmore_button_text);
                    $btn_ele->setAttribute('href', $link);
                    $btn_ele->setAttribute('class', 'et_pb_button');
                    $parent_ele->appendChild($btn_ele);
                }
            }
        }
        return $dom->saveHTML();
    }
    add_filter('et_module_shortcode_output', 'pac_misc_filter_portfolio_output', 10, 3);
endif;
?>
