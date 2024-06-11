<?php
   /*
   Plugin Name: Mini booking form for listings
   Plugin URI: 
   description: Displays subset of listing info along with booking button.
   Version: 0.2
   Author: Mark Jones
   Author URI: 
   */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Used by the template loader
define( 'MRJ_MINI_BOOKING_FORM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

$thisplugin = plugin_dir_path( __FILE__ );

//wp_register_style('ticket', plugin_dir_url( __FILE__ ) . '/public/css/ticket.css');
wp_register_style( 'my-custom-style', plugin_dir_url( __FILE__ ) . 'assets/css/mrj-mini-style.css' );
wp_enqueue_style( 'my-custom-style' );

// Include and init classes
include( 'includes/mrj-mini-booking-form-class-template-loader.php' );

// Add a shortcode for use on the page that will be used to display the mini listing and booking form
add_shortcode( 'show_mini_listing_booking', 'show_mini_listing_booking' );

function show_mini_listing_booking(){
    $mrj_mini_booking_form_template_loader = new Mrj_Mini_Booking_Form_Template_Loader;

    // The links that come to this booking form will have id= in the url. This gets the ID and gets
    // the post object
    if(isset($_GET['id'])){
        $url_id_value = $_GET['id'];
        $thepost = get_post($url_id_value);
    } else {
        return;
    }

    ob_start();
    // Listeo uses a class to load templates, we use it with a different class name with the
    // location set to this plugin's templates
    $mrj_mini_booking_form_template_loader = new Mrj_Mini_Booking_Form_Template_Loader;
    $mrj_mini_booking_form_template_loader->set_template_data(
    array( 
        'post_id' => $thepost->ID  // We send just the ID into the template
    ) )->get_template_part( '/single-listing-mini-booking' ); 

    $output = ob_get_clean();
    echo $output;
}

?>
