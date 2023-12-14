<?php

function general_consolidations()
{
    $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));
    find_duplicate_geolocations($geolocations);
    set_nearest_geolocations_with_gd_places_for_all_geolocations();
    trigger_error("general consolidations done", E_USER_NOTICE);
}

function find_duplicate_geolocations($geolocations)
{
    $emailoutput = "";

    $titles = array_column($geolocations, 'post_title');
    //var_dump($titles);
    $duplicate_titles = array_filter(array_count_values($titles), function ($count) {
        return $count > 1;
    });

    foreach ($duplicate_titles as $title => $count) {
        $message = "Duplicate geolocation titles found: $title, count: $count\n";
        trigger_error($message, E_USER_WARNING);
        $emailoutput .= $message;
    }

    if ($emailoutput != "") {
        send_email($emailoutput, 'Duplicate geolocation(s) found');
    }
}

function set_nearest_geolocations_with_gd_places_for_all_geolocations()
{
    $geolocations_ids = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1, 'fields' => 'ids'));
    if (empty($geolocations_ids)) {
        trigger_error("No geolocations found", E_USER_WARNING);
        return;
    }


    foreach ($geolocations_ids as $geolocation_id) {
        $nearest_geolocations = get_nearest_geolocations_with_gd_places_for_single_geolocation($geolocations_ids, $geolocation_id, 5);

        $nearest_geolocation_ids = array_keys($nearest_geolocations);
        update_post_meta($geolocation_id, 'nearest_geolocations', $nearest_geolocation_ids);

        unset($nearest_geolocations, $nearest_geolocations_keys);
    }
}

function get_nearest_geolocations_with_gd_places_for_single_geolocation($geolocations_ids, $current_geolocation_id, $limit = 5)
{
    $distances = array();
    $current_geodir_title = get_the_title($current_geolocation_id);

    $current_lat = get_post_meta($current_geolocation_id, 'latitude', true);
    $current_long = get_post_meta($current_geolocation_id, 'longitude', true);

    $geolocations_ids = array_diff($geolocations_ids, [$current_geolocation_id]);

    foreach ($geolocations_ids as $geolocation_id) {
        $gd_places = get_post_meta($geolocation_id, 'gd_place_list', true);
        if (empty($gd_places)) {
            continue;
        }
        unset($gd_places);

        $lat = get_post_meta($geolocation_id, 'latitude', true);
        $long = get_post_meta($geolocation_id, 'longitude', true);

        $theta = $current_long - $long;

        $dist = sin(deg2rad($current_lat)) * sin(deg2rad($lat)) +  cos(deg2rad($current_lat)) * cos(deg2rad($lat)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $kilometers = $dist * 60 * 1.852;

        $distances[$geolocation_id] = $kilometers;
        unset($lat, $long, $kilometers, $theta, $dist);
    }

    asort($distances);
    $distances = array_slice($distances, 0, $limit, true);

    return $distances;
}


function set_near_gd_places_for_all_geolocations()
{

    $geolocations_ids = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1, 'fields' => 'ids'));
    if (empty($geolocations_ids)) {
        trigger_error("No geolocations found", E_USER_WARNING);
        return;
    }

    foreach ($geolocations_ids as $geolocation_id) {
        $nearest_geolocations = get_nearest_geolocations_with_gd_places_for_single_geolocation($geolocations_ids, $geolocation_id, 5);

        $nearest_geolocation_ids = array_keys($nearest_geolocations);
        update_post_meta($geolocation_id, 'nearest_geolocations', $nearest_geolocation_ids);

        unset($nearest_geolocations, $nearest_geolocations_keys);
    }
}

function get_near_gd_places_for_single_geolocation($geolocations_ids, $current_geolocation_id, $limit = 5)
{
    $distances = array();
    $current_geodir_title = get_the_title($current_geolocation_id);

    $current_lat = get_post_meta($current_geolocation_id, 'latitude', true);
    $current_long = get_post_meta($current_geolocation_id, 'longitude', true);

    $geolocations_ids = array_diff($geolocations_ids, [$current_geolocation_id]);

    foreach ($geolocations_ids as $geolocation_id) {
        $gd_places = get_post_meta($geolocation_id, 'gd_place_list', true);
        if (empty($gd_places)) {
            continue;
        }
        unset($gd_places);

        $lat = get_post_meta($geolocation_id, 'latitude', true);
        $long = get_post_meta($geolocation_id, 'longitude', true);

        $theta = $current_long - $long;

        $dist = sin(deg2rad($current_lat)) * sin(deg2rad($lat)) +  cos(deg2rad($current_lat)) * cos(deg2rad($lat)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $kilometers = $dist * 60 * 1.852;

        $distances[$geolocation_id] = $kilometers;
        unset($lat, $long, $kilometers, $theta, $dist);
    }

    asort($distances);
    $distances = array_slice($distances, 0, $limit, true);

    return $distances;
}
