<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


// returns the form id of the forms that have paypal enabled - used for redirect method 1 and method 2
function cf7pp_forms_enabled() {

	// array that will contain which forms paypal is enabled on
	$enabled = array();
	
	$args = array(
		'posts_per_page'   => 999,
		'post_type'        => 'wpcf7_contact_form',
		'post_status'      => 'publish',
	);
	$posts_array = get_posts($args);
	
	
	// loop through them and find out which ones have paypal enabled
	foreach($posts_array as $post) {
		
		$post_id = $post->ID;
		
		// paypal
		$enable = get_post_meta( $post_id, "_cf7pp_enable", true);
		
		if ($enable == "1") {
			$enabled[] = $post_id.'|paypal';
		}
		
		// stripe
		$enable_stripe = get_post_meta( $post_id, "_cf7pp_enable_stripe", true);
		
		if ($enable_stripe == "1") {
			$enabled[] = $post_id.'|stripe';
		}
		
	}

	return json_encode($enabled);

}


// hook into contact form 7 - after send
add_action('template_redirect','cf7pp_redirect_method');
function cf7pp_redirect_method() {

	if (isset($_GET['cf7pp_redirect'])) {

		// get the id from the cf7pp_before_send_mail function theme redirect
		$post_id = $_GET['cf7pp_redirect'];

		cf7pp_redirect($post_id);
		exit;

	}
}


// hook into contact form 7 - before send
add_action('wpcf7_before_send_mail', 'cf7pp_before_send_mail');
function cf7pp_before_send_mail() {

	$wpcf7 = WPCF7_ContactForm::get_current();

	// need to save submission for later and the variables get lost in the cf7 javascript redirect
	$submission_orig = WPCF7_Submission::get_instance();

	if ($submission_orig) {
		// get form post id
		$posted_data = $submission_orig->get_posted_data();
		
		$options = 			get_option('cf7pp_options');
		
		
		$post_id = 			$wpcf7->id;
		
		
		$gateway = 			strtolower(get_post_meta($post_id, "_cf7pp_gateway", true));
		$amount_total = 	get_post_meta($post_id, "_cf7pp_price", true);
		
		$enable = 			get_post_meta( $post_id, "_cf7pp_enable", true);
		$enable_stripe = 	get_post_meta( $post_id, "_cf7pp_enable_stripe", true);
		
		$stripe_email = 	strtolower(get_post_meta($post_id, "_cf7pp_stripe_email", true));
		
		if (!empty($stripe_email)) {
			$stripe_email = 	$posted_data[$stripe_email];
		} else {
			$stripe_email = '';
		}
		
		
		$gateway_orig = $gateway;
		
		if ($enable == '1') {
			$gateway = 'paypal';
		}
		
		if ($enable_stripe == '1') {
			$gateway = 'stripe';			
		}
		
		if ($enable == '1' && $enable_stripe == '1') {
			$gateway = $posted_data[$gateway_orig][0];
		}		
		
		
		
		if (!isset($options['default_symbol'])) {
			$options['default_symbol'] 	= '$';
		}
		
		
		
		if (isset($options['mode_stripe'])) {
			if ($options['mode_stripe'] == "1") {
				$tags['stripe_state'] = "test";
			} else {
				$tags['stripe_state'] = "live";
			}
		} else {
			$tags['stripe_state'] = "live";
		}
		
		
		if (empty($options['session'])) {
				$session = '1';
			} else {
				$session = $options['session'];
			}
			
		if ($session == '1') {
			setcookie('cf7pp_gateway', 				$gateway, time()+3600, '/', $_SERVER['HTTP_HOST']);
			setcookie('cf7pp_amount_total', 		$amount_total, time()+3600, '/', $_SERVER['HTTP_HOST']);
			setcookie('cf7pp_default_symbol', 		$options['default_symbol'], time()+3600, '/', $_SERVER['HTTP_HOST']);
			setcookie('cf7pp_stripe_state', 		$tags['stripe_state'], time()+3600, '/', $_SERVER['HTTP_HOST']);
			setcookie('cf7pp_stripe_email', 		$stripe_email, time()+3600, '/', $_SERVER['HTTP_HOST']);
			setcookie('cf7pp_stripe_return', 		$options['stripe_return'], time()+3600, '/', $_SERVER['HTTP_HOST']);
		} else {
			session_start();
			$_SESSION['cf7pp_gateway'] = 		$gateway;
			$_SESSION['cf7pp_amount_total'] = 	$amount_total;
			$_SESSION['cf7pp_default_symbol'] = $options['default_symbol'];
			$_SESSION['cf7pp_stripe_state'] = 	$tags['stripe_state'];
			$_SESSION['cf7pp_stripe_email'] = 	$stripe_email;
			$_SESSION['cf7pp_stripe_return'] = 	$options['stripe_return'];
			session_write_close();
		}

	}
}


