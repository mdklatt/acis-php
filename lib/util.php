<?php
/* Miscellaneous ACIS utility functions. */

$_ACIS_SID_TYPES = array(1=>'WBAN', 'COOP', 'FAA', 'WMO', 'ICAO', 'GHCN',
    'NWSLI', 'RCC', 'ThreadEx', 'CoCoRaHS');


function ACIS_decode_sids($sids)
{
    /* Return an array of site IDs keyed by there decoded ID types. */
    
    $sid_regex = '/(^[^ ]*) (\d+)$/';
    $decoded = array();
    foreach ($sids as $sid) {
        if (!preg_match($sid_regex, $sid, $matches)) {
            throw Exception("invalid sid: {$sid}");
        }
        list($ident, $code) = $matches;
        if (!array_key_exists($code, $_ACIS_SID_TYPES)) {
            throw Exception("uknknown sid type: {$code}");
        }
        $decoded[$_ACIS_SID_TYPES[$code]] = $ident;
    }
    return $decoded;
}