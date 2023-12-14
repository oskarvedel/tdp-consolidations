<?php

require_once(dirname(__FILE__) . '/general_consolidations.php');
require_once(dirname(__FILE__) . '/geodir_consolidations.php');

function consolidate_geolocations()
{
    general_consolidations();
    geodir_consolidations();
    trigger_error("consolidated geolocations", E_USER_NOTICE);
}


function send_email($body, $subject)
{
    $to = get_option('admin_email');
    $subject = $subject;
    $headers = 'From: system@tjekdepot.dk <system@tjekdepot.dk>' . "\r\n";

    wp_mail($to, $subject, $body, $headers);
}