// after submit post for js - used for method 1 and 2 for paypal and stripe
add_action('wp_ajax_cf7pp_get_form_post', 'cf7pp_get_form_post_callback');
add_action('wp_ajax_nopriv_cf7pp_get_form_post', 'cf7pp_get_form_post_callback');
function cf7pp_get_form_post_callback() {

	$options = get_option('cf7pp_options');


	if (empty($options['session'])) {
		$session = '1';
	} else {
		$session = $options['session'];
	}

	if ($session == '1') {
		
		if(isset($_COOKIE['cf7pp_gateway'])) {
			$gateway = $_COOKIE['cf7pp_gateway'];
		}
		
		if(isset($_COOKIE['cf7pp_amount_total'])) {
			$amount_total = $_COOKIE['cf7pp_amount_total'];
		}
		
		if(isset($_COOKIE['cf7pp_default_symbol'])) {
			$default_symbol = $_COOKIE['cf7pp_default_symbol'];
		}
		
		if(isset($_COOKIE['cf7pp_stripe_email'])) {
			$stripe_email = $_COOKIE['cf7pp_stripe_email'];
		}
		
		if(isset($_COOKIE['cf7pp_stripe_return'])) {
			$stripe_return = $_COOKIE['cf7pp_stripe_return'];
		}
	} else {
		
		if(isset($_SESSION['cf7pp_gateway'])) {
			$gateway = $_SESSION['cf7pp_gateway'];
		}
		
		if(isset($_SESSION['cf7pp_amount_total'])) {
			$amount_total = $_SESSION['cf7pp_amount_total'];
		}
		
		if(isset($_SESSION['cf7pp_default_symbol'])) {
			$default_symbol = $_SESSION['cf7pp_default_symbol'];
		}
		
		if(isset($_SESSION['cf7pp_stripe_email'])) {
			$stripe_email = $_SESSION['cf7pp_stripe_email'];
		}
		
		if(isset($_SESSION['cf7pp_stripe_return'])) {
			$stripe_return = $_SESSION['cf7pp_stripe_return'];
		}
	}
	
	
	$response = array(
		'gateway'         		=> $gateway,
		'amount_total'         	=> $amount_total,
		'default_symbol'        => $default_symbol,
		'email'       	 		=> $stripe_email,
		'stripe_return'       	=> $stripe_return,
		
	);

	echo json_encode($response);

	wp_die();
}


