<?php

/**
 * Plugin Name: CPD US Map
 * Description: This CPD US Map is a tailored widget laying out a map of the US and gives information on CPD affiliate and voting registration locations.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Ronnette Cox, Mekesia Brown
 * Author URI: n/a
 * Text Domain: cpdusmap
 *
 * @package CPDUSMap
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

// The main classes.
if (!class_exists('AffiliatesClass')) {
	include_once dirname(__FILE__) . '/includes/AffiliatesClass.php';
}

if (!class_exists('AffiliatesClassImages')) {
	include_once dirname(__FILE__) . "/includes/AffiliatesClassImages.php";
}

if (!class_exists('AffiliatesAdministration')) {
	include_once dirname(__FILE__) . "/includes/AffiliatesAdministration.php";
}

if (!class_exists('LocationClass')) {
	include_once dirname(__FILE__) . '/includes/LocationClass.php';
}

if (!class_exists('VoterInfoClass')) {
	include_once dirname(__FILE__) . '/includes/VoterInfoClass.php';
}

// Form the block, with js/css files.
function register_and_enqueue_cpd_block_assets()
{
	// Test VoterInfoClass and LocationClass method outputs
	$location = new LocationClass();
	$current_location = $location->getUserLocation("130.185.153.196");

	$voter_info = new VoterInfoClass();

	echo "<pre>";
	print_r($current_location);
	echo "-----";
	print_r($voter_info->getElectionQueryInfo());
	echo "-----";
	// print_r($voter_info->getVoterInfoQueryInfo());
	// echo "-----";
	// print_r($voter_info->getRepresentativeInfoByAddress());
	// echo "-----";
	// print_r($voter_info->getRepresentativeInfoByDivision());
	// echo "-----";
	// print_r($voter_info->getSearchInfo());
	echo "</pre>";

	exit;

	$cpd_nonce = wp_create_nonce("use_a_cpd_block");
	$index_js = plugins_url('build/index.js', __FILE__);

	$p_count_in_current_state = (new AffiliatesAdministration())->getAffiliateCountInCurrentState();
	$all_p_state_counts = (new AffiliatesAdministration())->getAllAffiliateStateCounts();
	$all_affiliates = (new AffiliatesAdministration())->get_cached_affiliates();
	$current_state_array = (new AffiliatesAdministration())->get_current_state_array();

	$script_asset_path = plugin_dir_path(__FILE__) . 'build/index.asset.php';
	$script_asset = require($script_asset_path);

	if (!file_exists($script_asset_path)) {
		throw new Error(
			'You need to run `npm start` or `npm run build` for the "create-block/cpdusmap" block first.'
		);
	}

	register_block_type(__DIR__ . '/build');

	wp_register_script(
		'create-wp-cpd-block-starter-block-editor',
		$index_js,
		$script_asset['dependencies'],
		$script_asset['version']
	);

	wp_localize_script(
		'create-wp-cpd-block-starter-block-editor',
		'cpdusmap_values',
		array(
			"cpd_nonce" => $cpd_nonce,
			"main_site_url" => get_site_url(),
			"pCountInCurrentState" => $p_count_in_current_state,
			"totalPCountsByState" => $all_p_state_counts,
			"allAffiliates" => $all_affiliates,
			"currentStateArray" => $current_state_array,
		)
	);

	wp_enqueue_script('create-wp-cpd-block-starter-block-editor');

	wp_enqueue_style(
		'wp-cpd-block-css-styles-edit',
		plugins_url('build/index.css', __FILE__),
		['wp-edit-blocks']
	);

	wp_enqueue_style(
		'wp-cpd-block-css-styles-front',
		plugins_url('build/style-index.css', __FILE__),
		['wp-edit-blocks']
	);

	register_uninstall_hook(__FILE__,  __NAMESPACE__ . '/includes/purge_widget_after_uninstall');
}

// Register the block, with js/css files.
add_action(
	"init",
	"register_and_enqueue_cpd_block_assets"
);

/**
 * https://developer.wordpress.org/plugins/administration-menus/sub-menus/
 */
function cpd_admin_plugins_html()
{
	wp_enqueue_style(
		'admin-page-forms-css',
		plugins_url('includes/css/admin-page-forms.css', __FILE__),
		['wp-edit-blocks']
	);

	wp_enqueue_media();

	wp_enqueue_script('admin-page-forms-js', plugins_url('includes/js/admin-page-forms.js', __FILE__));

	// Empty out the cached version of the images just in case
	delete_transient((new AffiliatesClassImages)->cpd_affiliate_image_cache);
	delete_transient((new AffiliatesClass)->cpd_affiliates);

	// Output setting sections and their fields
	// (sections are registered for "cpd", each field is registered to a specific section)
	do_settings_sections('cpd');

	echo "<h1>" . esc_html(get_admin_page_title()) . "</h1>";

	$admin_plugins_affiliates = new AffiliatesAdministration();
	$admin_plugins_affiliates->show_add_new_affiliate_thickbox();
	$admin_plugins_affiliates->show_affiliates_by_state();
}

