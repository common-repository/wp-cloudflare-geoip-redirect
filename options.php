<?php
function wpcfr_load_admin_scripts( $hook) {

	if($hook == 'toplevel_page_wpcfr_plugin_options') {
		wp_enqueue_style( 'wpcfr-admin-style', plugin_dir_url( __FILE__ ).'cf-redirect-admin.css', array(), '1.1', false );
	}
	//print_r($hook);
}
add_action( 'admin_enqueue_scripts', 'wpcfr_load_admin_scripts' );

/**
 * Get the bootstrap! If using the plugin from wordpress.org, REMOVE THIS!
 */

if ( file_exists( dirname( __FILE__ ) . '/cmb2/init.php' ) ) {
	require_once dirname( __FILE__ ) . '/cmb2/init.php';
} elseif ( file_exists( dirname( __FILE__ ) . '/CMB2/init.php' ) ) {
	require_once dirname( __FILE__ ) . '/CMB2/init.php';
}



add_action( 'cmb2_admin_init', 'wpcfr_register_plugin_options' );
/**
 * Hook in and register a metabox to handle a plugin options page and adds a menu item.
 */
function wpcfr_register_plugin_options() {

	/**
	 * Registers options page menu item and form.
	 */
	$cmb_options = new_cmb2_box( array(
		'id'           => 'wpcfr_options',
		'title'        => esc_html__( 'CloudFlare GeoIP Redirect Options', 'wpcfr' ),
		'object_types' => array( 'options-page' ),

		/*
		 * The following parameters are specific to the options-page box
		 * Several of these parameters are passed along to add_menu_page()/add_submenu_page().
		 */

		'option_key'      => 'wpcfr_plugin_options', // The option key and admin menu page slug.
		'icon_url'        => 'dashicons-redo', // Menu icon. Only applicable if 'parent_slug' is left empty.
		'menu_title'      => esc_html__( 'CF Redirect', 'wpcfr' ), // Falls back to 'title' (above).
		// 'parent_slug'     => 'themes.php', // Make options page a submenu item of the themes menu.
		// 'capability'      => 'manage_options', // Cap required to view options-page.
		// 'position'        => 1, // Menu position. Only applicable if 'parent_slug' is left empty.
		// 'admin_menu_hook' => 'network_admin_menu', // 'network_admin_menu' to add network-level options page.
		// 'display_cb'      => false, // Override the options-page form output (CMB2_Hookup::options_page_output()).
		// 'save_button'     => esc_html__( 'Save Theme Options', 'wpcfr' ), // The text for the options-page save button. Defaults to 'Save'.
		// 'disable_settings_errors' => true, // On settings pages (not options-general.php sub-pages), allows disabling.
		'message_cb'      => 'wpcfr_options_page_message_callback',
		// 'tab_group'       => '', // Tab-group identifier, enables options page tab navigation.
		// 'tab_title'       => null, // Falls back to 'title' (above).
		// 'autoload'        => false, // Defaults to true, the options-page option will be autloaded.
	) );

	/**
	 * Repeatable Field Groups
	 */


	// $group_field_id is the field id string, so in this case: 'wpcfr_group_demo'
	$group_field_id = $cmb_options->add_field( array(
		'id'          => 'wpcfr_redirects',
		'type'        => 'group',
		'description' => esc_html__( 'You can add multiple redirect rules. Order of accordion items will represent order of redirect execution.', 'wpcfr' ),
		'options'     => array(
			'group_title'    => esc_html__( 'Redirect {#}', 'wpcfr' ), // {#} gets replaced by row number
			'add_button'     => esc_html__( 'Add Another Redirect', 'wpcfr' ),
			'remove_button'  => esc_html__( 'Remove Redirect', 'wpcfr' ),
			'sortable'       => true,
			// 'closed'      => true, // true to have the groups closed by default
			// 'remove_confirm' => esc_html__( 'Are you sure you want to remove?', 'wpcfr' ), // Performs confirmation before removing group.
		),
	) );


	$cmb_options->add_group_field( $group_field_id, array(
		'name'       => esc_html__( 'Redirect URL', 'wpcfr' ),
		'description' => esc_html__( 'Where you want to redirect visitors? Enter website or specific page URL. Optional only if you want to use Query string parameter.', 'wpcfr' ),
		'id'         => 'url',
		'type'       => 'text_url',
		// 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
		'attributes'  => array(
			'placeholder' => 'eg. https://www.another-website.com',
			'required'    => 'required',
		),
	) );

	$cmb_options->add_group_field( $group_field_id, array(
		'name'        => esc_html__( 'Redirect type', 'wpcfr' ),
		'description' => esc_html__( 'HTTP response status code', 'wpcfr' ),
		'id'          => 'type',
		'type'        => 'radio_inline',
		'options'     => array(
			100 => __('None (Inactive)', 'wpcfr' ),
			307 => __('Temporary Redirect (307)', 'wpcfr' ),
			301 => __('Moved Permanently (301)', 'wpcfr' )
		),
		'default'     => 100
	) );

	$cmb_options->add_group_field( $group_field_id, array(
		'name'       => esc_html__( 'Query string parameter', 'wpcfr' ),
		'description' => esc_html__( 'This setting is optional', 'wpcfr' ),
		'id'         => 'query_parameter',
		'type'       => 'title',
	) );
	$cmb_options->add_group_field( $group_field_id, array(
		'name'       => esc_html__( 'Parameter Name', 'wpcfr' ),
		'description' => esc_html__( 'Set name of parameter added to current URL (eg. "language" will display as "URL?language=XX").', 'wpcfr' ),
		'id'         => 'query_parameter_name',
		'type'       => 'text',
		// 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
		'attributes'  => array(
			'placeholder' => 'eg. language',
			/*'required'    => 'required',*/
		),
	) );
	$cmb_options->add_group_field( $group_field_id, array(
		'name'       => esc_html__( 'Parameter Value', 'wpcfr' ),
		'description' => esc_html__( 'Set value of parameter added to current URL (eg. "us" will display as "URL?language=us"). If left empty value will be set to visitors lowercase country code provided by Cloudflare IP Geolocation', 'wpcfr' ),
		'id'         => 'query_parameter_value',
		'type'       => 'text_small',
		// 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
		'attributes'  => array(
			'placeholder' => 'eg. us',
			/*'required'    => 'required',*/
		),
	) );

	$cmb_options->add_group_field( $group_field_id, array(
		'name'       => esc_html__( 'Country list', 'wpcfr' ),
		//'description' => esc_html__( 'Select', 'wpcfr' ),
		'id'         => 'list',
		'type'       => 'title',
	) );
	$cmb_options->add_group_field( $group_field_id, array(
		'name'        => esc_html__( 'Visitors Country', 'wpcfr' ),
		'description' => esc_html__( 'Select one or multiple countries', 'wpcfr' ),
		'id'          => 'country',
		'type'        => 'multicheck',
		'select_all_button' => false,
		/*'inline'  => true,*/
		/*'multiple'   => true,*/
		'options'      => array(
			'XX'	 =>	__( '	Unknown/reserved	 (XX)	', 'wpcfr' ),
			'AF'	 =>	__( '	Afghanistan	 (AF)	', 'wpcfr' ),
			'AX'	 =>	__( '	Åland Islands	 (AX)	', 'wpcfr' ),
			'AL'	 =>	__( '	Albania	 (AL)	', 'wpcfr' ),
			'DZ'	 =>	__( '	Algeria	 (DZ)	', 'wpcfr' ),
			'AS'	 =>	__( '	American Samoa	 (AS)	', 'wpcfr' ),
			'AD'	 =>	__( '	Andorra	 (AD)	', 'wpcfr' ),
			'AO'	 =>	__( '	Angola	 (AO)	', 'wpcfr' ),
			'AI'	 =>	__( '	Anguilla	 (AI)	', 'wpcfr' ),
			'AQ'	 =>	__( '	Antarctica	 (AQ)	', 'wpcfr' ),
			'AG'	 =>	__( '	Antigua and Barbuda	 (AG)	', 'wpcfr' ),
			'AR'	 =>	__( '	Argentina	 (AR)	', 'wpcfr' ),
			'AM'	 =>	__( '	Armenia	 (AM)	', 'wpcfr' ),
			'AW'	 =>	__( '	Aruba	 (AW)	', 'wpcfr' ),
			'AU'	 =>	__( '	Australia	 (AU)	', 'wpcfr' ),
			'AT'	 =>	__( '	Austria	 (AT)	', 'wpcfr' ),
			'AZ'	 =>	__( '	Azerbaijan	 (AZ)	', 'wpcfr' ),
			'BS'	 =>	__( '	Bahamas	 (BS)	', 'wpcfr' ),
			'BH'	 =>	__( '	Bahrain	 (BH)	', 'wpcfr' ),
			'BD'	 =>	__( '	Bangladesh	 (BD)	', 'wpcfr' ),
			'BB'	 =>	__( '	Barbados	 (BB)	', 'wpcfr' ),
			'BY'	 =>	__( '	Belarus	 (BY)	', 'wpcfr' ),
			'BE'	 =>	__( '	Belgium	 (BE)	', 'wpcfr' ),
			'BZ'	 =>	__( '	Belize	 (BZ)	', 'wpcfr' ),
			'BJ'	 =>	__( '	Benin	 (BJ)	', 'wpcfr' ),
			'BM'	 =>	__( '	Bermuda	 (BM)	', 'wpcfr' ),
			'BT'	 =>	__( '	Bhutan	 (BT)	', 'wpcfr' ),
			'BO'	 =>	__( '	Bolivia, Plurinational State of	 (BO)	', 'wpcfr' ),
			'BQ'	 =>	__( '	Bonaire, Sint Eustatius and Saba	 (BQ)	', 'wpcfr' ),
			'BA'	 =>	__( '	Bosnia and Herzegovina	 (BA)	', 'wpcfr' ),
			'BW'	 =>	__( '	Botswana	 (BW)	', 'wpcfr' ),
			'BV'	 =>	__( '	Bouvet Island	 (BV)	', 'wpcfr' ),
			'BR'	 =>	__( '	Brazil	 (BR)	', 'wpcfr' ),
			'IO'	 =>	__( '	British Indian Ocean Territory	 (IO)	', 'wpcfr' ),
			'BN'	 =>	__( '	Brunei Darussalam	 (BN)	', 'wpcfr' ),
			'BG'	 =>	__( '	Bulgaria	 (BG)	', 'wpcfr' ),
			'BF'	 =>	__( '	Burkina Faso	 (BF)	', 'wpcfr' ),
			'BI'	 =>	__( '	Burundi	 (BI)	', 'wpcfr' ),
			'KH'	 =>	__( '	Cambodia	 (KH)	', 'wpcfr' ),
			'CM'	 =>	__( '	Cameroon	 (CM)	', 'wpcfr' ),
			'CA'	 =>	__( '	Canada	 (CA)	', 'wpcfr' ),
			'CV'	 =>	__( '	Cape Verde	 (CV)	', 'wpcfr' ),
			'KY'	 =>	__( '	Cayman Islands	 (KY)	', 'wpcfr' ),
			'CF'	 =>	__( '	Central African Republic	 (CF)	', 'wpcfr' ),
			'TD'	 =>	__( '	Chad	 (TD)	', 'wpcfr' ),
			'CL'	 =>	__( '	Chile	 (CL)	', 'wpcfr' ),
			'CN'	 =>	__( '	China	 (CN)	', 'wpcfr' ),
			'CX'	 =>	__( '	Christmas Island	 (CX)	', 'wpcfr' ),
			'CC'	 =>	__( '	Cocos (Keeling) Islands	 (CC)	', 'wpcfr' ),
			'CO'	 =>	__( '	Colombia	 (CO)	', 'wpcfr' ),
			'KM'	 =>	__( '	Comoros	 (KM)	', 'wpcfr' ),
			'CG'	 =>	__( '	Congo	 (CG)	', 'wpcfr' ),
			'CD'	 =>	__( '	Congo, the Democratic Republic of the	 (CD)	', 'wpcfr' ),
			'CK'	 =>	__( '	Cook Islands	 (CK)	', 'wpcfr' ),
			'CR'	 =>	__( '	Costa Rica	 (CR)	', 'wpcfr' ),
			'CI'	 =>	__( '	Côte d\'Ivoire	 (CI)	', 'wpcfr' ),
			'HR'	 =>	__( '	Croatia	 (HR)	', 'wpcfr' ),
			'CU'	 =>	__( '	Cuba	 (CU)	', 'wpcfr' ),
			'CW'	 =>	__( '	Curaçao	 (CW)	', 'wpcfr' ),
			'CY'	 =>	__( '	Cyprus	 (CY)	', 'wpcfr' ),
			'CZ'	 =>	__( '	Czech Republic	 (CZ)	', 'wpcfr' ),
			'DK'	 =>	__( '	Denmark	 (DK)	', 'wpcfr' ),
			'DJ'	 =>	__( '	Djibouti	 (DJ)	', 'wpcfr' ),
			'DM'	 =>	__( '	Dominica	 (DM)	', 'wpcfr' ),
			'DO'	 =>	__( '	Dominican Republic	 (DO)	', 'wpcfr' ),
			'EC'	 =>	__( '	Ecuador	 (EC)	', 'wpcfr' ),
			'EG'	 =>	__( '	Egypt	 (EG)	', 'wpcfr' ),
			'SV'	 =>	__( '	El Salvador	 (SV)	', 'wpcfr' ),
			'GQ'	 =>	__( '	Equatorial Guinea	 (GQ)	', 'wpcfr' ),
			'ER'	 =>	__( '	Eritrea	 (ER)	', 'wpcfr' ),
			'EE'	 =>	__( '	Estonia	 (EE)	', 'wpcfr' ),
			'ET'	 =>	__( '	Ethiopia	 (ET)	', 'wpcfr' ),
			'FK'	 =>	__( '	Falkland Islands (Malvinas)	 (FK)	', 'wpcfr' ),
			'FO'	 =>	__( '	Faroe Islands	 (FO)	', 'wpcfr' ),
			'FJ'	 =>	__( '	Fiji	 (FJ)	', 'wpcfr' ),
			'FI'	 =>	__( '	Finland	 (FI)	', 'wpcfr' ),
			'FR'	 =>	__( '	France	 (FR)	', 'wpcfr' ),
			'GF'	 =>	__( '	French Guiana	 (GF)	', 'wpcfr' ),
			'PF'	 =>	__( '	French Polynesia	 (PF)	', 'wpcfr' ),
			'TF'	 =>	__( '	French Southern Territories	 (TF)	', 'wpcfr' ),
			'GA'	 =>	__( '	Gabon	 (GA)	', 'wpcfr' ),
			'GM'	 =>	__( '	Gambia	 (GM)	', 'wpcfr' ),
			'GE'	 =>	__( '	Georgia	 (GE)	', 'wpcfr' ),
			'DE'	 =>	__( '	Germany	 (DE)	', 'wpcfr' ),
			'GH'	 =>	__( '	Ghana	 (GH)	', 'wpcfr' ),
			'GI'	 =>	__( '	Gibraltar	 (GI)	', 'wpcfr' ),
			'GR'	 =>	__( '	Greece	 (GR)	', 'wpcfr' ),
			'GL'	 =>	__( '	Greenland	 (GL)	', 'wpcfr' ),
			'GD'	 =>	__( '	Grenada	 (GD)	', 'wpcfr' ),
			'GP'	 =>	__( '	Guadeloupe	 (GP)	', 'wpcfr' ),
			'GU'	 =>	__( '	Guam	 (GU)	', 'wpcfr' ),
			'GT'	 =>	__( '	Guatemala	 (GT)	', 'wpcfr' ),
			'GG'	 =>	__( '	Guernsey	 (GG)	', 'wpcfr' ),
			'GN'	 =>	__( '	Guinea	 (GN)	', 'wpcfr' ),
			'GW'	 =>	__( '	Guinea-Bissau	 (GW)	', 'wpcfr' ),
			'GY'	 =>	__( '	Guyana	 (GY)	', 'wpcfr' ),
			'HT'	 =>	__( '	Haiti	 (HT)	', 'wpcfr' ),
			'HM'	 =>	__( '	Heard Island and McDonald Islands	 (HM)	', 'wpcfr' ),
			'VA'	 =>	__( '	Holy See (Vatican City State)	 (VA)	', 'wpcfr' ),
			'HN'	 =>	__( '	Honduras	 (HN)	', 'wpcfr' ),
			'HK'	 =>	__( '	Hong Kong	 (HK)	', 'wpcfr' ),
			'HU'	 =>	__( '	Hungary	 (HU)	', 'wpcfr' ),
			'IS'	 =>	__( '	Iceland	 (IS)	', 'wpcfr' ),
			'IN'	 =>	__( '	India	 (IN)	', 'wpcfr' ),
			'ID'	 =>	__( '	Indonesia	 (ID)	', 'wpcfr' ),
			'IR'	 =>	__( '	Iran, Islamic Republic of	 (IR)	', 'wpcfr' ),
			'IQ'	 =>	__( '	Iraq	 (IQ)	', 'wpcfr' ),
			'IE'	 =>	__( '	Ireland	 (IE)	', 'wpcfr' ),
			'IM'	 =>	__( '	Isle of Man	 (IM)	', 'wpcfr' ),
			'IL'	 =>	__( '	Israel	 (IL)	', 'wpcfr' ),
			'IT'	 =>	__( '	Italy	 (IT)	', 'wpcfr' ),
			'JM'	 =>	__( '	Jamaica	 (JM)	', 'wpcfr' ),
			'JP'	 =>	__( '	Japan	 (JP)	', 'wpcfr' ),
			'JE'	 =>	__( '	Jersey	 (JE)	', 'wpcfr' ),
			'JO'	 =>	__( '	Jordan	 (JO)	', 'wpcfr' ),
			'KZ'	 =>	__( '	Kazakhstan	 (KZ)	', 'wpcfr' ),
			'KE'	 =>	__( '	Kenya	 (KE)	', 'wpcfr' ),
			'KI'	 =>	__( '	Kiribati	 (KI)	', 'wpcfr' ),
			'KP'	 =>	__( '	Korea, Democratic People\'s Republic of	 (KP)	', 'wpcfr' ),
			'KR'	 =>	__( '	Korea, Republic of	 (KR)	', 'wpcfr' ),
			'KW'	 =>	__( '	Kuwait	 (KW)	', 'wpcfr' ),
			'KG'	 =>	__( '	Kyrgyzstan	 (KG)	', 'wpcfr' ),
			'LA'	 =>	__( '	Lao People\'s Democratic Republic	 (LA)	', 'wpcfr' ),
			'LV'	 =>	__( '	Latvia	 (LV)	', 'wpcfr' ),
			'LB'	 =>	__( '	Lebanon	 (LB)	', 'wpcfr' ),
			'LS'	 =>	__( '	Lesotho	 (LS)	', 'wpcfr' ),
			'LR'	 =>	__( '	Liberia	 (LR)	', 'wpcfr' ),
			'LY'	 =>	__( '	Libya	 (LY)	', 'wpcfr' ),
			'LI'	 =>	__( '	Liechtenstein	 (LI)	', 'wpcfr' ),
			'LT'	 =>	__( '	Lithuania	 (LT)	', 'wpcfr' ),
			'LU'	 =>	__( '	Luxembourg	 (LU)	', 'wpcfr' ),
			'MO'	 =>	__( '	Macao	 (MO)	', 'wpcfr' ),
			'MK'	 =>	__( '	Macedonia, the Former Yugoslav Republic of	 (MK)	', 'wpcfr' ),
			'MG'	 =>	__( '	Madagascar	 (MG)	', 'wpcfr' ),
			'MW'	 =>	__( '	Malawi	 (MW)	', 'wpcfr' ),
			'MY'	 =>	__( '	Malaysia	 (MY)	', 'wpcfr' ),
			'MV'	 =>	__( '	Maldives	 (MV)	', 'wpcfr' ),
			'ML'	 =>	__( '	Mali	 (ML)	', 'wpcfr' ),
			'MT'	 =>	__( '	Malta	 (MT)	', 'wpcfr' ),
			'MH'	 =>	__( '	Marshall Islands	 (MH)	', 'wpcfr' ),
			'MQ'	 =>	__( '	Martinique	 (MQ)	', 'wpcfr' ),
			'MR'	 =>	__( '	Mauritania	 (MR)	', 'wpcfr' ),
			'MU'	 =>	__( '	Mauritius	 (MU)	', 'wpcfr' ),
			'YT'	 =>	__( '	Mayotte	 (YT)	', 'wpcfr' ),
			'MX'	 =>	__( '	Mexico	 (MX)	', 'wpcfr' ),
			'FM'	 =>	__( '	Micronesia, Federated States of	 (FM)	', 'wpcfr' ),
			'MD'	 =>	__( '	Moldova, Republic of	 (MD)	', 'wpcfr' ),
			'MC'	 =>	__( '	Monaco	 (MC)	', 'wpcfr' ),
			'MN'	 =>	__( '	Mongolia	 (MN)	', 'wpcfr' ),
			'ME'	 =>	__( '	Montenegro	 (ME)	', 'wpcfr' ),
			'MS'	 =>	__( '	Montserrat	 (MS)	', 'wpcfr' ),
			'MA'	 =>	__( '	Morocco	 (MA)	', 'wpcfr' ),
			'MZ'	 =>	__( '	Mozambique	 (MZ)	', 'wpcfr' ),
			'MM'	 =>	__( '	Myanmar	 (MM)	', 'wpcfr' ),
			'NA'	 =>	__( '	Namibia	 (NA)	', 'wpcfr' ),
			'NR'	 =>	__( '	Nauru	 (NR)	', 'wpcfr' ),
			'NP'	 =>	__( '	Nepal	 (NP)	', 'wpcfr' ),
			'NL'	 =>	__( '	Netherlands	 (NL)	', 'wpcfr' ),
			'NC'	 =>	__( '	New Caledonia	 (NC)	', 'wpcfr' ),
			'NZ'	 =>	__( '	New Zealand	 (NZ)	', 'wpcfr' ),
			'NI'	 =>	__( '	Nicaragua	 (NI)	', 'wpcfr' ),
			'NE'	 =>	__( '	Niger	 (NE)	', 'wpcfr' ),
			'NG'	 =>	__( '	Nigeria	 (NG)	', 'wpcfr' ),
			'NU'	 =>	__( '	Niue	 (NU)	', 'wpcfr' ),
			'NF'	 =>	__( '	Norfolk Island	 (NF)	', 'wpcfr' ),
			'MP'	 =>	__( '	Northern Mariana Islands	 (MP)	', 'wpcfr' ),
			'NO'	 =>	__( '	Norway	 (NO)	', 'wpcfr' ),
			'OM'	 =>	__( '	Oman	 (OM)	', 'wpcfr' ),
			'PK'	 =>	__( '	Pakistan	 (PK)	', 'wpcfr' ),
			'PW'	 =>	__( '	Palau	 (PW)	', 'wpcfr' ),
			'PS'	 =>	__( '	Palestine, State of	 (PS)	', 'wpcfr' ),
			'PA'	 =>	__( '	Panama	 (PA)	', 'wpcfr' ),
			'PG'	 =>	__( '	Papua New Guinea	 (PG)	', 'wpcfr' ),
			'PY'	 =>	__( '	Paraguay	 (PY)	', 'wpcfr' ),
			'PE'	 =>	__( '	Peru	 (PE)	', 'wpcfr' ),
			'PH'	 =>	__( '	Philippines	 (PH)	', 'wpcfr' ),
			'PN'	 =>	__( '	Pitcairn	 (PN)	', 'wpcfr' ),
			'PL'	 =>	__( '	Poland	 (PL)	', 'wpcfr' ),
			'PT'	 =>	__( '	Portugal	 (PT)	', 'wpcfr' ),
			'PR'	 =>	__( '	Puerto Rico	 (PR)	', 'wpcfr' ),
			'QA'	 =>	__( '	Qatar	 (QA)	', 'wpcfr' ),
			'RE'	 =>	__( '	Réunion	 (RE)	', 'wpcfr' ),
			'RO'	 =>	__( '	Romania	 (RO)	', 'wpcfr' ),
			'RU'	 =>	__( '	Russian Federation	 (RU)	', 'wpcfr' ),
			'RW'	 =>	__( '	Rwanda	 (RW)	', 'wpcfr' ),
			'BL'	 =>	__( '	Saint Barthélemy	 (BL)	', 'wpcfr' ),
			'SH'	 =>	__( '	Saint Helena, Ascension and Tristan da Cunha	 (SH)	', 'wpcfr' ),
			'KN'	 =>	__( '	Saint Kitts and Nevis	 (KN)	', 'wpcfr' ),
			'LC'	 =>	__( '	Saint Lucia	 (LC)	', 'wpcfr' ),
			'MF'	 =>	__( '	Saint Martin (French part)	 (MF)	', 'wpcfr' ),
			'PM'	 =>	__( '	Saint Pierre and Miquelon	 (PM)	', 'wpcfr' ),
			'VC'	 =>	__( '	Saint Vincent and the Grenadines	 (VC)	', 'wpcfr' ),
			'WS'	 =>	__( '	Samoa	 (WS)	', 'wpcfr' ),
			'SM'	 =>	__( '	San Marino	 (SM)	', 'wpcfr' ),
			'ST'	 =>	__( '	Sao Tome and Principe	 (ST)	', 'wpcfr' ),
			'SA'	 =>	__( '	Saudi Arabia	 (SA)	', 'wpcfr' ),
			'SN'	 =>	__( '	Senegal	 (SN)	', 'wpcfr' ),
			'RS'	 =>	__( '	Serbia	 (RS)	', 'wpcfr' ),
			'SC'	 =>	__( '	Seychelles	 (SC)	', 'wpcfr' ),
			'SL'	 =>	__( '	Sierra Leone	 (SL)	', 'wpcfr' ),
			'SG'	 =>	__( '	Singapore	 (SG)	', 'wpcfr' ),
			'SX'	 =>	__( '	Sint Maarten (Dutch part)	 (SX)	', 'wpcfr' ),
			'SK'	 =>	__( '	Slovakia	 (SK)	', 'wpcfr' ),
			'SI'	 =>	__( '	Slovenia	 (SI)	', 'wpcfr' ),
			'SB'	 =>	__( '	Solomon Islands	 (SB)	', 'wpcfr' ),
			'SO'	 =>	__( '	Somalia	 (SO)	', 'wpcfr' ),
			'ZA'	 =>	__( '	South Africa	 (ZA)	', 'wpcfr' ),
			'GS'	 =>	__( '	South Georgia and the South Sandwich Islands	 (GS)	', 'wpcfr' ),
			'SS'	 =>	__( '	South Sudan	 (SS)	', 'wpcfr' ),
			'ES'	 =>	__( '	Spain	 (ES)	', 'wpcfr' ),
			'LK'	 =>	__( '	Sri Lanka	 (LK)	', 'wpcfr' ),
			'SD'	 =>	__( '	Sudan	 (SD)	', 'wpcfr' ),
			'SR'	 =>	__( '	Suriname	 (SR)	', 'wpcfr' ),
			'SJ'	 =>	__( '	Svalbard and Jan Mayen	 (SJ)	', 'wpcfr' ),
			'SZ'	 =>	__( '	Swaziland	 (SZ)	', 'wpcfr' ),
			'SE'	 =>	__( '	Sweden	 (SE)	', 'wpcfr' ),
			'CH'	 =>	__( '	Switzerland	 (CH)	', 'wpcfr' ),
			'SY'	 =>	__( '	Syryan Arab Republic	 (SY)	', 'wpcfr' ),
			'TW'	 =>	__( '	Taiwan, Province of China	 (TW)	', 'wpcfr' ),
			'TJ'	 =>	__( '	Tajikistan	 (TJ)	', 'wpcfr' ),
			'TZ'	 =>	__( '	Tanzania, United Republic of	 (TZ)	', 'wpcfr' ),
			'TH'	 =>	__( '	Thailand	 (TH)	', 'wpcfr' ),
			'TL'	 =>	__( '	Timor-Leste	 (TL)	', 'wpcfr' ),
			'TG'	 =>	__( '	Togo	 (TG)	', 'wpcfr' ),
			'TK'	 =>	__( '	Tokelau	 (TK)	', 'wpcfr' ),
			'TO'	 =>	__( '	Tonga	 (TO)	', 'wpcfr' ),
			'TT'	 =>	__( '	Trinidad and Tobago	 (TT)	', 'wpcfr' ),
			'TN'	 =>	__( '	Tunisia	 (TN)	', 'wpcfr' ),
			'TR'	 =>	__( '	Turkey	 (TR)	', 'wpcfr' ),
			'TM'	 =>	__( '	Turkmenistan	 (TM)	', 'wpcfr' ),
			'TC'	 =>	__( '	Turks and Caicos Islands	 (TC)	', 'wpcfr' ),
			'TV'	 =>	__( '	Tuvalu	 (TV)	', 'wpcfr' ),
			'UG'	 =>	__( '	Uganda	 (UG)	', 'wpcfr' ),
			'UA'	 =>	__( '	Ukraine	 (UA)	', 'wpcfr' ),
			'AE'	 =>	__( '	United Arab Emirates	 (AE)	', 'wpcfr' ),
			'GB'	 =>	__( '	United Kingdom	 (GB)	', 'wpcfr' ),
			'US'	 =>	__( '	United States	 (US)	', 'wpcfr' ),
			'UM'	 =>	__( '	United States Minor Outlying Islands	 (UM)	', 'wpcfr' ),
			'UY'	 =>	__( '	Uruguay	 (UY)	', 'wpcfr' ),
			'UZ'	 =>	__( '	Uzbekistan	 (UZ)	', 'wpcfr' ),
			'VU'	 =>	__( '	Vanuatu	 (VU)	', 'wpcfr' ),
			'VE'	 =>	__( '	Venezuela, Bolivarian Republic of	 (VE)	', 'wpcfr' ),
			'VN'	 =>	__( '	Vietnam	 (VN)	', 'wpcfr' ),
			'VG'	 =>	__( '	Virgin Islands, British	 (VG)	', 'wpcfr' ),
			'VI'	 =>	__( '	Virgin Islands, U.S.	 (VI)	', 'wpcfr' ),
			'WF'	 =>	__( '	Wallis and Futuna	 (WF)	', 'wpcfr' ),
			'EH'	 =>	__( '	Western Sahara	 (EH)	', 'wpcfr' ),
			'YE'	 =>	__( '	Yemen	 (YE)	', 'wpcfr' ),
			'ZM'	 =>	__( '	Zambia	 (ZM)	', 'wpcfr' ),
			'ZW'	 =>	__( '	Zimbabwe	 (ZW)	', 'wpcfr' ),
		)
	) );


	$cmb_options->add_field( array(
		'name' => 'Debug',
		'desc' => 'CF Redirect debug info is displayed at HTTP headers',
		'id'   => 'debug_frontend',
		'type' => 'checkbox',
	) );

	/* if needed there is a need to control headers using "wpcfr_cache_control_handle_redirects()"

		$cmb_options->add_field( array(
			'name' => 'Cache Control Header',
			'desc' => 'Add "Cache-Control: no-cache, no-store, must-revalidate" to HTTP headers',
			'id'   => 'cache_control',
			'type' => 'checkbox',
		) );
	*/
}

