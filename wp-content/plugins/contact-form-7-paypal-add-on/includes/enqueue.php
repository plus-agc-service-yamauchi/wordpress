<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly



// admin enqueue
function cf7pp_admin_enqueue() {

	// admin css
	wp_register_style('cf7pp-admin-css',plugins_url('../assets/css/admin.css',__FILE__),false,false);
	wp_enqueue_style('cf7pp-admin-css');

	// admin js
	wp_enqueue_script('cf7pp-admin',plugins_url('../assets/js/admin.js',__FILE__),array('jquery'),false);

}
add_action('admin_enqueue_scripts','cf7pp_admin_enqueue');





// public enqueue
function cf7pp_public_enqueue() {

	// path
	$site_url = get_home_url();
	$path = $site_url.'/?cf7pp_redirect=';

	// stripe public key
	$options = get_option('cf7pp_options');
	
	
	// set defaults in case uplugin has been updated without savings the settings page
	if (!isset($options['failed'])) {
		$options['failed'] = 		'Payment Failed';
		$options['pay'] = 			'Pay';
		$options['processing'] = 	'Processing Payment';
		$options['mode_stripe'] = 	'live';
		$options['pub_key_live'] = 	'';
	}
	

	if ($options['mode_stripe'] == "1") {
		$stripe_key = $options['pub_key_test'];
	} else {
		$stripe_key = $options['pub_key_live'];
	}

	// redirect method js
	wp_enqueue_script('cf7pp-redirect_method',plugins_url('../assets/js/redirect_method.js',__FILE__),array('jquery'),null);
	wp_localize_script('cf7pp-redirect_method', 'ajax_object_cf7pp',
		array (
			'ajax_url' 			=> admin_url('admin-ajax.php'),
			'forms' 			=> cf7pp_forms_enabled(),
			'path'				=> $path,
			'stripe_key' 		=> $stripe_key,
			'failed' 			=> $options['failed'],
			'pay' 				=> $options['pay'],
			'processing' 		=> $options['processing'],
			'method'			=> $options['redirect'],
		)
	);


	// stripe elements js - only load if api keys are entered
	if ((!empty($options['pub_key_live']) && !empty($options['sec_key_live']) && $options['mode_stripe'] == '2') || (!empty($options['pub_key_test']) && !empty($options['sec_key_test']) && $options['mode_stripe'] == '1')) {
		
		wp_enqueue_script('cf7pp-stripe-checkout','https://js.stripe.com/v3/');
		
		// public css
		wp_register_style('cf7pp-stripe-css',plugins_url('../assets/css/stripe.css',__FILE__),null,null);
		wp_enqueue_style('cf7pp-stripe-css');
	}


}
add_action('wp_enqueue_scripts','cf7pp_public_enqueue',10);
