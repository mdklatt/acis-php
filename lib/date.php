<?php
/**
 * ACIS date-handling functions.
 *
 * This module contains functions for converting ACIS date strings/to from PHP
 * DateTime objects, and a function to calculate a date range based on ACIS
 * call parameters.
 *
 * The client needs to define a default time zone or the DateTime functions
 * will throw an exeption:
 *     date_default_timezone_set('UTC'); 
 *
 */
 
 /**
 * Convert an ACIS date string to a DateTime object.
 *
 * Valid date formats are YYYY[-MM[-DD]] (hyphens are optional but leading
 * zeroes are not; no two-digit years).
 *
 */
function ACIS_dateObject($dateStr)
{
    $date_regex = '/^(\d{4})(?:-?(\d{2}))?(?:-?(\d{2}))?$/';
    if (preg_match($date_regex, $dateStr, $matches)) {
		$yr = $matches[1];
		$mo = count($matches) >= 3 ? $matches[2] : 1;
		$da = count($matches) >= 4 ? $matches[3] : 1;
	}
    else {
        throw new Exception('invalid date format');
    }
	$date = new DateTime();
	$date->setDate($yr, $mo, $da);
	$date->setTime(0, 0, 0);
	return $date;
}

/**
 * Return an ACIS-format date string from a DateTime object.
 *
 */
function ACIS_dateString($dateObj)
{
   return $dateObj->format('Y-m-d');
}

/**
 * Determine the date delta for an interval.
 *
 * An interval can be a name ("dly", "mly", "yly") or a (yr, mo, da) value
 * given as a list/tuple or comma-delimited string. For (yr, mo, da) the least
 * significant nonzero value sets the interval, e.g. "0, 3, 0" is an interval
 * of 3 months.
 * 
 */
 function _ACIS_dateDelta($interval)
{
    $named_deltas = array(
        'dly' => array(0, 0, 1),
        'mly' => array(0, 1, 0),
        'yly' => array(1, 0, 0),
    );
    if (is_string($interval)) {
        if (array_key_exists($interval, $named_deltas)) {
            list($yr, $mo, $da) = $named_deltas[$interval];        
        }
        else {  // comma-delimited string?
            list($yr, $mo, $da) = explode(',', $interval);
        }
    }
    elseif (is_array($interval)) {
        list($yr, $mo, $da) = $interval;
    }
    else {
        throw new Exception("invalid interval specification: {$interval}");
    } 
    $da = (int)$da;
    $mo = $da > 0 ? 0 : (int)$mo;
    $yr = ($mo > 0 or $da > 0) ? 0 : (int)$yr;
    return "+{$yr} years, +{$mo} months, +{$da} days"; 
}


/**
 * Return an array of dates for the date range specified by params.
 *
 * The params parameter is an array of options defining an ACIS call. The
 * returned date range will be the dates for a result returned by that
 * call. This cannot be used for period-of-record ("por") date ranges.
 *
 * IN THE CURRENT IMPLEMENTATION THE RESULTS FOR A "GROUPBY" RESULT WILL NOT
 * BE CORRECT.
 *
 */
function ACIS_dateRange($params)
{
    if (array_key_exists('sdate', $params) and
            array_key_exists('edate', $params)) {
        $sdate = ACIS_dateObject($params['sdate']);
        $edate = ACIS_dateObject($params['edate']);
    }
    else if (array_key_exists('date', $params)) {
        return array($params['date']);  # single date
    }
    else {
        throw new Exception('invalid date range specification');
    }
    $elems = $params['elems'];
	if (is_array($elems) and array_key_exists('interval', $elems[0])) {
		$interval = $elems[0]['interval'];
	}
	else {
		$interval = 'dly';
	}
    $range = array();
    $delta = _ACIS_dateDelta($interval);
    while ($sdate <= $edate) {
        $range[] = ACIS_dateString($sdate);
        $sdate->modify($delta);
    }
    return $range;
}
