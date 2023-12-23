<?php

function seo_consolidations()
{
    set_gd_places_within_radius_sorted_by_distance_for_all_geolocations(40);
    set_geolocations_within_8_km_with_gd_places_within_8_km_sorted_by_distance_for_all_geolocations();
    generate_seo_gd_place_list_for_all_geolocations();

    //generate_seo_schools(); //to be developed
    //set_50_nearest_geolocations_sorted_by_distance_list_for_all_geolocations(); //to be developed

    trigger_error("seo consolidations done", E_USER_NOTICE);
}

function set_geolocations_within_8_km_with_gd_places_within_8_km_sorted_by_distance_for_all_geolocations()
{
    $geolocations_ids = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1, 'fields' => 'ids'));
    if (empty($geolocations_ids)) {
        trigger_error("No geolocations found", E_USER_WARNING);
        return;
    }

    foreach ($geolocations_ids as $geolocation_id) {
        $geolocations_within_8_km_with_gd_places_within_8_km_sorted_by_distance = get_geolocations_within_8_km_with_gd_places_within_8_km_sorted_by_distance_for_single_geolocation($geolocations_ids, $geolocation_id);

        update_post_meta($geolocation_id, 'geolocations_within_8_km_with_gd_places_within_8_km_sorted_by_distance', $geolocations_within_8_km_with_gd_places_within_8_km_sorted_by_distance);

        unset($geolocations_within_8_km_with_gd_places_within_8_km_sorted_by_distance);
    }
    unset($geolocations_ids);
}

function get_geolocations_within_8_km_with_gd_places_within_8_km_sorted_by_distance_for_single_geolocation($geolocations_ids, $current_geolocation_id, $radius = 8)
{
    $distances = array();

    $current_lat = get_post_meta($current_geolocation_id, 'latitude', true);
    $current_long = get_post_meta($current_geolocation_id, 'longitude', true);

    $geolocations_ids = array_diff($geolocations_ids, [$current_geolocation_id]);

    foreach ($geolocations_ids as $geolocation_id) {
        $gd_places_within_8_km = get_gd_places_within_radius($current_geolocation_id, 8);

        if (empty($gd_places_within_8_km)) {
            continue;
        }
        unset($gd_places_within_8_km);

        $lat = get_post_meta($geolocation_id, 'latitude', true);
        $long = get_post_meta($geolocation_id, 'longitude', true);

        $theta = $current_long - $long;

        $dist = sin(deg2rad($current_lat)) * sin(deg2rad($lat)) +  cos(deg2rad($current_lat)) * cos(deg2rad($lat)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $kilometers = $dist * 60 * 1.852;
        $kilometers = number_format($kilometers, 5);



        $distances[$geolocation_id] = $kilometers;
        unset($lat, $long, $kilometers, $theta, $dist, $gd_place_ids_list, $gd_places_within_8_km, $all_gd_places);
    }
    asort($distances);
    $distances = array_filter($distances, function ($distance) use ($radius) {
        return $distance <= $radius;
    });
    unset($geolocations_ids, $current_geolocation_id, $radius, $current_lat, $current_long);

    return $distances;
}


function set_gd_places_within_radius_sorted_by_distance_for_all_geolocations($radius)
{
    global $wpdb;
    $geodir_gd_place_detail_table = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}geodir_gd_place_detail", OBJECT);

    $all_gd_places = get_posts(array('post_type' => 'gd_place', 'posts_per_page' => -1));

    $filtered_geodir_gd_place_detail_table = [];
    foreach ($all_gd_places as $gd_place) {
        foreach ($geodir_gd_place_detail_table as $gd_place_detail) {
            if ($gd_place_detail->post_id == $gd_place->ID) {
                $filtered_geodir_gd_place_detail_table[] = $gd_place_detail;
            }
        }
    }

    $geolocations_ids = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1, 'fields' => 'ids'));

    if (empty($filtered_geodir_gd_place_detail_table)) {
        trigger_error("No gd_places_ids found", E_USER_WARNING);
        return;
    }

    if (empty($geolocations_ids)) {
        trigger_error("No geolocations_ids found", E_USER_WARNING);
        return;
    }

    foreach ($geolocations_ids as $current_geolocation_id) {
        $near_gd_place_list = get_gd_places_within_radius_sorted_by_distance_for_single_geolocation($filtered_geodir_gd_place_detail_table, $current_geolocation_id, $radius);
        update_post_meta($current_geolocation_id, 'all_gd_places_sorted_by_distance', $near_gd_place_list);

        $all_gd_places_sorted_by_distance_list = get_post_meta($current_geolocation_id, 'all_gd_places_sorted_by_distance', true);
        unset($gd_places_in_current_geodir, $gd_places_not_in_current_geodir, $near_gd_place_list, $all_gd_places_sorted_by_distance_list);
    }
    unset($geodir_gd_place_detail_table, $geolocations_ids, $filtered_geodir_gd_place_detail_table, $all_gd_places, $wpdb);
}


