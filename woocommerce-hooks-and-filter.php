<?php
if ( class_exists( 'WooCommerce' ) ) {
    add_action( 'wp_head', 'insert_html_in_header');    
} else {
  return false;
}

function insert_html_in_header() {
    
    if ( is_single() ) { global $product;  ?>

        <div style="display:none;" itemscope itemtype="http://schema.org/Product">
            <meta itemprop="brand" content="brandname">
            <meta itemprop="name" content="<?php echo $product->get_formatted_name(); ?>">
            <a itemprop="url" href="<?php echo get_the_permalink(); ?>"></a>
            <img itemprop="image" src="<?php echo wp_get_attachment_url( $product->get_image_id() ); ?>" alt="<?php echo $product->get_formatted_name(); ?>" />
            <span itemprop="description"><?php echo $product->get_short_description(); ?></span>
            <meta itemprop="productID" content="<?php echo get_the_ID(); ?>">
            <meta itemprop="category" content="166" />
            <span itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                <link itemprop="availability" href="http://schema.org/InStock" />
                <meta itemprop="itemCondition" itemtype="http://schema.org/OfferItemCondition" content="http://schema.org/NewCondition" />
                <div class="product_price" itemprop="price"><?php echo number_format($product->get_price(),2); ?></div>
                <meta itemprop="priceCurrency" content="INR">
            </span>
        </div>
    <?php }

}

// Remove checkout fields 

add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );
 
function custom_override_checkout_fields( $fields ) {
	unset($fields['billing']['billing_company']);
	return $fields;
}

// Display the product thumbnail in order view pages

add_filter( 'woocommerce_order_item_name', 'display_product_image_in_order_item', 20, 3 );
function display_product_image_in_order_item( $item_name, $item, $is_visible ) {
    // Targeting view order pages only
    if(is_wc_endpoint_url( 'view-order' ) ) {
        $product   = $item->get_product(); // Get the WC_Product object (from order item)
        $thumbnail = $product->get_image(array( 36, 36)); // Get the product thumbnail (from product object)
        if( $product->get_image_id() > 0 )
            $item_name = '<div class="item-thumbnail">' . $thumbnail . '</div>' . $item_name;
    }
    return $item_name;
}

add_filter('woocommerce_currency_symbol',  'my_custom_currency_symbol', 10, 2) ;

function my_custom_currency_symbol( $currency_symbol, $currency ) {

  //print_r($currency);

  switch( $currency ) {
    case 'INR': $currency_symbol = 'Rs.'; 
    break;
  }
  return $currency_symbol;
}

// Product thumbnail in checkout
add_filter( 'woocommerce_cart_item_name', 'product_thumbnail_in_checkout', 20, 3 );
function product_thumbnail_in_checkout( $product_name, $cart_item, $cart_item_key ){
    if ( is_checkout() ) {

        $thumbnail   = $cart_item['data']->get_image(array( 60, 81));
        $image_html  = '<div class="product-item-thumbnail">'.$thumbnail.'</div> ';

        $product_name = $image_html . $product_name;
    }
    return $product_name;
}

// Cart item qquantity in checkout
add_filter( 'woocommerce_checkout_cart_item_quantity', 'filter_checkout_cart_item_quantity', 20, 3 );
function filter_checkout_cart_item_quantity( $quantity_html, $cart_item, $cart_item_key ){
    return ' <strong class="product-quantity">' . sprintf( '&times; %s', $cart_item['quantity'] ) . '</strong><br clear="all">';
}

// Product attribute in cart and checkout
add_filter( 'woocommerce_get_item_data', 'product_descrition_to_cart_items', 20, 2 );
function product_descrition_to_cart_items( $cart_item_data, $cart_item ){
    $product_id = $cart_item['product_id'];
    $product = wc_get_product($product_id);
    $taxonomy = 'pa_delivery';
    $value = $product->get_attribute($taxonomy);
    if ($product->get_attribute($taxonomy)) {
        $cart_item_data[] = array(
            'name' => get_taxonomy($taxonomy)->labels->singular_name,
            'value' => $product->get_attribute($taxonomy),
        );
    }
    return $cart_item_data;
}