/**
 * Callback to define the options-saved message.
 *
 * @param CMB2  $cmb The CMB2 object.
 * @param array $args {
 *     An array of message arguments
 *
 *     @type bool   $is_options_page Whether current page is this options page.
 *     @type bool   $should_notify   Whether options were saved and we should be notified.
 *     @type bool   $is_updated      Whether options were updated with save (or stayed the same).
 *     @type string $setting         For add_settings_error(), Slug title of the setting to which
 *                                   this error applies.
 *     @type string $code            For add_settings_error(), Slug-name to identify the error.
 *                                   Used as part of 'id' attribute in HTML output.
 *     @type string $message         For add_settings_error(), The formatted message text to display
 *                                   to the user (will be shown inside styled `<div>` and `<p>` tags).
 *                                   Will be 'Settings updated.' if $is_updated is true, else 'Nothing to update.'
 *     @type string $type            For add_settings_error(), Message type, controls HTML class.
 *                                   Accepts 'error', 'updated', '', 'notice-warning', etc.
 *                                   Will be 'updated' if $is_updated is true, else 'notice-warning'.
 * }
 */
function wpcfr_options_page_message_callback( $cmb, $args ) {

	if ( ! empty( $args['should_notify'] ) ) {

		if ( $args['is_updated'] ) {

			// Modify the updated message.
			$args['message'] = sprintf( esc_html__( '%s &mdash; Updated!', 'wpcfr' ), $cmb->prop( 'title' ) );
		}


		add_settings_error( $args['setting'], $args['code'], $args['message'], $args['type'] );
	}

	if ( ! empty( $args['is_options_page'] ) ) {

		if(!empty($_SERVER["HTTP_CF_IPCOUNTRY"])) {
			$country_code = sanitize_text_field($_SERVER["HTTP_CF_IPCOUNTRY"]);
		}else{
			$country_code = null;
		}

		if($country_code != null) {
			$args['message'] =  __( 'Cloudflare GeoIP is Active, your country code: ', 'wpcfr' ) . esc_html__($country_code, 'wpcfr');
			$args['type'] = 'info';
		}else{
			$args['message'] = __( '<strong>Country is not detected!</strong><br>Activate GeoIP in your <a href="https://dash.cloudflare.com/" target="_blank">Cloudflare account</a>, link with instructions is <a href="https://support.cloudflare.com/hc/en-us/articles/200168236-Configuring-Cloudflare-IP-Geolocation" target="_blank">here</a>.', 'wpcfr' );
			$args['type'] = 'error';
		}


		add_settings_error( $args['setting'], $args['code'], $args['message'], $args['type'] );
	}
}