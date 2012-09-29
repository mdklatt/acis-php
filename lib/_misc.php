<?php
/**
 * Miscellaneous implemetation functions.
 */
 require_once 'exception.php';
 require_once 'date.php';
 
 
 /**
  * Annotate duplicate strings in an array to make them unique.
  *
  * Duplicate items will be indexed, e.g. (abc0, abc1, ...). The original order
  * of the array is presevered.
  */
function ACIS_annotate($arr)
{
    // Reverse the array, count the duplicates, use the duplicate count as an
    // an index while it is decremented, then reverse the array again to 
    // restore the original order.
    $annotated = array_reverse($arr);
    foreach(array_count_values($annotated) as $key => $count) {
        if ($count == 1) {  // no duplicates
            continue;
        }
        foreach ($annotated as &$item) {
            if ($item != $key) {
                continue;
            }
            $item .= --$count;
        }
    }
    return array_reverse($annotated);
}

/**
 * Define the date parameters for a call.
 *
 * If edate is null sdate is treated as a single date. Both parameters must be
 * a date strig or "por" (period of record). Acceptable date formats are 
 * YYYY-[MM-[DD]] (hyphes are optional but leading zeroes are not; no two-digit
 * years).
 */
function ACIS_dateParams($sdate, $edate=null)
{
    $params = array();
    if (strcasecmp($sdate, "por") != 0) { // validate date
        $sdate = ACIS_dateString(ACIS_dateObject($sdate));        
    }
    if (!$edate) {  // single date or POR
        if ($sdate == "por") {  // period of record
            $params["sdate"] = $params["edate"] = "por";
        }
        else {  # single date
            $params["date"] = $sdate;
        }
    }
    else {  // date range
        if (strcasecmp($edate, "por") != 0) {  // validate date
            $edate = ACIS_dateString(ACIS_dateObject($edate));        
        }
        $params["sdate"] = $sdate;
        $params["edate"] = $edate;
    }
    return $params;    
}


/**
 * Return a valid ACIS date interval.
 *
 * An interval can be specified as a name ("dly", "mly", "yly") or an array of
 * three integers specifiying a year, month, and day. For an array only the
 * least-significant nonzero value is used.
 */
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


/**
 * Determine the start date, end date and interval for a call.
 *
 * If there is no end date it will be null. If there is no interval it will be
 * "dly".
 */
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