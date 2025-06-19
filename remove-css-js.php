<?php
/*
Plugin Name: Remove unwanted CSS & JS
Plugin URI: https://itsoulinfotech.com
description: Remove unwanted CSS & JS from website
Version: 1.0.0
Author: Mr. Sandip Mistry
Author URI: https://sandipmistry.com
*/


add_action( 'wp_enqueue_scripts', 'dequeue_woocommerce_styles_scripts', 99999 );

function dequeue_woocommerce_styles_scripts() {
    if ( function_exists( 'is_woocommerce' ) ) {
        if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) {
            # Styles
            wp_dequeue_style( 'woocommerce-general' );
            wp_dequeue_style( 'woocommerce-layout' );
            wp_dequeue_style( 'woocommerce-smallscreen' );
            wp_dequeue_style( 'woocommerce_frontend_styles' );
            wp_dequeue_style( 'woocommerce_fancybox_styles' );
            wp_dequeue_style( 'woocommerce_chosen_styles' );
            wp_dequeue_style( 'woocommerce_prettyPhoto_css' );
            # Scripts
            wp_dequeue_script( 'wc_price_slider' );
            wp_dequeue_script( 'wc-single-product' );
            wp_dequeue_script( 'wc-add-to-cart' );
            wp_dequeue_script( 'wc-cart-fragments' );
            wp_dequeue_script( 'wc-checkout' );
            wp_dequeue_script( 'wc-add-to-cart-variation' );
            wp_dequeue_script( 'wc-single-product' );
            wp_dequeue_script( 'wc-cart' );
            wp_dequeue_script( 'wc-chosen' );
            wp_dequeue_script( 'woocommerce' );
            wp_dequeue_script( 'prettyPhoto' );
            wp_dequeue_script( 'prettyPhoto-init' );
            wp_dequeue_script( 'jquery-blockui' );
            wp_dequeue_script( 'jquery-placeholder' );
            wp_dequeue_script( 'fancybox' );
            wp_dequeue_script( 'jqueryui' );
        }
    }

    // Other CSS and Js

    //wp_dequeue_script( 'jquery' );

}

// Remove comment-reply.min.js from footer
function crunchify_clean_header_hook(){
    if ( is_front_page() ) :
        //wp_deregister_script( 'comment-reply' );
        wp_deregister_script('jquery-ui-button');
        wp_deregister_script('jquery-ui-slider');
        wp_deregister_script('jquery-ui-autocomplete');
        wp_deregister_script('pb-table');
        wp_deregister_script('jquery-bbq');
        wp_deregister_script('jquery-qtip');
        wp_deregister_script('jquery-themeOption');
        wp_deregister_script('jquery-themeOptionElement');
        wp_deregister_script('media-editor');
        wp_deregister_script('media-audiovideo');

    endif;        

}
add_action('init','crunchify_clean_header_hook');




// Remove jQuery Migrate Script from header and Load jQuery from Google API
function crunchify_remove_jquery_migrate_load_google_hosted_jquery() {
    if (!is_admin()) {
        wp_deregister_script('jquery');
        wp_register_script('jquery', 'https://code.jquery.com/jquery-3.5.1.min.js', false, null);
        wp_enqueue_script('jquery');
    }
}
//add_action('init', 'crunchify_remove_jquery_migrate_load_google_hosted_jquery');


function crunchify_print_scripts_styles() {
    // Print all loaded Scripts
    global $wp_scripts;
    foreach( $wp_scripts->queue as $script ) :
        if(current_user_can('administrator') )
        echo $script . '  **  ';
    endforeach;
 
    // Print all loaded Styles (CSS)
    global $wp_styles;
    foreach( $wp_styles->queue as $style ) :
        if(current_user_can('administrator') )
        echo $style . '  ||  ';
    endforeach;
}
 
//add_action( 'wp_print_scripts', 'crunchify_print_scripts_styles' );

add_action( 'wp_enqueue_scripts', 'remove_unused_stylesheet', 20 );

function remove_unused_stylesheet() {

    if ( is_front_page() ) :

        //styles
        wp_deregister_style('wc-blocks-vendors-style');
        wp_deregister_style('photoswipe-default-skin');
        
        wp_deregister_style('wp-block-library');
        wp_deregister_style('hfe-widgets-style');
        wp_deregister_style('dashicons');
        wp_deregister_style('generate-blog');
        wp_deregister_style('classic-theme-styles');
        wp_deregister_style('litespeed-cache');

        // script
        wp_deregister_script( 'photoswipe' );

        if (!wp_is_mobile() ) :
            wp_deregister_style('woocommerce-smallscreen');
            wp_deregister_style('generate-woocommerce-mobile');    
        endif;    
    else:
    endif;    
}
function add_alt_to_images_if_missing( $attr, $attachment = null ) {
    if($attr['alt']==''){
        $attr['alt']=trim( strip_tags( $attachment->post_title ) );
    }
    return $attr;
}
add_filter( 'wp_get_attachment_image_attributes','add_alt_to_images_if_missing', 10, 2 );
