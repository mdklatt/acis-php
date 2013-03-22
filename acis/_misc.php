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
            $item .= "_" . --$count;
        }
    }
    return array_reverse($annotated);
}


/**
 * Construct an element array.
 *
 * The elem parameter can be an element name, a var major (vX) code, or an
 * associative array. An element can have a user-specified alias assigned to
 * the "alias" key if elem is an array. Otherwise, the alias will be the
 * element name or "vxN" for var major code N. 
 */
function ACIS_makeElement($elem)
{
    if (!is_array($elem)) {
        if (is_numeric($elem)) {
            $elem = array('vX' => (int)$elem);
        }
        else {
            $elem = array('name' => strtolower($elem));
        }
    }
    if (!array_key_exists('alias', $elem)) {
        $elem['alias'] = ($vx = @$elem['vX']) ? "vx{$vx}" : $elem['name'];
    }
    return $elem;
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
    if (!($sdate = ACIS_arrayGetKey($params, "sdate"))) {
        $sdate = $params["date"];
    }
    $interval = ACIS_arrayGetKey($params['elems'][0], 'interval', 'dly');
    $edate = ACIS_arrayGetKey($params, "edate");
    return array($sdate, $edate, $interval);
}


/**
 * Return array value by key or default value if key doesn't exist.
 */
function ACIS_arrayGetKey($arr, $key, $default=null)
{
    return array_key_exists($key, $arr) ? $arr[$key] : $default;
}


/**
 * Return array value by key and remove the key from the array.
 *
 * The default is returned if key is not in arr. If arr is an indexed array it
 * will need to be reindexed.
 */
function ACIS_arrayPopKey(&$arr, $key, $default=null)
{
    $value = $default;
    if (array_key_exists($key, $arr)) {
        $value = $arr[$key];
        unset($arr[$key]);
    }
    return $value;
}


/**
 * Return array value by key and set to default if key doesn't already exist.
 */
function ACIS_arraySetKey(&$arr, $key, $default=null)
{
    if (!array_key_exists($key, $arr)) {
        $arr[$key] = $default;
    }
    return $arr[$key];
}