function get_gd_places_within_radius_sorted_by_distance_for_single_geolocation($filtered_geodir_gd_place_detail_table, $current_geolocation_id, $radius)
{
    $distances = array();

    $current_geolocation_lat = get_post_meta($current_geolocation_id, 'latitude', true);
    $current_geolocation_long = get_post_meta($current_geolocation_id, 'longitude', true);

    foreach ($filtered_geodir_gd_place_detail_table as $gd_place) {
        $lat = $gd_place->latitude;
        $long = $gd_place->longitude;

        $theta = $current_geolocation_long - $long;

        $dist = sin(deg2rad($current_geolocation_lat)) * sin(deg2rad($lat)) +  cos(deg2rad($current_geolocation_lat)) * cos(deg2rad($lat)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $kilometers = $dist * 60 * 1.852;
        $kilometers = number_format($kilometers, 5);

        $distances[$gd_place->post_id] = $kilometers;
        unset($lat, $long, $kilometers, $theta, $dist);
    }

    asort($distances);

    $distances = array_filter($distances, function ($distance) use ($radius) {
        return $distance <= $radius;
    });

    return $distances;
}

// function set_gd_places_within_radiuses_for_all_geolocations($radiuses)
// {
//     $geolocations_ids = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1, 'fields' => 'ids'));
//     foreach ($geolocations_ids as $current_geolocation_id) {
//         $all_gd_places_sorted_by_distance_list = get_post_meta($current_geolocation_id, 'all_gd_places_sorted_by_distance_list', true);
//         foreach ($radiuses as $radius) {
//             $within_radius = array_filter($all_gd_places_sorted_by_distance_list, function ($distance) use ($radius) {
//                 return $distance <= $radius;
//             });
//             update_post_meta($current_geolocation_id, 'gd_places_within_' . $radius . '_km', $within_radius);
//             $test = get_post_meta($current_geolocation_id, 'gd_places_within_' . $radius . '_km', true);
//         }
//     }
// }

function get_gd_places_within_radius($geolocation, $radius)
{
    $all_gd_places_sorted_by_distance = get_post_meta($geolocation, 'all_gd_places_sorted_by_distance', true);
    $gd_places_within_radius = array_filter($all_gd_places_sorted_by_distance, function ($distance) use ($radius) {
        return $distance <= $radius;
    });
    //$gd_places_within_radius = array_keys($all_gd_places_sorted_by_distance);

    return $gd_places_within_radius;
}

//seo gd place list is gd_places_within_8_km + geodir_neighbourhoods_gd_place_list
function generate_seo_gd_place_list_for_all_geolocations()
{
    $geolocations_ids = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1, 'fields' => 'ids'));
    foreach ($geolocations_ids as $geolocation_id) {
        $gd_places_within_8_km = get_gd_places_within_radius($geolocation_id, 8);

        $gd_places_in_neighbourhoods = get_gd_places_in_neighbourhoods($geolocation_id);

        $seo_gd_place_list = [];

        $seo_gd_place_list = $gd_places_within_8_km + $gd_places_in_neighbourhoods;

        $seo_gd_place_list = array_unique($seo_gd_place_list);
        asort($seo_gd_place_list);

        $seo_gd_place_list = array_keys($seo_gd_place_list);

        update_post_meta($geolocation_id, 'seo_gd_place_list', $seo_gd_place_list);
    }
}

