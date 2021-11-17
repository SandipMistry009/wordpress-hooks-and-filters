<?php 

@ini_set( 'upload_max_size' , '64M' );
@ini_set( 'post_max_size', '64M');
@ini_set( 'max_execution_time', '1000' );

// changing default wordpres email settings
 
add_filter('wp_mail_from', 'new_mail_from');
add_filter('wp_mail_from_name', 'new_mail_from_name');
 
function new_mail_from($old) {
 return 'info@example.com';
}
function new_mail_from_name($old) {
 return 'Company Name';
}

// remove core updates

function remove_core_updates(){
    global $wp_version;
    return(object) array('last_checked'=> time(),'version_checked'=> $wp_version,);
}
add_filter('pre_site_transient_update_core','remove_core_updates');
add_filter('pre_site_transient_update_plugins','remove_core_updates');
add_filter('pre_site_transient_update_themes','remove_core_updates');

// change text

function my_admin_change_text( $translated_text ) {

  // echo "<pre>";
  // print_r($translated_text);

  switch ($translated_text) {
    case 'WooCommerce':
      $translated_text = 'My Store';
      break;  
    default:
      # code...
      break;
  }

  return $translated_text;
}
add_filter( 'gettext', 'my_admin_change_text', 20 );

// Disable Edit last and Edit Lock

function my_remove_post_locked() {
    $current_post_type = get_current_screen()->post_type;   

    // Disable locking for page, post and some custom post type
    $post_types_arr = array(
        'page',
        'post',
        'custom_post_type'
    );

    if(in_array($current_post_type, $post_types_arr)) {
        add_filter( 'show_post_locked_dialog', '__return_false' );
        add_filter( 'wp_check_post_lock_window', '__return_false' );
        wp_deregister_script('heartbeat');
    }
}

add_action('load-edit.php', 'my_remove_post_locked');
add_action('load-post.php', 'my_remove_post_locked');

// access control allow origin

function my_customize_rest_cors() {
  remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
  add_filter( 'rest_pre_serve_request', function( $value ) {
    header( 'Access-Control-Allow-Origin: *' );
    header( 'Access-Control-Allow-Methods: GET' );
    header( 'Access-Control-Allow-Credentials: true' );
    header( 'Access-Control-Expose-Headers: Link', false );

    return $value;
  });
}
add_action( 'rest_api_init', 'my_customize_rest_cors', 15 );

// disable authentation by wp-rest api

add_filter( 'rest_authentication_errors', function(){
    wp_set_current_user( 1 ); // replace with the ID of a WP user with the authorization you want
}, 101 );

// for each loop for repeated task

global $wpdb;
$post_types = $wpdb->get_results( "SELECT post_type FROM {$wpdb->prefix}posts GROUP BY post_type ", OBJECT );
 
foreach ($post_types as $key => $value) {

    // Save post Meta

   add_action("rest_insert_".$value->post_type, function (\WP_Post $post, $request, $creating) {
    $metas = $request->get_param("meta");
    if (is_array($metas)) {
        foreach ($metas as $name => $value) {
            update_post_meta($post->ID, $name, $value);
        }
    }
    }, 10, 3);

    // remove links from rest api json

    add_filter( 'rest_prepare_'.$value->post_type, function ( $response ) {

      $response->remove_link( 'collection' );
      $response->remove_link( 'self' );
      $response->remove_link( 'about' );
      $response->remove_link( 'author' );
      $response->remove_link( 'replies' );
      $response->remove_link( 'version-history' );
      $response->remove_link( 'https://api.w.org/featuredmedia' );
      $response->remove_link( 'https://api.w.org/attachment' );
      $response->remove_link( 'https://api.w.org/term' );
      $response->remove_link( 'curies' );

    return $response;
    } );
 
     // Filter by ACF

    add_filter( 'rest_'.$value->post_type.'_query', function( $args, $request ) {
        $select_company   = $request->get_param( 'select_company' );

        if ( ! empty( $select_company ) ) {
            $args['meta_query'] = array(
                array(
                    'key'     => 'select_company',
                    'value'   => $select_company,
                    'compare' => '=',
                )
            );      
        }

        return $args;
    }, 10, 2 );

} //foreach
?>
