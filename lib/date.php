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
 define('ACIS_DATE_REGEX', '/^(\d{4})(?:-?(\d{2}))?(?:-?(\d{2}))?$/');


function ACIS_dateObject($date)
{
    if (!preg_match(ACIS_DATE_REGEX, $date, $matches)) {
        throw new Exception("invalid date: {$date}");
    }
	$yr = (int)$matches[1];
	$mo = count($matches) >= 3 ? $matches[2] : 1;
	$da = count($matches) >= 4 ? $matches[3] : 1;
	$date = new DateTime();
	$date->setDate($yr, $mo, $da);
	$date->setTime(0, 0, 0);
	return $date;
}

/**
 * Return an ACIS-format date string from a DateTime object.
 *
 */
function ACIS_dateString($date)
{
   return $date->format('Y-m-d');
}

/**
 * Determine the date delta for an interval.
 *
 * An interval can be a name ("dly", "mly", "yly") or a (yr, mo, da) array.
 * 
 */
 function ACIS_dateDelta($interval)
{
    $named_deltas = array(
        'dly' => array(0, 0, 1),
        'mly' => array(0, 1, 0),
        'yly' => array(1, 0, 0),
    );
    if (is_string($interval)) {
        if (($interval = @$named_deltas[$interval]) === null) {
            throw Exception("unknown interval: {$interval}");            
        }
    }
    list($yr, $mo, $da) = $interval;
    return "+{$yr} years, +{$mo} months, +{$da} days"; 
}


/**
 * Truncate a date to a defined precision.
 *
 * The only interval that have an effect are "yly" and "mly". For all other
 * intervals, include (y, m d) arrays, the precision daily.
 *
 */
function ACIS_dateTrunc($date, $interval)
{
    if (!preg_match(ACIS_DATE_REGEX, $date, $matches)) {
        throw new Exception("invalid date: {$date}");
    }
    $precision = array('yly' => 1, 'mly' => 2);    
    if (($prec = @$precision[$interval]) === null) {
        $prec = 3;
    }
    $date = array($matches[1]);
    if ($prec >= 2) {  // month
        $date[] = count($matches) >= 3 ? $matches[2] : "01";
    }
    if ($prec == 3) {  // day
        $date[] = count($matches) >= 4 ? $matches[3] : "01";
    }
    return implode('-', $date);
}


/**
 * Return an array of dates.
 *
 * The params parameter is an array of options defining an ACIS call. The
 * returned date range will be the dates for a result returned by that
 * call. This cannot be used for period-of-record ("por") date ranges.
 *
 * IN THE CURRENT IMPLEMENTATION THE RESULTS FOR A "GROUPBY" RESULT WILL NOT
 * BE CORRECT.
 *
 */
 function ACIS_dateRange($sdate, $edate=null, $interval="dly")
 {
     $edate = $edate == null ? $sdate : $edate ;
     $sdate = ACIS_dateObject(ACIS_dateTrunc($sdate, $interval));
     $edate = ACIS_dateObject(ACIS_dateTrunc($edate, $interval));
     $delta = ACIS_dateDelta($interval);
     $range = array();
     while ($sdate <= $edate) {
         $range[] = ACIS_dateTrunc(ACIS_dateString($sdate), $interval);
         $sdate->modify($delta);
     }
     return $range;
 }
