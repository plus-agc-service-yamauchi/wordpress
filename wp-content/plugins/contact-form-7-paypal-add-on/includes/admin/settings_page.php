<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


// admin table
function cf7pp_admin_table() {



	if ( !current_user_can( "manage_options" ) )  {
	wp_die( __( "You do not have sufficient permissions to access this page." ) );
	}



	// save and update options
	if (isset($_POST['update'])) {

		$options['currency'] = 					sanitize_text_field($_POST['currency']);
		if (empty($options['currency'])) { 		$options['currency'] = ''; }

		$options['language'] = 					sanitize_text_field($_POST['language']);
		if (empty($options['language'])) { 		$options['language'] = ''; }

		$options['liveaccount'] = 				sanitize_text_field($_POST['liveaccount']);
		if (empty($options['liveaccount'])) { 	$options['liveaccount'] = ''; }

		$options['sandboxaccount'] = 			sanitize_text_field($_POST['sandboxaccount']);
		if (empty($options['sandboxaccount'])) { $options['sandboxaccount'] = ''; }

		$options['mode'] = 						sanitize_text_field($_POST['mode']);
		if (empty($options['mode'])) { 			$options['mode'] = '2'; }

		$options['mode_stripe'] = 				sanitize_text_field($_POST['mode_stripe']);
		if (empty($options['mode_stripe'])) { 	$options['mode_stripe'] = '2'; }

		$options['cancel'] = 					sanitize_text_field($_POST['cancel']);
		if (empty($options['cancel'])) { 		$options['cancel'] = ''; }

		$options['return'] = 					sanitize_text_field($_POST['return']);
		if (empty($options['return'])) { 		$options['return'] = ''; }

		$options['redirect'] = 					sanitize_text_field($_POST['redirect']);
		if (empty($options['redirect'])) { 		$options['redirect'] = '1'; }
		
		$options['session'] = 					sanitize_text_field($_POST['session']);
		if (empty($options['session'])) { 		$options['session'] = '1'; }

		$options['pub_key_live'] = 				sanitize_text_field($_POST['pub_key_live']);
		if (empty($options['pub_key_live'])) { 	$options['pub_key_live'] = ''; }

		$options['sec_key_live'] = 				sanitize_text_field($_POST['sec_key_live']);
		if (empty($options['sec_key_live'])) { 	$options['sec_key_live'] = ''; }

		$options['pub_key_test'] = 				sanitize_text_field($_POST['pub_key_test']);
		if (empty($options['pub_key_test'])) { 	$options['pub_key_test'] = ''; }

		$options['sec_key_test'] = 				sanitize_text_field($_POST['sec_key_test']);
		if (empty($options['sec_key_test'])) { 	$options['sec_key_test'] = ''; }

		$options['card_number'] = 				sanitize_text_field($_POST['card_number']);
		if (empty($options['card_number'])) { 	$options['card_number'] = 'Card Number'; }

		$options['card_code'] = 				sanitize_text_field($_POST['card_code']);
		if (empty($options['card_code'])) { 	$options['card_code'] = 'Card Code (CSV)'; }

		$options['expiration'] = 				sanitize_text_field($_POST['expiration']);
		if (empty($options['expiration'])) { 	$options['expiration'] = 'Expiration (MM/YY)'; }

		$options['zip'] = 						sanitize_text_field($_POST['zip']);
		if (empty($options['zip'])) { 			$options['zip'] = 'Billing Zip / Postal Code'; }

		$options['pay'] = 						sanitize_text_field($_POST['pay']);
		if (empty($options['pay'])) { 			$options['pay'] = 'Pay'; }

		$options['status'] = 					sanitize_text_field($_POST['status']);
		if (empty($options['status'])) { 		$options['status'] = 'Status'; }

		$options['order'] = 					sanitize_text_field($_POST['order']);
		if (empty($options['order'])) { 		$options['order'] = 'Order'; }

		$options['success'] = 					sanitize_text_field($_POST['success']);
		if (empty($options['success'])) { 		$options['success'] = 'Payment Successful'; }

		$options['failed'] = 					sanitize_text_field($_POST['failed']);
		if (empty($options['failed'])) { 		$options['failed'] = 'Payment Failed'; }
		
		$options['processing'] = 				sanitize_text_field($_POST['processing']);
		if (empty($options['processing'])) { 	$options['processing'] = 'Processing Payment'; }
		
		$options['default_symbol'] = 			sanitize_text_field($_POST['default_symbol']);
		if (empty($options['default_symbol'])) {$options['default_symbol'] = '$'; }
		
		$options['stripe_return'] = 			sanitize_text_field($_POST['stripe_return']);
		if (empty($options['stripe_return'])) { $options['stripe_return'] = ''; }

		update_option("cf7pp_options", $options);

		echo "<br /><div class='updated'><p><strong>"; _e("Settings Updated."); echo "</strong></p></div>";

	}























	// get options
	$options = get_option('cf7pp_options');

	if (empty($options['currency'])) { 					$options['currency'] = ''; }
	if (empty($options['language'])) { 					$options['language'] = ''; }
	if (empty($options['liveaccount'])) { 				$options['liveaccount'] = ''; }
	if (empty($options['sandboxaccount'])) { 			$options['sandboxaccount'] = ''; }
	if (empty($options['mode'])) { 						$options['mode'] = '2'; }
	if (empty($options['mode_stripe'])) { 				$options['mode_stripe'] = '2'; }
	if (empty($options['cancel'])) { 					$options['cancel'] = ''; }
	if (empty($options['return'])) { 					$options['return'] = ''; }
	if (empty($options['redirect'])) { 					$options['redirect'] = '1'; }
	if (empty($options['session'])) { 					$options['session'] = '1'; }
	if (empty($options['pub_key_live'])) { 				$options['pub_key_live'] = ''; }
	if (empty($options['sec_key_live'])) {				$options['sec_key_live'] = ''; }
	if (empty($options['pub_key_test'])) { 				$options['pub_key_test'] = ''; }
	if (empty($options['sec_key_test'])) { 				$options['sec_key_test'] = ''; }
	if (empty($options['card_number'])) { 				$options['card_number'] = 'Card Number'; }
	if (empty($options['card_code'])) { 				$options['card_code'] = 'Card Code (CSV)'; }
	if (empty($options['expiration'])) { 				$options['expiration'] = 'Expiration (MM/YY)'; }
	if (empty($options['zip'])) { 						$options['zip'] = 'Billing Zip / Postal Code'; }
	if (empty($options['pay'])) { 						$options['pay'] = 'Pay'; }
	if (empty($options['status'])) { 					$options['status'] = 'Status'; }
	if (empty($options['order'])) { 					$options['order'] = 'Order'; }
	if (empty($options['success'])) { 					$options['success'] = 'Payment Successful'; }
	if (empty($options['failed'])) { 					$options['failed'] = 'Payment Failed'; }
	if (empty($options['processing'])) { 				$options['processing'] = 'Processing Payment'; }
	if (empty($options['default_symbol'])) { 			$options['default_symbol'] = '$'; }
	if (empty($options['stripe_return'])) { 			$options['stripe_return'] = ''; }

	$siteurl = get_site_url();

	if (isset($_POST['hidden_tab_value'])) {
		$active_tab =  $_POST['hidden_tab_value'];
	} else {
		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : '1';
	}

	?>


<form method='post'>

	<table width='70%'><tr><td>
	<div class='wrap'><h2>Contact Form 7 - PayPal & Stripe Settings</h2></div><br /></td><td><br />
	<input type='submit' name='btn2' class='button-primary' style='font-size: 17px;line-height: 28px;height: 32px;float: right;' value='Save Settings'>
	</td></tr></table>

	<table width='100%'><tr><td width='70%' valign='top'>




		<h2 class="nav-tab-wrapper">
			<a onclick='closetabs("1,3,4,5,6,7");newtab("1");' href="#" id="id1" class="nav-tab <?php echo $active_tab == '1' ? 'nav-tab-active' : ''; ?>">Getting Started</a>
			<a onclick='closetabs("1,3,4,5,6,7");newtab("3");' href="#" id="id3" class="nav-tab <?php echo $active_tab == '3' ? 'nav-tab-active' : ''; ?>">Language & Currency</a>
			<a onclick='closetabs("1,3,4,5,6,7");newtab("4");' href="#" id="id4" class="nav-tab <?php echo $active_tab == '4' ? 'nav-tab-active' : ''; ?>">PayPal</a>
			<a onclick='closetabs("1,3,4,5,6,7");newtab("5");' href="#" id="id5" class="nav-tab <?php echo $active_tab == '5' ? 'nav-tab-active' : ''; ?>">Stripe</a>
			<a onclick='closetabs("1,3,4,5,6,7");newtab("6");' href="#" id="id6" class="nav-tab <?php echo $active_tab == '6' ? 'nav-tab-active' : ''; ?>">Other</a>
			<a onclick='closetabs("1,3,4,5,6,7");newtab("7");' href="#" id="id7" class="nav-tab <?php echo $active_tab == '7' ? 'nav-tab-active' : ''; ?>">Extensions</a>
		</h2>
		<br />




	</td><td colspan='3'></td></tr><tr><td valign='top'>









	<div id="1" style="display:none;border: 1px solid #CCCCCC;<?php echo $active_tab == '1' ? 'display:block;' : ''; ?>">
		<div style="background-color:#E4E4E4;padding:8px;color:#000;font-size:15px;color:#464646;font-weight: 700;border-bottom: 1px solid #CCCCCC;">
			&nbsp; Getting Started
		</div>
		<div style="background-color:#fff;padding:8px;">
			
			On this page, you can setup your general PayPal & Stripe settings which will be used for all of your contact forms.
			
			<br /><br />
			
			When go to your list of contact forms, make a new form or edit an existing form, you will see a new tab called 'PayPal & Stripe'. Here you can
			setup individual settings for that specific contact form.
			
			<br /><br />
			
			Once you have PayPal enabled on a form, you will receive an email as soon as the customer submits the form. Then after they have paid, you should receive a payment
			notification from PayPal with the details of the transaction.
			
			<br />
			
		</div>
	</div>



	<div id="3" style="display:none;border: 1px solid #CCCCCC;<?php echo $active_tab == '3' ? 'display:block;' : ''; ?>">
		<div style="background-color:#E4E4E4;padding:8px;color:#000;font-size:15px;color:#464646;font-weight: 700;border-bottom: 1px solid #CCCCCC;">
			&nbsp; Language & Currency
		</div>
		<div style="background-color:#fff;padding:8px;">

			<table>

				<tr><td class='cf7pp_width'>
					<b>Language:</b>
				</td><td>
					<select name="language">
					<option <?php if ($options['language'] == "1") { echo "SELECTED"; } ?> value="1">Danish</option>
					<option <?php if ($options['language'] == "2") { echo "SELECTED"; } ?> value="2">Dutch</option>
					<option <?php if ($options['language'] == "3") { echo "SELECTED"; } ?> value="3">English</option>
					<option <?php if ($options['language'] == "20") { echo "SELECTED"; } ?> value="20">English - UK</option>
					<option <?php if ($options['language'] == "4") { echo "SELECTED"; } ?> value="4">French</option>
					<option <?php if ($options['language'] == "5") { echo "SELECTED"; } ?> value="5">German</option>
					<option <?php if ($options['language'] == "6") { echo "SELECTED"; } ?> value="6">Hebrew</option>
					<option <?php if ($options['language'] == "7") { echo "SELECTED"; } ?> value="7">Italian</option>
					<option <?php if ($options['language'] == "8") { echo "SELECTED"; } ?> value="8">Japanese</option>
					<option <?php if ($options['language'] == "9") { echo "SELECTED"; } ?> value="9">Norwgian</option>
					<option <?php if ($options['language'] == "10") { echo "SELECTED"; } ?> value="10">Polish</option>
					<option <?php if ($options['language'] == "11") { echo "SELECTED"; } ?> value="11">Portuguese</option>
					<option <?php if ($options['language'] == "12") { echo "SELECTED"; } ?> value="12">Russian</option>
					<option <?php if ($options['language'] == "13") { echo "SELECTED"; } ?> value="13">Spanish</option>
					<option <?php if ($options['language'] == "14") { echo "SELECTED"; } ?> value="14">Swedish</option>
					<option <?php if ($options['language'] == "15") { echo "SELECTED"; } ?> value="15">Simplified Chinese -China only</option>
					<option <?php if ($options['language'] == "16") { echo "SELECTED"; } ?> value="16">Traditional Chinese - Hong Kong only</option>
					<option <?php if ($options['language'] == "17") { echo "SELECTED"; } ?> value="17">Traditional Chinese - Taiwan only</option>
					<option <?php if ($options['language'] == "18") { echo "SELECTED"; } ?> value="18">Turkish</option>
					<option <?php if ($options['language'] == "19") { echo "SELECTED"; } ?> value="19">Thai</option>
					</select>
			</td></tr>

				<tr><td>
				</td></tr>

				<tr><td class='cf7pp_width'>
				<b>Currency:</b></td><td>
				<select name="currency">
				<option <?php if ($options['currency'] == "1") { echo "SELECTED"; } ?> value="1">Australian Dollar - AUD</option>
				<option <?php if ($options['currency'] == "2") { echo "SELECTED"; } ?> value="2">Brazilian Real - BRL</option>
				<option <?php if ($options['currency'] == "3") { echo "SELECTED"; } ?> value="3">Canadian Dollar - CAD</option>
				<option <?php if ($options['currency'] == "4") { echo "SELECTED"; } ?> value="4">Czech Koruna - CZK</option>
				<option <?php if ($options['currency'] == "5") { echo "SELECTED"; } ?> value="5">Danish Krone - DKK</option>
				<option <?php if ($options['currency'] == "6") { echo "SELECTED"; } ?> value="6">Euro - EUR</option>
				<option <?php if ($options['currency'] == "7") { echo "SELECTED"; } ?> value="7">Hong Kong Dollar - HKD</option>
				<option <?php if ($options['currency'] == "8") { echo "SELECTED"; } ?> value="8">Hungarian Forint - HUF</option>
				<option <?php if ($options['currency'] == "9") { echo "SELECTED"; } ?> value="9">Israeli New Sheqel - ILS</option>
				<option <?php if ($options['currency'] == "10") { echo "SELECTED"; } ?> value="10">Japanese Yen - JPY</option>
				<option <?php if ($options['currency'] == "11") { echo "SELECTED"; } ?> value="11">Malaysian Ringgit - MYR</option>
				<option <?php if ($options['currency'] == "12") { echo "SELECTED"; } ?> value="12">Mexican Peso - MXN</option>
				<option <?php if ($options['currency'] == "13") { echo "SELECTED"; } ?> value="13">Norwegian Krone - NOK</option>
				<option <?php if ($options['currency'] == "14") { echo "SELECTED"; } ?> value="14">New Zealand Dollar - NZD</option>
				<option <?php if ($options['currency'] == "15") { echo "SELECTED"; } ?> value="15">Philippine Peso - PHP</option>
				<option <?php if ($options['currency'] == "16") { echo "SELECTED"; } ?> value="16">Polish Zloty - PLN</option>
				<option <?php if ($options['currency'] == "17") { echo "SELECTED"; } ?> value="17">Pound Sterling - GBP</option>
				<option <?php if ($options['currency'] == "18") { echo "SELECTED"; } ?> value="18">Russian Ruble - RUB</option>
				<option <?php if ($options['currency'] == "19") { echo "SELECTED"; } ?> value="19">Singapore Dollar - SGD</option>
				<option <?php if ($options['currency'] == "20") { echo "SELECTED"; } ?> value="20">Swedish Krona - SEK</option>
				<option <?php if ($options['currency'] == "21") { echo "SELECTED"; } ?> value="21">Swiss Franc - CHF</option>
				<option <?php if ($options['currency'] == "22") { echo "SELECTED"; } ?> value="22">Taiwan New Dollar - TWD</option>
				<option <?php if ($options['currency'] == "23") { echo "SELECTED"; } ?> value="23">Thai Baht - THB</option>
				<option <?php if ($options['currency'] == "24") { echo "SELECTED"; } ?> value="24">Turkish Lira - TRY</option>
				<option <?php if ($options['currency'] == "25") { echo "SELECTED"; } ?> value="25">U.S. Dollar - USD</option>
				</select></td></tr>

			</table>

		</div>
	</div>




	<div id="4" style="display:none;border: 1px solid #CCCCCC;<?php echo $active_tab == '4' ? 'display:block;' : ''; ?>">
		<div style="background-color:#E4E4E4;padding:8px;color:#000;font-size:15px;color:#464646;font-weight: 700;border-bottom: 1px solid #CCCCCC;">
		&nbsp; PayPal Account
		</div>
		<div style="background-color:#fff;padding:8px;">

			<table width='100%'>

				<tr><td class='cf7pp_width'>
				<b>Live Account: </b></td><td><input type='text' size=40 name='liveaccount' value='<?php echo $options['liveaccount']; ?>'> Required to use PayPal
				</td></tr>

				<tr><td class='cf7pp_width'></td><td>
				<br />Enter a valid Merchant account ID (strongly recommend) or PayPal account email address. All payments will go to this account.
				<br /><br />You can find your Merchant account ID in your PayPal account under Profile -> My business info -> Merchant account ID

				<br /><br />If you don't have a PayPal account, you can sign up for free at <a target='_blank' href='https://paypal.com'>PayPal</a>. <br /><br />
				</td></tr>

				<tr><td class='cf7pp_width'>
				<b>Sandbox Account: </b></td><td><input type='text' size=40 name='sandboxaccount' value='<?php echo $options['sandboxaccount']; ?>'> Optional
				</td></tr>

				<tr><td class='cf7pp_width'></td><td>
				Enter a valid sandbox PayPal account email address. A Sandbox account is a PayPal accont with fake money used for testing. This is useful to make sure your PayPal account and settings are working properly being going live.
				<br /><br />To create a Sandbox account, you first need a Developer Account. You can sign up for free at the <a target='_blank' href='https://www.paypal.com/webapps/merchantboarding/webflow/unifiedflow?execution=e1s2'>PayPal Developer</a> site. <br /><br />

				Once you have made an account, create a Sandbox Business and Personal Account <a target='_blank' href='https://developer.paypal.com/webapps/developer/applications/accounts'>here</a>. Enter the Business acount email on this page and use the Personal account username and password to buy something on your site as a customer.
				<br /><br />
				</td></tr>

				<tr><td class='cf7pp_width'>
				<b>Sandbox Mode:</b></td><td>
				<input <?php if ($options['mode'] == "1") { echo "checked='checked'"; } ?> type='radio' name='mode' value='1'>On (Sandbox mode)
				<input <?php if ($options['mode'] == "2") { echo "checked='checked'"; } ?> type='radio' name='mode' value='2'>Off (Live mode)
				</td></tr>

			</table>

		</div>
	</div>




	<div id="5" style="display:none;border: 1px solid #CCCCCC;<?php echo $active_tab == '5' ? 'display:block;' : ''; ?>">
		<div style="background-color:#E4E4E4;padding:8px;color:#000;font-size:15px;color:#464646;font-weight: 700;border-bottom: 1px solid #CCCCCC;">
		&nbsp; Stripe Account
		</div>
		<div style="background-color:#fff;padding:8px;">
		
			<center><b>Important: To use Stripe in live mode, an HTTPS certificate is required.</b></center>
			<br />

			<table width='100%'>
				<tr><td class='cf7pp_width'><b>Live Publishable Key: </b></td><td><input type='text' size=40 name='pub_key_live' value='<?php echo $options['pub_key_live']; ?>'> Required to use Stripe</td></tr>
				<tr><td class='cf7pp_width'><b>Live Secret Key: </b></td><td><input type='text' size=40 name='sec_key_live' value='<?php echo $options['sec_key_live']; ?>'> Required to use Stripe</td></tr>

				<tr></td><td><td>
				<br />
				You can get your API keys here: <a target='_blank' href='https://dashboard.stripe.com/account/apikeys'>https://dashboard.stripe.com/account/apikeys</a>
				<br /><br />
				</td></tr>

				<tr><td class='cf7pp_width'><b>Test Publishable Key: </b></td><td><input type='text' size=40 name='pub_key_test' value='<?php echo $options['pub_key_test']; ?>'> Optional</td></tr>
				<tr><td class='cf7pp_width'><b>Test Secret Key: </b></td><td><input type='text' size=40 name='sec_key_test' value='<?php echo $options['sec_key_test']; ?>'> Optional</td></tr>

				<tr><td>
				<br />
				</td></tr>

				<tr><td class='cf7pp_width'><b>Sandbox Mode:</b></td><td>

				<input <?php if ($options['mode_stripe'] == "1") { echo "checked='checked'"; } ?> type='radio' name='mode_stripe' value='1'>On (Sandbox mode)
				<input <?php if ($options['mode_stripe'] == "2") { echo "checked='checked'"; } ?> type='radio' name='mode_stripe' value='2'>Off (Live mode)</td></tr>


				<tr><td>
				<br />
				</td></tr>

				<tr><td class='cf7pp_width'><b>Default Text: </b></td><td></td></tr>
				<tr><td class='cf7pp_width'><b>Card Number: </b></td><td><input type='text' size=40 name='card_number' value='<?php echo $options['card_number']; ?>'></td></tr>
				<tr><td class='cf7pp_width'><b>Card Code (CSV): </b></td><td><input type='text' size=40 name='card_code' value='<?php echo $options['card_code']; ?>'></td></tr>
				<tr><td class='cf7pp_width'><b>Expiration (MM/YY): </b></td><td><input type='text' size=40 name='expiration' value='<?php echo $options['expiration']; ?>'></td></tr>
				<tr><td class='cf7pp_width'><b>Billing Zip / Postal Code: </b></td><td><input type='text' size=40 name='zip' value='<?php echo $options['zip']; ?>'></td></tr>
				<tr><td class='cf7pp_width'><b>Pay: </b></td><td><input type='text' size=40 name='pay' value='<?php echo $options['pay']; ?>'></td></tr>
				<tr><td class='cf7pp_width'><b>Status: </b></td><td><input type='text' size=40 name='status' value='<?php echo $options['status']; ?>'></td></tr>
				<tr><td class='cf7pp_width'><b>Order: </b></td><td><input type='text' size=40 name='order' value='<?php echo $options['order']; ?>'></td></tr>
				<tr><td class='cf7pp_width'><b>Payment Successful: </b></td><td><input type='text' size=40 name='success' value='<?php echo $options['success']; ?>'></td></tr>
				<tr><td class='cf7pp_width'><b>Payment Failed: </b></td><td><input type='text' size=40 name='failed' value='<?php echo $options['failed']; ?>'></td></tr>
				<tr><td class='cf7pp_width'><b>Processing Payment: </b></td><td><input type='text' size=40 name='processing' value='<?php echo $options['processing']; ?>'></td></tr>
				<tr><td class='cf7pp_width'><b>Currency Symbol: </b></td><td><input type='text' size=40 name='default_symbol' value='<?php echo $options['default_symbol']; ?>'></td></tr>

			</table>

		</div>
	</div>


	<div id="6" style="display:none;border: 1px solid #CCCCCC;<?php echo $active_tab == '6' ? 'display:block;' : ''; ?>">
		<div style="background-color:#E4E4E4;padding:8px;font-size:15px;color:#464646;font-weight: 700;border-bottom: 1px solid #CCCCCC;">
			&nbsp; Other Settings
		</div>
		<div style="background-color:#fff;padding:8px;">

			<table style="width: 100%;">

				<tr><td class='cf7pp_width'><b>PayPal Cancel URL: </b></td><td><input type='text' name='cancel' value='<?php echo $options['cancel']; ?>'> Optional <br /></td></tr>
				<tr><td class='cf7pp_width'></td><td>If the customer goes to PayPal and clicks the cancel button, where do they go. Example: http://example.com/cancel. Max length: 1,024. </td></tr>

				<tr><td>
				<br />
				</td></tr>

				<tr><td class='cf7pp_width'><b>PayPal Return URL: </b></td><td><input type='text' name='return' value='<?php echo $options['return']; ?>'> Optional <br /></td></tr>
				<tr><td class='cf7pp_width'></td><td>If the customer goes to PayPal and successfully pays, where are they redirected to after. Example: http://example.com/thankyou. Max length: 1,024. </td></tr>
				
				<tr><td>
				<br />
				</td></tr>
				
				<tr><td class='cf7pp_width'><b>Stripe Return URL: </b></td><td><input type='text' name='stripe_return' value='<?php echo $options['stripe_return']; ?>'> Optional <br /></td></tr>
				<tr><td class='cf7pp_width'></td><td>If the customer successfully pays with Stripe, where are they redirected to after. Example: http://example.com/thankyou. </td></tr>
				
				<tr><td>
				<br />
				</td></tr>
				
				<tr><td class='cf7pp_width'>
				<b>Redirect Method:</b></td><td>
				<input <?php if ($options['redirect'] == "1") { echo "checked='checked'"; } ?> type='radio' name='redirect' value='1'>1 (DOM wpcf7mailsent event listener)
				<input <?php if ($options['redirect'] == "2") { echo "checked='checked'"; } ?> type='radio' name='redirect' value='2'>2 (Form sent class listener)
				</td></tr>
				<tr><td class='cf7pp_width'></td><td>Method 1 recommend unless the form has problems redirecting.</td></tr>
				
				
				<tr><td>
				<br />
				</td></tr>
				
				<tr><td class='cf7pp_width'>
				<b>Temporary Storage Method:</b></td><td>
				<input <?php if ($options['session'] == "1") { echo "checked='checked'"; } ?> type='radio' name='session' value='1'>Cookies
				<input <?php if ($options['session'] == "2") { echo "checked='checked'"; } ?> type='radio' name='session' value='2'>Sessions
				</td></tr>
				<tr><td class='cf7pp_width'></td><td>Cookies are recommend unless the form has problems.</td></tr>

			</table>

		</div>
	</div>
	
	
	<div id="7" style="display:none;border: 1px solid #CCCCCC;<?php echo $active_tab == '7' ? 'display:block;' : ''; ?>">
		<div style="background-color:#E4E4E4;padding:8px;font-size:15px;color:#464646;font-weight: 700;border-bottom: 1px solid #CCCCCC;">
			&nbsp; Extensions
		</div>
		<div style="background-color:#fff;padding:8px;">
			
			<table style="width: 100%;">
				
				<?php
				cf7pp_extensions_page();
				?>
				
			</table>
			
		</div>
	</div>




	<input type='hidden' name='update' value='1'>
	<input type='hidden' name='hidden_tab_value' id="hidden_tab_value" value="<?php echo $active_tab; ?>">

</form>













	</td><td width="3%" valign="top">

	</td><td width="24%" valign="top">

	<div style="border: 1px solid #CCCCCC;">	
		<div style="background-color:#E4E4E4;padding:8px;font-size:15px;color:#464646;font-weight: 700;border-bottom: 1px solid #CCCCCC;">
		&nbsp; Pro Version
		</div>
		
		<div style="background-color:#fff;padding:8px;">		
		
		<center><label style="font-size:14pt;"><b>Pro Features: </b></label></center>
		
		<br />
		<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Only send email if payment is successful <br />
		<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Link any form item to price, quantity, or description <br />
		<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Sell up to 5 items per form  <br />
		<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Charge tax and shipping <br />
		<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Separate PayPal & Stripe account per form <br />
		<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Skip redirecting <br />
		<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Amazing plugin support <br />
		<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> No risk, 30 day return policy <br />
		<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Many more features! <br />
		
		<br />
		<center><a target='_blank' href="https://wpplugin.org/downloads/contact-form-7-paypal-add-on/" class='button-primary' style='font-size: 17px;line-height: 28px;height: 32px;'>Learn More</a></center>
		<br />
		</div>
	</div>
	
	
	</td><td width="2%" valign="top">



	</td></tr></table>

	<?php

}
