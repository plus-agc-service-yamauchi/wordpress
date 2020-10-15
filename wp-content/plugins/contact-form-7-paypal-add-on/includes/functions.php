<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


// format currency
function cf7pp_format_currency($price) {
	$price = floatval(preg_replace('/[^\d\.]/', '', $price));
	$price =number_format((float)$price, 2, '.', '');
	return $price;
}


// display activation notice
function cf7pp_my_plugin_admin_notices() {
	if (!get_option('cf7pp_my_plugin_notice_shown')) {
		echo "<div class='updated'><p><a href='admin.php?page=cf7pp_admin_table'>Click here to view the plugin settings</a>.</p></div>";
		update_option("cf7pp_my_plugin_notice_shown", "true");
	}
}
add_action('admin_notices', 'cf7pp_my_plugin_admin_notices');


// admin footer rate us link
function cf7pp_admin_rate_us( $footer_text ) {
	
	$screen = get_current_screen();

	if ($screen->base == 'contact_page_cf7pp_admin_table') {
		
		$rate_text = sprintf( __( 'Thank you for using software from <a href="%1$s" target="_blank">WP Plugin</a>! Please <a href="%2$s" target="_blank">rate us on WordPress.org</a>', '' ),
			'https://wpplugin.org',
			'https://wordpress.org/support/plugin/contact-form-7-paypal-add-on/reviews/?filter=5'
		);
		
		return str_replace( '</span>', '', $footer_text ) . ' | ' . $rate_text . '</span>';
		
	} else {
		return $footer_text;
	}

}
add_filter( 'admin_footer_text', 'cf7pp_admin_rate_us' );
