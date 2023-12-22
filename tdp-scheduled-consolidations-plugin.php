<?php

/**
 * Plugin Name: tdp-scheduled-consolidations-plugin
 * Version: 1.0
 */

require_once(dirname(__FILE__) . '/general-consolidations.php');
require_once(dirname(__FILE__) . '/geodir-consolidations.php');
require_once(dirname(__FILE__) . '/seo-consolidations.php');

// Define the activation function
function tdp_scheduled_consolidations_plugin_activation_function()
{
    // Check if the scheduled event is already set
    wp_schedule_event(time(), 'daily', 'tdp_scheduled_consolidations_daily_event');
    trigger_error("tdp_scheduled_consolidations_plugin_daily_function activated", E_USER_NOTICE);
}

register_activation_hook(__FILE__, 'tdp_scheduled_consolidations_plugin_activation_function');

// Define the deactivation function
function  tdp_scheduled_consolidations_plugin_deactivation_function()
{
    // Unschedule the daily event when the plugin or theme is deactivated
    trigger_error("tdp_scheduled_consolidations_plugin_daily_function deactivated", E_USER_NOTICE);
    wp_clear_scheduled_hook('tdp_scheduled_consolidations_daily_event');
}

// Hook the activation and deactivation functions
register_deactivation_hook(__FILE__, 'tdp_scheduled_consolidations_plugin_deactivation_function');


// Hook the daily function to the scheduled event
add_action('tdp_scheduled_consolidations_daily_event', 'tdp_scheduled_consolidations_plugin_daily_function');

// Define the function to be executed daily
function tdp_scheduled_consolidations_plugin_daily_function()
{
    consolidate_geolocations();
    trigger_error("tdp_scheduled_consolidations_plugin_daily_function just ran", E_USER_NOTICE);
}

function consolidate_geolocations()
{
    geodir_consolidations();
    general_consolidations();
    seo_consolidations();
    trigger_error("consolidated geolocations", E_USER_NOTICE);
}

function send_email($body, $subject)
{
    $to = get_option('admin_email');
    $subject = $subject;
    $headers = 'From: system@tjekdepot.dk <system@tjekdepot.dk>' . "\r\n";

    wp_mail($to, $subject, $body, $headers);
}

function add_geodir_consolidations_button($links)
{
    $geodir_link = '<a href="' . admin_url('admin-ajax.php?action=geodir_consolidations') . '">Run geodir geolocation consolidations</a>';
    array_unshift($links, $geodir_link);
    return $links;
}
add_filter('plugin_action_links_tdp-scheduled-consolidations/tdp-scheduled-consolidations-plugin.php', 'add_geodir_consolidations_button');

function handle_geodir_consolidations()
{
    geodir_consolidations();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('wp_ajax_geodir_consolidations', 'handle_geodir_consolidations');

function add_general_consolidations_button($links)
{
    $general_link = '<a href="' . admin_url('admin-ajax.php?action=general_consolidations') . '">Run general geolocation consolidations</a>';
    array_unshift($links, $general_link);
    return $links;
}
add_filter('plugin_action_links_tdp-scheduled-consolidations/tdp-scheduled-consolidations-plugin.php', 'add_general_consolidations_button');

function handle_general_consolidations()
{
    general_consolidations();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('wp_ajax_general_consolidations', 'handle_general_consolidations');


//add a button to the plugin settings page to run seo consolidations
function add_seo_consolidations_button($links)
{
    $seo_link = '<a href="' . admin_url('admin-ajax.php?action=seo_consolidations') . '">Run SEO geolocation consolidations</a>';
    array_unshift($links, $seo_link);
    return $links;
}
add_filter('plugin_action_links_tdp-scheduled-consolidations/tdp-scheduled-consolidations-plugin.php', 'add_seo_consolidations_button');

//add a button to the plugin settings page to consolidate geolocations
function add_consolidate_button($links)
{
    $consolidate_link = '<a href="' . esc_url(admin_url('admin-post.php?action=consolidate_geolocations')) . '">Run ALL geolocation consolidations</a>';
    array_unshift($links, $consolidate_link);
    return $links;
}
add_filter('plugin_action_links_tdp-scheduled-consolidations/tdp-scheduled-consolidations-plugin.php', 'add_consolidate_button');

function handle_consolidate_geolocations()
{
    consolidate_geolocations();
    wp_redirect(admin_url('plugins.php?s=tdp&plugin_status=all'));
    exit;
}
add_action('admin_post_consolidate_geolocations', 'handle_consolidate_geolocations');
