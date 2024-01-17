<?php

function seo_consolidations()
{
    set_gd_places_within_radius_sorted_by_distance_for_all_geolocations(40);
    trigger_error("set_gd_places_within_radius_sorted_by_distance_for_all_geolocations done", E_USER_NOTICE);
    generate_archive_gd_place_list_for_all_geolocations();
    trigger_error("generate_archive_gd_place_list_for_all_geolocations done", E_USER_NOTICE);
    generate_seo_gd_place_list_for_all_geolocations();
    trigger_error("generate_seo_gd_place_list_for_all_geolocations done", E_USER_NOTICE);
    set_first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance_for_all_geolocations();
    trigger_error("set_first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance_for_all_geolocations done", E_USER_NOTICE);

    //generate_seo_schools(); //to be developed
    //set_50_nearest_geolocations_sorted_by_distance_list_for_all_geolocations(); //to be developed

    trigger_error("All SEO consolidations done", E_USER_NOTICE);
}

function set_first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance_for_all_geolocations()
{
    $batch_size = 10; // Adjust this to a suitable size

    $geolocations_ids = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1, 'fields' => 'ids'));
    if (empty($geolocations_ids)) {
        trigger_error("No geolocations found", E_USER_WARNING);
        return;
    }

    // Split the geolocations into batches
    $batches = array_chunk($geolocations_ids, $batch_size);

    $counter = 0;

    // Loop through the batches
    foreach ($batches as $batch) {
        // Loop through the geolocations in the current batch
        foreach ($batch as $geolocation_id) {
            $counter++;
            $first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance = get_first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance_for_single_geolocation($geolocations_ids, $geolocation_id);
            $first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance = array_keys($first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance);
            if (empty($first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance)) {
                continue;
            }

            $current_first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance = get_post_meta($geolocation_id, 'first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance', false);
            $current_first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance_ids = array_map(function ($current_first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance) {
                return $current_first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance['ID'];
            }, $current_first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance);
            $current_first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance_ids = array_map('intval', $current_first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance_ids);
            if ($first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance == $current_first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance_ids) {
                continue;
            }
            $post_title = get_the_title($geolocation_id);
            update_post_meta($geolocation_id, 'first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance', $first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance);
            trigger_error("set new first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance for single geolocation: " . $post_title, E_USER_NOTICE);

            unset($first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance, $current_first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance);
        }
    }
}

