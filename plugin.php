<?php
/*
Plugin Name: Referrer Plugin
Plugin URI: http://github.com/IWAtech/yourls-referrer-plugin
Description: Takes care of a few custom requirements (including campaign tracking based on referrer) of a project
Version: 1.0
Author: kl4n4
Author URI: http://github.com/kl4n4
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

define('KEYWORD_REGEX', "/(at|de|us)-([a-z0-9-]{8})/i");
define('LOCATION_REGEX', "/updatemi.com/i");
define('REFERRER_REGEX', "/(facebook).com|(twitter).com/i");

$requested_locale = null;
$tracking_campaign = null;

function referrer_get_locale_string($locale_short) {
    $country_code = strtoupper($locale_short);
    if(in_array($country_code, ['AT', 'DE'])) {
        return 'de_' . $country_code;
    } elseif(in_array($country_code, ['US'])) {
        return 'en_' . $country_code;
    }
    return null;
}

function referrer_sanitize_string( $valid, $string ) {
    global $requested_locale;
    if(preg_match(KEYWORD_REGEX, $valid, $matches) > 0) {
        $requested_locale = referrer_get_locale_string(@$matches[1]);
        return @$matches[2];
    }
    return $valid;
}

function referrer_redirect_location( $location, $code ) {
    global $requested_locale, $tracking_campaign;
    if(strpos($location, 'updatemi.com/') !== false) {
        if($_SERVER['HTTP_REFERER'] && preg_match(REFERRER_REGEX, $_SERVER['HTTP_REFERER'], $matches) > 0) {
            if(!empty(@$matches[1])) {
                $tracking_campaign = @$matches[1];
            }
        }
        $query_params = array(
            'pk_campaign' => 'share'
        );
        if($tracking_campaign) {
            $query_params['pk_kwd'] = $tracking_campaign;
        }
        if($requested_locale) {
            $query_params['locale'] = $requested_locale;
        }
        $query_clue = strpos($location, '?') === false ? '?' : '&';
        return $location . $query_clue . http_build_query($query_params);
    }
    return $location;
}

yourls_add_filter( 'sanitize_string', 'referrer_sanitize_string' );
yourls_add_filter( 'redirect_location', 'referrer_redirect_location' );