function cpd_admin_plugins_html_add_p_form()
{
	$info = $_POST;
	$redirect_url = $info["_wp_http_referer"];
	$new_p_nonce = $info['cpd_add_affiliate_nonce'];
	$new_p_nonce_name = 'cpd_add_affiliate';
	$is_the_new_p_nonce_set = isset($new_p_nonce);
	$is_new_p_nonce_verified = wp_verify_nonce($new_p_nonce, $new_p_nonce_name);

	if ($is_the_new_p_nonce_set && $is_new_p_nonce_verified) {
		wp_redirect($redirect_url);

		exit;
	} else {
		wp_die(
			__('Invalid nonce specified', "cpd_us_map"),
			__('Error', "cpd_us_map"),
			array(
				'response' 	=> 403,
				'back_link' => 'plugins.php?page=' . "cpd_us_map",

			)
		);
	}
}

add_action(
	'admin_post_cpd_add_affiliate',
	'cpd_admin_plugins_html_add_p_form'
);

function cpd_admin_plugins_html_edit_p_form()
{
	$info = $_POST;
	$redirect_url = $info["_wp_http_referer"];
	$new_p_nonce = $info['cpd_edit_affiliate_nonce'];
	$new_p_nonce_name = 'cpd_edit_affiliate';
	$is_the_new_p_nonce_set = isset($new_p_nonce);
	$is_new_p_nonce_verified = wp_verify_nonce($new_p_nonce, $new_p_nonce_name);

	if ($is_the_new_p_nonce_set && $is_new_p_nonce_verified) {
		wp_redirect($redirect_url);

		exit;
	} else {
		wp_die(
			__('Invalid nonce specified', "cpd_us_map"),
			__('Error', "cpd_us_map"),
			array(
				'response' 	=> 403,
				'back_link' => 'plugins.php?page=' . "cpd_us_map",

			)
		);
	}
}

add_action(
	'admin_post_cpd_edit_affiliate',
	'cpd_admin_plugins_html_edit_p_form'
);

function cpd_admin_plugins_html_delete_p_form()
{
	$info = $_POST;
	$redirect_url = $info["_wp_http_referer"];
	$affiliate_id_number = $info['affiliate_id_number'];
	$delete_p_nonce = $info['cpd_delete_affiliate_nonce'];
	$delete_p_nonce_name = 'cpd_delete_affiliate';
	$is_the_delete_p_nonce_set = isset($delete_p_nonce);
	$is_delete_p_nonce_verified = wp_verify_nonce($delete_p_nonce, $delete_p_nonce_name);

	if ($is_the_delete_p_nonce_set && $is_delete_p_nonce_verified && is_numeric($affiliate_id_number)) {
		wp_redirect($redirect_url);

		exit;
	} else {
		wp_die(
			__('Invalid nonce specified', "cpd_us_map"),
			__('Error', "cpd_us_map"),
			array(
				'response' 	=> 403,
				'back_link' => 'plugins.php?page=' . "cpd_us_map",
			)
		);
	}
}

add_action(
	'admin_post_cpd_delete_affiliate',
	'cpd_admin_plugins_html_delete_p_form'
);

function cpd_admin_plugins()
{
	add_plugins_page(
		__('CPD Affiliates'),
		__('CPD Affiliates'),
		'manage_options',
		'cpd_us_map',
		'cpd_admin_plugins_html'
	);
}

add_action(
	'admin_menu',
	'cpd_admin_plugins'
);

// Used in D3 code for displaying the map's svg elements
add_filter(
	'wp_kses_allowed_html',
	function ($tags) {
		$tags['svg'] = array(
			'x' => array(),
			'y' => array(),
			'xmlns' => array(),
			'width' => array(),
			'height' => array(),
			'fill' => array(),
			'viewbox' => array(),
			'role' => array(),
			'aria-hidden' => array(),
			'focusable' => array(),
			'onclick' => array(),
		);

		$tags['path'] = array(
			'd' => array(),
			'fill' => array(),
		);

		$tags['defs'] = array();
		$tags['g'] = array();
		$tags['title'] = array();
		$tags['circle'] = array(
			'cx' => array(),
			'cy' => array(),
			'r' => array(),
		);

		return $tags;
	},
	10,
	2
);
