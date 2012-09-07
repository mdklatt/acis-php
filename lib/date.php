<?php

require_once 'error.php';

function ACIS_parse_date($date)
{
    $date_regex = '/^(\d{4})(?:-?(\d{2}))?(?:-?(\d{2}))?$/';
    if (preg_match($date_regex, $date, $matches)) {
		$y = $matches[1];
		$m = count($matches) >= 3 ? $matches[2] : 1;
		$d = count($matches) >= 4 ? $matches[3] : 1;
	}
    else {
        throw new Exception('invalid date format');
    }
	$date = new DateTime();
	$date->setDate($y, $m, $d);
	$date->setTime(0, 0, 0);
	return $date;
}


function ACIS_format_date($date)
{
   return $date->format('Y-m-d');
}


function ACIS_date_range($params)
{
    if (array_key_exists('sdate', $params) &&
            array_key_exists('edate', $params)) {
        $sdate = ACIS_parse_date($params['sdate']);
        $edate = ACIS_parse_date($params['edate']);
    }
    else if (array_key_exists('date', $params)) {
        return array($params['date']);  # single date
    }
    else {
        throw ACIS_ParameterError("invalid date range specification");
    }
    $elems = $params['elems'];
	if (is_array($elems) && array_key_exists('interval', $elems[0])) {
		$interval = $elems[0]['interval'];
	}
	else {
		$interval = 'dly';
	}
    $deltas = array('dly'=>'+1 days', 'mly'=>'+1 months', 'yly'=>'+1 years');
    $range = array();
    while ($sdate <= $edate) {
        $range[] = ACIS_format_date($sdate);
        $sdate->modify($deltas[$interval]);
    }
    return $range;
}
    