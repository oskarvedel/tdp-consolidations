<?php

/**
 * Plugin Name: tdp-scheduled-consolidations-plugin
 * Version: 1.0
 */

require_once dirname(__FILE__) . '/consolidate_geolocations.php';

// Define the activation function
function tdp_scheduled_consolidations_plugin_activation_function()
{
    // Check if the scheduled event is already set
    wp_schedule_event(time(), 'daily', 'tdp_unit_list_daily_event');
    trigger_error("tdp_unit_list_plugin_daily_function activated", E_USER_WARNING);
}

register_activation_hook(__FILE__, 'tdp_unit_list_plugin_activation_function');

// Define the deactivation function
function  tdp_scheduled_consolidations_plugin_deactivation_function()
{
    // Unschedule the daily event when the plugin or theme is deactivated
    trigger_error("tdp_unit_list_plugin_daily_function deactivated", E_USER_WARNING);
    wp_clear_scheduled_hook('tdp_unit_list_daily_event');
}

// Hook the activation and deactivation functions
register_deactivation_hook(__FILE__, 'tdp_unit_list_plugin_deactivation_function');


// Hook the daily function to the scheduled event
add_action('tdp_unit_list_daily_event', 'tdp_unit_list_plugin_daily_function');

// Define the function to be executed daily
function tdp_unit_list_plugin_daily_function()
{
    update_statistics_data();
    trigger_error("tdp_unit_list_plugin_daily_function just ran", E_USER_WARNING);
}
