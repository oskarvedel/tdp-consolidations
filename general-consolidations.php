<?php

function general_consolidations()
{
    set_gd_place_list_for_special_geolocations();
    find_duplicate_geolocations();
    // add_gd_places_from_neighbourhoods_to_gd_place_list();
    trigger_error("general consolidations done", E_USER_NOTICE);
}

function find_duplicate_geolocations()
{
    $geolocations = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1));
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

//seems to be doing the same as update_gd_place_list_for_single_geolocation in geodir_consolidations, but will be needed at time of removing geodir
function add_gd_places_from_neighbourhoods_to_gd_place_list()
{
    $geolocations_ids = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1, 'fields' => 'ids'));
    foreach ($geolocations_ids as $current_geolocation_id) {
        $neighbourhoods = get_post_meta($current_geolocation_id, 'geodir_neighbourhoods', false);
        $all_neighbourhoods_gd_place_ids = array();
        foreach ($neighbourhoods as $neighbourhood) {
            $neighbourhood_gd_place_ids = get_post_meta($neighbourhood, 'gd_place_list', false);
            $all_neighbourhoods_gd_place_ids = array_merge($all_neighbourhoods_gd_place_ids, $neighbourhood_gd_place_ids);
        }
        $all_neighbourhoods_gd_place_ids = array_unique($all_neighbourhoods_gd_place_ids);
        $current_gd_place_ids_list = get_post_meta($current_geolocation_id, 'gd_place_list', false);

        $new_gd_place_ids_list = array_merge($current_gd_place_ids_list, $all_neighbourhoods_gd_place_ids);
        $new_gd_place_ids_list = array_unique($new_gd_place_ids_list);
        update_post_meta($current_geolocation_id, 'gd_place_list', $new_gd_place_ids_list);
    }
}

function set_gd_place_list_for_special_geolocations()
{
    $geolocations_ids = get_posts(array(
        'post_type' => 'geolocations',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'special_location',
                'value' => 1, // or whatever value you're looking for
                'compare' => '='
            )
        )
    ));

    $all_gd_places = get_posts(array(
        'post_type' => 'gd_place',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ));
    foreach ($geolocations_ids as $current_geolocation_id) {
        $geolocation_title = get_the_title($current_geolocation_id);
        if ($geolocation_title == "Danmark") {
            $current_gd_place_list = get_post_meta($current_geolocation_id, 'gd_place_list', false);
            // $new_gd_place_ids_list = array_merge($current_gd_place_ids_list, $all_gd_places);
            // $new_gd_place_ids_list = array_unique($new_gd_place_ids_list);
            update_post_meta($current_geolocation_id, 'gd_place_list', $all_gd_places);
        }
    }
}
