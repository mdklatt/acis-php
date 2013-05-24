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
    $network_types = array(        
         1 => 'WBAN',      2 => 'COOP',      3 => 'FAA',       4 => 'WMO',       
         5 => 'ICAO',      6 => 'GHCN',      7 => 'NWSLI',     8 => 'RCC',  
         9 => 'ThreadEx',  9 => 'ThreadEx', 10 => 'CoCoRaHS', 16 => 'AWDN',
        29 => 'SNOTEL',
    );
    $table = array();
    foreach ($sids as $sid) {
        @list($ident, $ntype) = explode(" ", $sid);
        if ($ntype === null) {
            throw new InvalidArgumentException("invalid sid: {$sid}"); 
        }
        $network = ACIS_arrayGetKey($network_types, $ntype, $ntype);
        $sid_list = &ACIS_arraySetKey($table, $network, array());
        $sid_list[] = $ident;
    }
    return $table;
}
