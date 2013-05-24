<?php
/* Miscellaneous ACIS utility functions. */

/**
 * Return an associative array of site IDs keyed by their ID type.
 *
 * The sids parameter is an array of SIDs from ACIS metadata. There can be 
 * more than on SID per network type.
 */
function ACIS_decodeSids($sids)
{
    $SID_REGEX = '/^([^ ]*) (\d+)$/';
    $NETWORK_TYPES = array(        
         1 => 'WBAN',      2 => 'COOP',      3 => 'FAA',       4 => 'WMO',       
         5 => 'ICAO',      6 => 'GHCN',      7 => 'NWSLI',     8 => 'RCC',  
         9 => 'ThreadEx',  9 => 'ThreadEx', 10 => 'CoCoRaHS', 16 => 'AWDN',
        29 => 'SNOTEL',
    );
    $table = array();
    foreach ($sids as $sid) {
        if (!preg_match($SID_REGEX, $sid, $matches)) {
            throw new InvalidArgumentException("invalid sid: {$sid}");
        }
        list(, $ident, $ntype) = $matches;
        $network = ACIS_arrayGetKey($NETWORK_TYPES, $ntype, $ntype);
        $sid_list = &ACIS_arraySetKey($table, $network, array());
        $sid_list[] = $ident;
    }
    return $table;
}
