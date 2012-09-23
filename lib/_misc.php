<?php
/*
 *
 *
 */
 require_once 'date.php';
 
 
function ACIS_dateParams($sdate, $edate=null)
{
    $params = array();
    if (strcasecmp($sdate, "por") != 0) { // validate date
        $sdate = ACIS_dateString(ACIS_dateObject($sdate));        
    }
    if (!$edate) {
        if ($sdate == "por") {  // period of record
            $params["sdate"] = $params["edate"] = "por";
        }
        else {  # single date
            $params["date"] = $sdate;
        }
    }
    else {
        if (strcasecmp($edate, "por") != 0) {  // validate date
            $edate = ACIS_dateString(ACIS_dateObject($edate));        
        }
        $params["sdate"] = $sdate;
        $params["edate"] = $edate;
    }
    return $params;    
}