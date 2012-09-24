<?php
/*
 *
 *
 */
 require_once 'exception.php';
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


function ACIS_validInterval($value)
{
    if (is_array($value)) {
        list($yr, $mo, $da) = $value;
        $mo = $da > 0 ? 0 : $mo;
        $yr = ($mo > 0 or $da > 0) ? 0 : $yr;
        $value = array($yr, $mo, $da);
    }
    elseif (!in_array(strtolower($value), array('dly', 'mly', 'yly'))) {
        throw new ACIS_RequestException("invalid interval: {$value}");
    }
    return $value;
}


function ACIS_dateSpan($params)
{
    if (($sdate = @$params["sdate"]) === null) {
        $sdate = $params["date"];
    }
    if (($interval = @$params["elems"][0]["interval"]) === null) {
        $interval = "dly";
    }
    $edate = @$params["edate"];  // default is null
    return array($sdate, $edate, $interval);
}