// Product title shorter
add_filter( 'the_title', 'shorten_woo_product_title', 10, 2 );
function shorten_woo_product_title( $title, $id ) {
    if ( get_post_type( $id ) === 'product' && !is_single() && !is_admin() ) {
        return substr( $title, 0, 40 ); // change last number to the number of characters you want
    } else {
        return $title;
    }
}

//Remove product-category in URL

add_filter( 'term_link', 'devvn_product_cat_permalink', 10, 3 );
function devvn_product_cat_permalink( $url, $term, $taxonomy ){
    switch ($taxonomy):
        case 'product_cat':
            $taxonomy_slug = 'product-category'; //Change product-category to your product category slug
            if(strpos($url, $taxonomy_slug) === FALSE) break;
            $url = str_replace('/' . $taxonomy_slug, '', $url);
            break;
    endswitch;
    return $url;
}

// Add our custom product cat rewrite rules
function devvn_product_category_rewrite_rules($flash = false) {
    $terms = get_terms( array(
        'taxonomy' => 'product_cat',
        'post_type' => 'product',
        'hide_empty' => false,
    ));
    if($terms && !is_wp_error($terms)){
        $siteurl = esc_url(home_url('/'));
        foreach ($terms as $term){
            $term_slug = $term->slug;
            $baseterm = str_replace($siteurl,'',get_term_link($term->term_id,'product_cat'));
            add_rewrite_rule($baseterm.'?$','index.php?product_cat='.$term_slug,'top');
            add_rewrite_rule($baseterm.'page/([0-9]{1,})/?$', 'index.php?product_cat='.$term_slug.'&paged=$matches[1]','top');
            add_rewrite_rule($baseterm.'(?:feed/)?(feed|rdf|rss|rss2|atom)/?$', 'index.php?product_cat='.$term_slug.'&feed=$matches[1]','top');
        }
    }
    if ($flash == true)
        flush_rewrite_rules(false);
}
add_action('init', 'devvn_product_category_rewrite_rules');

// remove /product/ from URL

function remove_product_slug( $post_link, $post, $leavename ) {
    if ( 'product' != $post->post_type || 'publish' != $post->post_status ) {
        return $post_link;
    }
    $post_link = str_replace( '/product/', '/', $post_link );
    return $post_link;
}
add_filter( 'post_type_link', 'remove_product_slug', 10, 3 );

function change_slug_struct( $query ) {

    if ( ! $query->is_main_query() || 2 != count( $query->query ) || ! isset( $query->query['page'] ) ) {
        return;
    }

    if ( ! empty( $query->query['name'] ) ) {
        $query->set( 'post_type', array( 'post', 'product', 'page' ) );
    } elseif ( ! empty( $query->query['pagename'] ) && false === strpos( $query->query['pagename'], '/' ) ) {
        $query->set( 'post_type', array( 'post', 'product', 'page' ) );

        // We also need to set the name query var since redirect_guess_404_permalink() relies on it.
        $query->set( 'name', $query->query['pagename'] );
    }
}
add_action( 'pre_get_posts', 'change_slug_struct', 99 );

//  Remove /product_category/ from url and add .(dot) in permalink structure

add_filter('request', function( $vars ) {
    global $wpdb;
    if( ! empty( $vars['pagename'] ) || ! empty( $vars['category_name'] ) || ! empty( $vars['name'] ) || ! empty( $vars['attachment'] ) ) {
        $slug = ! empty( $vars['pagename'] ) ? $vars['pagename'] : ( ! empty( $vars['name'] ) ? $vars['name'] : ( !empty( $vars['category_name'] ) ? $vars['category_name'] : $vars['attachment'] ) );
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT t.term_id FROM $wpdb->terms t LEFT JOIN $wpdb->term_taxonomy tt ON tt.term_id = t.term_id WHERE tt.taxonomy = 'product_cat' AND t.slug = %s" ,array( $slug )));
        if( $exists ){
            $old_vars = $vars;
            $vars = array('product_cat' => $slug );
            if ( !empty( $old_vars['paged'] ) || !empty( $old_vars['page'] ) )
                $vars['paged'] = ! empty( $old_vars['paged'] ) ? $old_vars['paged'] : $old_vars['page'];
            if ( !empty( $old_vars['orderby'] ) )
                    $vars['orderby'] = $old_vars['orderby'];
                if ( !empty( $old_vars['order'] ) )
                    $vars['order'] = $old_vars['order'];    
        }
    }
    return $vars;
});

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
