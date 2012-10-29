<?php
/* Miscellaneous ACIS utility functions. */

/**
 * Return an associative array of site IDs keyed by their ID type.
 *
 * The sids parameter is an array of SIDs from ACIS metadata.
 */
function ACIS_sidsTable($sids)
{
    $SID_REGEX = '/^([^ ]*) (\d+)$/';
    $SID_TYPES = array(1=>'WBAN', 'COOP', 'FAA', 'WMO', 'ICAO', 'GHCN',
        'NWSLI', 'RCC', 'ThreadEx', 'CoCoRaHS');
    $table = array();
    foreach ($sids as $sid) {
        if (!preg_match($SID_REGEX, $sid, $matches)) {
            throw new InvalidArgumentException("invalid sid: {$sid}");
        }
        list(, $ident, $code) = $matches;
        if (!($sid_type = ACIS_arrayGetKey($SID_TYPES, $code))) {
            throw new InvalidArgumentException("invalid sid type: {$code}");
        }
        $table[$sid_type] = $ident;
    }
    return $table;
}