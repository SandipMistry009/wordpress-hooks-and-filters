<?php 
@ini_set( 'upload_max_size' , '64M' );
@ini_set( 'post_max_size', '64M');
@ini_set( 'max_execution_time', '1000' );

// Remove Main menu for shop_manager
function remove_menus(){

    global $current_user;
    $user_roles = $current_user->roles;

    if($user_roles[0] == 'shop_manager'){

       remove_submenu_page( 'index.php', 'update-core.php' ); // WP updated
      //remove_menu_page( 'edit.php' );                   //Posts
      remove_menu_page( 'themes.php' );                 //Appearance
      //remove_menu_page( 'edit-comments.php' );          //Comments
      //remove_menu_page( 'upload.php' );                 //Media
      //remove_menu_page( 'edit.php?post_type=page' );    //Pages
      remove_menu_page( 'plugins.php' );                //Plugins
      //remove_menu_page( 'users.php' );                //Users
      remove_menu_page( 'tools.php' );                  //Tools
      //remove_menu_page( 'options-general.php' );        //Settings
      remove_menu_page('edit.php?post_type=acf-field-group'); // Advance Custom Fields
      remove_menu_page('wpcf7'); // contact form 7
      remove_menu_page('elementor'); // Elementor
      remove_menu_page('wpseo_workouts'); //Yeost SEO
      remove_menu_page( 'edit.php?post_type=elementor_library' );    //Elementor
      remove_menu_page('edit.php?post_type=featured_item'); // flstsome portfolio
      remove_menu_page('edit.php?post_type=blocks'); // elementor blocks
      //remove_menu_page('itsoul_settings');

    }

}
add_action( 'admin_menu', 'remove_menus',9999 );

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

// Eliminate render blocking javascript

function js_defer_attr( $tag ){
  // add defer to all  scripts tags
  return str_replace( ' src', ' defer="defer" src', $tag );
}
add_filter( 'script_loader_tag', 'js_defer_attr', 10 );

//preloading CSS on Wordpress site

function add_rel_preload($html, $handle, $href, $media) {
if (is_admin())
    return $html;

$html = <<<EOT
<link rel='preload' as='style' onload="this.onload=null;this.rel='stylesheet'" 
id='$handle' href='$href' type='text/css' media='all' />
EOT;

return $html;
}

add_filter( 'style_loader_tag', 'add_rel_preload', 10, 4 );

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
	
	    
	// generate featured images

      $featured_image_id = $response->data['featured_media'];

      $images = array('original','thumbnail','medium','large');

      foreach ($images as $image) {
        
        $featured_image = wp_get_attachment_image_src( $featured_image_id,$image); 
        $alt_text = get_post_meta($featured_image_id, '_wp_attachment_image_alt', true);

        if( $featured_image ) {
          $response->data['featured_image'][$image] = $featured_image[0];
          $response->data['alt_text'] = $alt_text;
        }
     
      }	    
	    
	    
     // remove unwanted json fields from REST API response  

      unset($response->data['date_gmt']);
      unset($response->data['guid']);
      unset($response->data['modified']);
      unset($response->data['modified_gmt']);
      unset($response->data['slug']);
      unset($response->data['status']);
      unset($response->data['post_type']);
      unset($response->data['type']);
      unset($response->data['link']);
      unset($response->data['template']); 
     
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

// WP REST API custom end points

function wp_api_v2_all_posts ($data) {

	@header( 'Access-Control-Allow-Origin: *' );

   	 global $wpdb;
     $arr = array();
	 $args=array();
	 $args=array('posts_per_page'=> -1,
	 	'post_type' => 'post',
	 	'orderby' => 'title',
	 	'order' => 'ASC',
	);

	 $all_posts=get_posts($args);		
	 

	 //wp_send_json_success($all_posts);

	 foreach ($all_posts as $value) {
	     $data = new stdClass;
	 	$data->id = $value->ID;
	 	$data->date = $value->post_date;
	 	$data->slug = $value->post_name;
	 	$data->title = $value->post_title;
	 	$data->content = $value->post_content;
	 	$data->categories = get_the_category( $value->ID)[0]->cat_ID;
        $arr[] = $data;
	 }
	 wp_send_json_success($arr);
}

add_action( 'rest_api_init', function () {

register_rest_route( 'wp/v2', '/all_posts', array(
        'methods' => 'GET',
        'callback' => 'wp_api_v2_all_posts',
    ) );

});

// Prepaid Order Discount

add_action( 'woocommerce_cart_calculate_fees','libaasqueen_add_discount', 20, 1 );

function libaasqueen_add_discount( $cart_object ) {

if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

// Mention the payment method e.g. cod, bacs, cheque or paypal
$payment_method = 'payubiz';

// The percentage to apply
//$percent = 2; // 2%

$cart_total = $cart_object->subtotal_ex_tax;

$chosen_payment_method = WC()->session->get('chosen_payment_method'); //Get the selected payment method

if( $payment_method == $chosen_payment_method ){

$label_text = __( "Extra ₹100 Discount on Prepaid/UPI" );

// Calculating percentage
//$discount = number_format(($cart_total / 100) * $percent, 2);
$discount = number_format(100, 2);

// Adding the discount
$cart_object->add_fee( $label_text, -$discount, false );
}
}

// Products search by SKU

function search_by_sku( $search, &$query_vars ) {
    global $wpdb;
    if(isset($query_vars->query['s']) && !empty($query_vars->query['s'])){
        $args = array(
            'posts_per_page'  => -1,
            'post_type'       => 'product',
            'meta_query' => array(
                array(
                    'key' => '_sku',
                    'value' => $query_vars->query['s'],
                    'compare' => 'LIKE'
                )
            )
        );
        $posts = get_posts($args);
        if(empty($posts)) return $search;
        $get_post_ids = array();
        foreach($posts as $post){
            $get_post_ids[] = $post->ID;
        }
        if(sizeof( $get_post_ids ) > 0 ) {
                $search = str_replace( 'AND (((', "AND ((({$wpdb->posts}.ID IN (" . implode( ',', $get_post_ids ) . ")) OR (", $search);
        }
    }
    return $search;
    
}
    add_filter( 'posts_search', 'search_by_sku', 999, 2 );

add_filter( 'woocommerce_gateway_title', 'rudr_change_payment_gateway_title', 25, 2 );

function rudr_change_payment_gateway_title( $title, $gateway_id ){
    

    if( 'payubiz' === $gateway_id ) {
        $title = 'Online Payment';
    }

    return $title;
}

// hide admin notices
function hide_update_noticee_to_all_but_admin_users()
{
    if (is_super_admin()) {
        remove_all_actions( 'admin_notices' );
    }
}
add_action( 'admin_head', 'hide_update_noticee_to_all_but_admin_users', 1 );