// return stripe payment form
add_action('wp_ajax_cf7pp_get_form_stripe', 'cf7pp_get_form_stripe_callback');
add_action('wp_ajax_nopriv_cf7pp_get_form_stripe', 'cf7pp_get_form_stripe_callback');
function cf7pp_get_form_stripe_callback() {

	// generate nonce
	$salt = wp_salt();
	$nonce = wp_create_nonce('cf7pp_'.$salt);

	$options = get_option('cf7pp_options');
	
	
	$result = '';
	
	if ( (empty($options['pub_key_test']) && $options['mode_stripe'] == '1') || (empty($options['pub_key_live']) && $options['mode_stripe'] == '2') ) {
		$result .= 'Stripe Error. Admin: Please enter your Stripe Keys on the settings page.';
		
		$response = array(
			'html'         		=> $result,
		);
		
		echo json_encode($response);
		wp_die();
	}
	
	
	if (empty($options['session'])) {
		$session = '1';
	} else {
		$session = $options['session'];
	}

	if ($session == '1') {
		// show stripe test mode div
		if(isset($_COOKIE['cf7pp_stripe_state'])) {
			if($_COOKIE['cf7pp_stripe_state'] == 'test') {
				$result .= "<a href='https://stripe.com/docs/testing#cards' target='_blank' class='cf7pp-test-mode'>test mode</a>";
			}
		}
		
		if(isset($_COOKIE['cf7pp_amount_total'])) {
			$amount_total = $_COOKIE['cf7pp_amount_total'];
		} else {
			$amount_total = '';
		}
	} else {
		// show stripe test mode div
		if(isset($_SESSION['cf7pp_stripe_state'])) {
			if($_SESSION['cf7pp_stripe_state'] == 'test') {
				$result .= "<a href='https://stripe.com/docs/testing#cards' target='_blank' class='cf7pp-test-mode'>test mode</a>";
			}
		}
		
		if(isset($_SESSION['cf7pp_amount_total'])) {
			$amount_total = $_SESSION['cf7pp_amount_total'];
		} else {
			$amount_total = '';
		}
	}
	
	$currency = $options['currency'];
	if ($currency == '10') {
		// convert amount to cents
		$amount_total = (int)$amount_total;
	}

	$result .= "<div class='cf7pp_stripe'>";
		$result .= "<form method='post' id='cf7pp-payment-form'>";
			$result .= "<div class='cf7pp_body'>";
				$result .= "<div class='cf7pp_row'>";
					$result .= "<div class='cf7pp_details_input'>";
						$result .= "<label for='cf7pp_stripe_credit_card_number'>"; $result .= __($options['card_number'],'cf7pp_stripe'); $result .= "</label>";
						$result .= "<div id='cf7pp_stripe_credit_card_number'></div>";
					$result .= "</div>";

					$result .= "<div class='cf7pp_details_input'>";
						$result .= "<label for='cf7pp_stripe_credit_card_csv'>"; $result .= __($options['card_code'],'cf7pp_stripe'); $result .= "</label>";
						$result .= "<div id='cf7pp_stripe_credit_card_csv'></div>";
					$result .= "</div>";
				$result .= "</div>";
				$result .= "<div class='cf7pp_row'>";
					$result .= "<div class='cf7pp_details_input'>";
						$result .= "<label for='cf7pp_stripe_credit_card_expiration'>"; $result .= __($options['expiration'],'cf7pp_stripe'); $result .= "</label>";
						$result .= "<div id='cf7pp_stripe_credit_card_expiration''></div>";
					$result .= "</div>";

					$result .= "<div class='cf7pp_details_input'>";
						$result .= "<label for='cf7pp_stripe_credit_card_zip'>"; $result .= __($options['zip'],'cf7pp_stripe'); $result .= "</label>";
						$result .= "<div id='cf7pp_stripe_credit_card_zip'></div>";
					$result .= "</div>";
				$result .= "</div>";
				$result .= "<div id='card-errors' role='alert'></div>";
			$result .= "<br />&nbsp;<input id='stripe-submit' value='".$options['pay']." ".$options['default_symbol'].$amount_total."' type='submit'>";
			$result .= "<div>";
		$result .= "<input type='hidden' id='cf7pp_stripe_nonce' value='$nonce'>";
		$result .= "</form>";
	$result .= "<div>";


	$response = array(
		'html'         		=> $result,
	);

	echo json_encode($response);
	wp_die();
}
