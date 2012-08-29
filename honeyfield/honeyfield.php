<?php
/*
Plugin Name: HoneyField
Plugin URI: http://www.honeyfield.io/
Description: A plugin that captures requests and provides security analytics.
Version: 1.0
Author: Matt Konda
Author URI: http://www.jemurai.com
License: GPL
*/

// Section to do the work of the plugin.
include 'honey.php';
include 'config.php';

add_action('init', 'honeypress');

function honeypress(){
	$config = new Config();
	$config->hf_key = get_option('honeyfield_key');
	$config->hf_host = get_option('honeyfield_host');
	$config->hf_blocked_params = get_option('honeyfield_blocked_params');
	$config->hf_sample_rate = get_option('honeyfield_sample_rate');
	$config->hf_debug_mode = get_option('honeyfield_debug_mode');
	
	$h = new Honey($config);
	
	$current_ip = $_SERVER['REMOTE_ADDR'];
	$request = build_string_from_request($config);
	$app = get_option('honeyfield_appname');
	$uri = $_SERVER['REQUEST_URI'];
	$e = new Event($app, $uri, $request, $current_ip, $config->hf_key);
	$h->fire_event($e);
}

function array_to_string($config, $array){
	$output = "";
	foreach ($array as $property=>$value) {
		if ( watching($config, $property)){
			$output .= $property . "=" . $value . "&"; 			
		} else {
			$output .= $property . "=" . "REDACTED" . "&";
		}
	}
	return $output;	
}

// Should we include this field in the data sent to HoneyField.
function watching($config, $property){
	$blocked_params = explode(',', $config->hf_blocked_params);
	foreach ($blocked_params as $blocked_param){
		if ($property == $blocked_param){
			return false;
		}
	}
	return true;
}

function build_string_from_request($config){
	$output = "";
	$output .= array_to_string($config, $_COOKIE);
	$output .= array_to_string($config, $_GET);
	$output .= array_to_string($config, $_POST);
	return $output;
}

// Section to install / uninstall.
// 
register_activation_hook(__FILE__,'honeyfield_install'); 
register_deactivation_hook( __FILE__, 'honeyfield_remove' );

function honeyfield_install(){
	add_option("honeyfield_appname");
	add_option("honeyfield_key", 'abc123');
	add_option("honeyfield_host", 'http://www.honeyfield.io/');
	add_option("honeyfield_blocked_params", "pwd");
	add_option("honeyfield_sample_rate", 10);
	add_option("honeyfield_debug_mode", false);
}

function honeyfield_remove(){
	delete_option("honeyfield_appname");
	delete_option("honeyfield_key");
	delete_option("honeyfield_host");
	delete_option("honeyfield_blocked_params");
	delete_option("honeyfield_sample_rate");
	delete_option("honeyfield_debug_mode");
}

// Section to set up menus and options.
// Derived from various helps, tutorials and 
// the wordpress-firewall-2 plugin.
if (is_admin()){
	add_action('admin_menu', 'honeyfield_build_admin_menu');
}

function honeyfield_build_admin_menu(){
 	add_options_page(__('HoneyField','honeyfield'), __('HoneyField','honeyfield'), 'manage_options', 'honeyfield', 'honeyfield_settings_page');
	add_action('admin_init', 'register_honeyfield_settings');
}

function register_honeyfield_settings(){
	register_setting('honeyfield', 'honeyfield_appname');
	register_setting('honeyfield', 'honeyfield_key');
	register_setting('honeyfield', 'honeyfield_host');
	register_setting('honeyfield', 'honeyfield_blocked_params');
	register_setting('honeyfield', 'honeyfield_sample_rate');
	register_setting('honeyfield', 'honeyfield_debug_mode');
}

function honeyfield_settings_page(){
	if ( !current_user_can('manage_options')){
		wp_die( __('You do not have sufficient privileges to access this page.'));
	}
	
?>
<div class="wrap">
	<h2>HoneyField Options:</h2>
	<form action="options.php" method="post">
		<?php settings_fields('honeyfield'); ?>
		<?php do_settings_sections('honeyfield'); ?>
		<table class="form-table">
		        <tr valign="top">
		        <th scope="row">Application Name</th>
		        <td><input type="text" name="honeyfield_appname" value="<?php echo get_option('honeyfield_appname'); ?>" /></td>
		        </tr>

		        <tr valign="top">
		        <th scope="row">API Key</th>
		        <td><input type="text" size="50" name="honeyfield_key" value="<?php echo get_option('honeyfield_key'); ?>" /></td>
		        </tr>

		        <tr valign="top">
		        <th scope="row">HoneyField Host</th>
		        <td><input type="text" size="50" name="honeyfield_host" value="<?php echo get_option('honeyfield_host'); ?>" /></td>
		        </tr>
		        <tr valign="top">
		        <th scope="row">Blocked Parameters</th>
		        <td><input type="text" size="50" name="honeyfield_blocked_params" value="<?php echo get_option('honeyfield_blocked_params'); ?>" /></td>
		        </tr>
		        <tr valign="top">
		        <th scope="row">Sample Rate (Integer between 0 and 100)</th>
		        <td><input type="text" name="honeyfield_sample_rate" value="<?php echo get_option('honeyfield_sample_rate'); ?>" /></td>
		        </tr>
		        <tr valign="top">
		        <th scope="row">Debug Mode</th>
		        <td><input type="checkbox" name="honeyfield_debug_mode" value="<?php echo get_option('honeyfield_debug_mode'); ?>" /></td>
		        </tr>

		    </table>

		    <p class="submit">
		    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		    </p>
	</form>
</div>
<? } ?>
