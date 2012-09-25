<?php
/* Miscellaneous ACIS utility functions. */


function ACIS_sidsTypes($sids)
{
    /* Return an array of site IDs keyed by their decoded ID types. */
    
    $SID_REGEX = '/^([^ ]*) (\d+)$/';
    $SID_TYPES = array(1=>'WBAN', 'COOP', 'FAA', 'WMO', 'ICAO', 'GHCN',
        'NWSLI', 'RCC', 'ThreadEx', 'CoCoRaHS');
    $types = array();
    foreach ($sids as $sid) {
        if (!preg_match($SID_REGEX, $sid, $matches)) {
            throw new Exception("invalid sid: {$sid}");
        }
        list(, $ident, $code) = $matches;
        if (!array_key_exists($code, $SID_TYPES)) {
            throw new Exception("uknknown sid type: {$code}");
        }
        $types[$SID_TYPES[$code]] = $ident;
    }
    return $types;
}