function get_gd_places_in_neighbourhoods($geolocation_id)
{
    global $wpdb;
    $geodir_gd_place_detail_table = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}geodir_gd_place_detail", OBJECT);

    //get neighbourhoods
    $gd_neighbourhoods = get_post_meta($geolocation_id, 'geodir_neighbourhoods', false);
    if (empty($gd_neighbourhoods)) {
        return [];
    }

    //get gd_place_ids in neighbourhoods
    $gd_neighbourhoods_gd_place_ids = array();
    foreach ($gd_neighbourhoods as $gd_neighbourhood) {
        $gd_neighbourhood_gd_place_ids = get_post_meta($gd_neighbourhood, 'gd_place_list', false);
        $gd_neighbourhoods_gd_place_ids = array_merge($gd_neighbourhoods_gd_place_ids, $gd_neighbourhood_gd_place_ids);
    }
    if (empty($gd_neighbourhoods_gd_place_ids)) {
        return [];
    }
    $gd_neighbourhoods_gd_place_ids = array_unique($gd_neighbourhoods_gd_place_ids);

    //populate filtered_geodir_gd_place_detail_table
    $filtered_geodir_gd_place_detail_table = [];
    foreach ($gd_neighbourhoods_gd_place_ids as $gd_place_id) {
        foreach ($geodir_gd_place_detail_table as $gd_place_detail) {
            if ($gd_place_detail->post_id == $gd_place_id) {
                $filtered_geodir_gd_place_detail_table[] = $gd_place_detail;
            }
        }
    }
    if (empty($filtered_geodir_gd_place_detail_table)) {
        return [];
    }

    //find distance from geolocation for each gd_place
    $gd_places_in_neighbourhoods = [];
    foreach ($filtered_geodir_gd_place_detail_table as $gd_place) {
        $distance = find_distance_from_geolocation($geolocation_id, $gd_place);
        $gd_places_in_neighbourhoods[$gd_place->post_id] = $distance;
    }
    return $gd_places_in_neighbourhoods;
}

function find_distance_from_geolocation($geolocation, $gd_place)
{
    $geolocation_lat = get_post_meta($geolocation, 'latitude', true);
    $geolocation_long = get_post_meta($geolocation, 'longitude', true);

    $lat = $gd_place->latitude;
    $long = $gd_place->longitude;

    $theta = $geolocation_long - $long;

    $dist = sin(deg2rad($geolocation_lat)) * sin(deg2rad($lat)) +  cos(deg2rad($geolocation_lat)) * cos(deg2rad($lat)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $kilometers = $dist * 60 * 1.852;
    $kilometers = number_format($kilometers, 5);

    return $kilometers;
}

function generate_seo_schools()
{
    $geolocations_ids = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1, 'fields' => 'ids'));
    foreach ($geolocations_ids as $current_geolocation_id) {
        $gd_places_within_8_km = get_post_meta($current_geolocation_id, 'gd_places_within_8_km', true);
        $gd_places_within_8_km = array_slice($gd_places_within_8_km, 0, 5, true);
        $gd_places_within_8_km = array_keys($gd_places_within_8_km);
        $gd_places_within_8_km = array_map(function ($gd_place_id) {
            return get_post($gd_place_id);
        }, $gd_places_within_8_km);
        $gd_places_within_8_km = array_filter($gd_places_within_8_km, function ($gd_place) {
            return $gd_place->post_type == 'gd_place';
        });
        $gd_places_within_8_km = array_map(function ($gd_place) {
            return $gd_place->post_title;
        }, $gd_places_within_8_km);
        $gd_places_within_8_km = implode(', ', $gd_places_within_8_km);
        update_post_meta($current_geolocation_id, 'seo_schools', $gd_places_within_8_km);
    }
}