function get_first_10_geolocations_within_8_km_with_seo_gd_place_list_sorted_by_distance_for_single_geolocation($geolocations_ids, $current_geolocation_id, $radius = 8)
{
    $distances = array();

    $current_lat = get_post_meta($current_geolocation_id, 'latitude', true);
    $current_long = get_post_meta($current_geolocation_id, 'longitude', true);

    $geolocations_ids = array_diff($geolocations_ids, [$current_geolocation_id]);

    foreach ($geolocations_ids as $geolocation_id) {
        $seo_gd_place_list = get_post_meta($geolocation_id, 'seo_gd_place_list', true);

        if (empty($seo_gd_place_list)) {
            continue;
        }
        unset($seo_gd_place_list);

        $lat = get_post_meta($geolocation_id, 'latitude', true);
        $long = get_post_meta($geolocation_id, 'longitude', true);

        if (empty($lat) || empty($long)) {
            trigger_error("No lat or long found for geolocation_id: $geolocation_id", E_USER_WARNING);
            continue;
        }

        $theta = $current_long - $long;

        $dist = sin(deg2rad($current_lat)) * sin(deg2rad($lat)) +  cos(deg2rad($current_lat)) * cos(deg2rad($lat)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $kilometers = $dist * 60 * 1.852;
        $kilometers = number_format($kilometers, 5);

        $distances[$geolocation_id] = $kilometers;
        unset($lat, $long, $kilometers, $theta, $dist);
    }
    asort($distances);
    $distances = array_filter($distances, function ($distance) use ($radius) {
        return $distance <= $radius;
    });

    $distances = array_slice($distances, 0, 10, true);

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
        $all_gd_places_sorted_by_distance = get_gd_places_within_radius_sorted_by_distance_for_single_geolocation($filtered_geodir_gd_place_detail_table, $current_geolocation_id, $radius);
        $all_gd_places_sorted_by_distance_display = array_keys($all_gd_places_sorted_by_distance);
        if (empty($all_gd_places_sorted_by_distance)) {
            continue;
        }
        update_post_meta($current_geolocation_id, 'all_gd_places_sorted_by_distance', $all_gd_places_sorted_by_distance);
        update_post_meta($current_geolocation_id, 'all_gd_places_sorted_by_distance_display', $all_gd_places_sorted_by_distance_display);
        unset($all_gd_places_sorted_by_distance, $all_gd_places_sorted_by_distance_display);
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

function get_gd_places_within_radius($geolocation, $radius)
{
    $all_gd_places_sorted_by_distance = get_post_meta($geolocation, 'all_gd_places_sorted_by_distance', true);
    if (empty($all_gd_places_sorted_by_distance)) {
        return [];
    }
    $gd_places_within_radius = array_filter($all_gd_places_sorted_by_distance, function ($distance) use ($radius) {
        return $distance <= $radius;
    });
    //$gd_places_within_radius = array_keys($all_gd_places_sorted_by_distance);

    return $gd_places_within_radius;
}

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

function generate_archive_gd_place_list_for_all_geolocations()
{
    $geolocations_ids = get_posts(array('post_type' => 'geolocations', 'posts_per_page' => -1, 'fields' => 'ids'));
    foreach ($geolocations_ids as $geolocation_id) {

        // if ($geolocation_id == 6078) {
        //     xdebug_break();
        // }

        //get all gd_places for geolocation
        $gd_place_list = get_post_meta($geolocation_id, 'gd_place_list', false);
        $gd_place_list_ids = array_map(function ($item) {
            return intval($item['ID']); // or return $item['id']; if $item is an array
        }, $gd_place_list);

        //get all gd_places within 2 km
        $gd_places_within_2_km = get_gd_places_within_radius($geolocation_id, 2);
        $gd_places_within_2_km_ids = array_keys($gd_places_within_2_km);

        //get all gd_places in neighbourhoods
        $gd_places_in_neighbourhoods = get_gd_places_in_neighbourhoods($geolocation_id);
        $gd_places_in_neighbourhoods_ids = array_keys($gd_places_in_neighbourhoods);

        //combine all gd_place lists
        $archive_gd_place_list = [];
        $archive_gd_place_list = array_merge($gd_place_list_ids, $gd_places_within_2_km_ids, $gd_places_in_neighbourhoods_ids);
        $archive_gd_place_list = array_unique($archive_gd_place_list);

        //remove any gd_places that has show_listing set to 0
        // $archive_gd_place_list = array_filter($archive_gd_place_list, function ($gd_place_id) {
        //     return get_post_meta($gd_place_id, 'show_listing', true) == 1;
        // });

        //sort all the partner gd_places to the top
        $archive_gd_place_list = sort_partner_gd_places_to_top($archive_gd_place_list);

        //sort all featured gd_places to the top
        $archive_gd_place_list = sort_featured_gd_places_to_top($archive_gd_place_list);

        //if list is smaller than 10, add more gd_places
        if (count($archive_gd_place_list) < 10) {
            $archive_gd_place_list = add_extra_gd_places($archive_gd_place_list, $geolocation_id);
        }

        //remove any gd_places that has show_listing set to 0
        // $archive_gd_place_list = array_filter($archive_gd_place_list, function ($gd_place_id) {
        //     return get_post_meta($gd_place_id, 'show_listing', true) == 1;
        // });

        update_post_meta($geolocation_id, 'archive_gd_place_list', $archive_gd_place_list);
    }
}

function add_extra_gd_places($archive_gd_place_list, $geolocation_id)
{
    if (count($archive_gd_place_list) < 10) {
        $gd_places_within_radius = get_gd_places_within_radius($geolocation_id, 4);
        $gd_places_within_radius_ids = array_keys($gd_places_within_radius);
        // $gd_places_within_radius = array_filter($gd_places_within_radius, function ($gd_place_id) {
        //     return get_post_meta($gd_place_id, 'show_listing', true) == 1;
        // });
        $gd_places_within_radius_ids = array_diff($gd_places_within_radius_ids, $archive_gd_place_list);
        $gd_places_within_radius_ids = sort_partner_gd_places_to_top($gd_places_within_radius_ids);
        $archive_gd_place_list = array_merge($archive_gd_place_list, $gd_places_within_radius_ids);
        //for 4 km radius, sort the featured gd_places to the top of the main list
        $archive_gd_place_list = sort_featured_gd_places_to_top($archive_gd_place_list);
    }
    if (count($archive_gd_place_list) < 10) {
        $gd_places_within_radius = get_gd_places_within_radius($geolocation_id, 6);
        $gd_places_within_radius_ids = array_keys($gd_places_within_radius);
        // $gd_places_within_radius = array_filter($gd_places_within_radius, function ($gd_place_id) {
        //     return get_post_meta($gd_place_id, 'show_listing', true) == 1;
        // });
        $gd_places_within_radius_ids = array_diff($gd_places_within_radius_ids, $archive_gd_place_list);
        $gd_places_within_radius_ids = sort_partner_gd_places_to_top($gd_places_within_radius_ids);
        $gd_places_within_radius_ids = sort_featured_gd_places_to_top($gd_places_within_radius_ids);
        $archive_gd_place_list = array_merge($archive_gd_place_list, $gd_places_within_radius_ids);
    }
    if (count($archive_gd_place_list) < 10) {
        $gd_places_within_radius = get_gd_places_within_radius($geolocation_id, 8);
        $gd_places_within_radius_ids = array_keys($gd_places_within_radius);
        // $gd_places_within_radius = array_filter($gd_places_within_radius, function ($gd_place_id) {
        //     return get_post_meta($gd_place_id, 'show_listing', true) == 1;
        // });
        $gd_places_within_radius_ids = array_diff($gd_places_within_radius_ids, $archive_gd_place_list);
        $gd_places_within_radius_ids = sort_partner_gd_places_to_top($gd_places_within_radius_ids);
        $gd_places_within_radius_ids = sort_featured_gd_places_to_top($gd_places_within_radius_ids);
        $archive_gd_place_list = array_merge($archive_gd_place_list, $gd_places_within_radius_ids);
    }
    //slice the array to 10
    $archive_gd_place_list = array_slice($archive_gd_place_list, 0, 10);
    return $archive_gd_place_list;
}

function add_gd_places_within_radius($archive_gd_place_list, $geolocation_id, $radius)
{
    $gd_places_within_radius = get_gd_places_within_radius($geolocation_id, $radius);
    $gd_places_within_radius_ids = array_keys($gd_places_within_radius);
    $gd_places_within_radius_ids = array_diff($gd_places_within_radius_ids, $archive_gd_place_list);

    $archive_gd_place_list = array_merge($archive_gd_place_list, $gd_places_within_radius_ids);
    return $archive_gd_place_list;
}

function sort_partner_gd_places_to_top($archive_gd_place_list)
{
    usort($archive_gd_place_list, function ($a, $b) {
        $partnerA = get_post_meta($a, 'partner', true) === '1' ? 1 : 0;
        $partnerB = get_post_meta($b, 'partner', true) === '1' ? 1 : 0;

        if ($partnerA == $partnerB) {
            return 0;
        }

        return ($partnerA > $partnerB) ? -1 : 1;
    });
    return $archive_gd_place_list;
}
function sort_featured_gd_places_to_top($archive_gd_place_list)
{
    usort($archive_gd_place_list, function ($a, $b) {
        $featuredA = get_post_meta($a, 'featured', true) === '1' ? 1 : 0;
        $featuredB = get_post_meta($b, 'featured', true) === '1' ? 1 : 0;

        if ($featuredA == $featuredB) {
            return 0;
        }

        return ($featuredA > $featuredB) ? -1 : 1;
    });
    return $archive_gd_place_list;
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
    $gd_neighbourhoods_gd_places = array();
    foreach ($gd_neighbourhoods as $gd_neighbourhood) {
        $gd_neighbourhood_gd_places = get_post_meta($gd_neighbourhood['ID'], 'gd_place_list', false);
        if (empty($gd_neighbourhood_gd_places)) {
            continue;
        }
        $gd_neighbourhoods_gd_places = array_merge($gd_neighbourhoods_gd_places, $gd_neighbourhood_gd_places);
    }
    if (empty($gd_neighbourhoods_gd_places)) {
        return [];
    }
    $gd_neighbourhoods_gd_places = array_map('unserialize', array_unique(array_map('serialize', $gd_neighbourhoods_gd_places)));

    //populate filtered_geodir_gd_place_detail_table
    $filtered_geodir_gd_place_detail_table = [];
    foreach ($gd_neighbourhoods_gd_places as $gd_place) {
        foreach ($geodir_gd_place_detail_table as $gd_place_detail) {
            if ($gd_place_detail->post_id == $gd_place['ID']) {
                $filtered_geodir_gd_place_detail_table[] = $gd_place_detail;
            }
        }
    }
    if (empty($filtered_geodir_gd_place_detail_table)) {
        return [];
    }
    // foreach ($gd_neighbourhoods) {
    //     $gd_places_within_8_km = get_gd_places_within_radius($geolocation_id, 8);
    // }

    //find distance from geolocation for each gd_place
    $gd_neighbourhoods_gd_places = [];
    foreach ($filtered_geodir_gd_place_detail_table as $gd_place) {
        $distance = find_distance_from_geolocation($geolocation_id, $gd_place);
        $gd_neighbourhoods_gd_places[$gd_place->post_id] = $distance;
    }
    return $gd_neighbourhoods_gd_places;
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
