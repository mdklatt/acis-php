<?php

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


function ACIS_dateString($dateObj)
{
   return $dateObj->format('Y-m-d');
}


function ACIS_dateRange($params)
{
    if (array_key_exists('sdate', $params) &&
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
	if (is_array($elems) && array_key_exists('interval', $elems[0])) {
		$interval = $elems[0]['interval'];
	}
	else {
		$interval = 'dly';
	}
    $deltas = array('dly'=>'+1 days', 'mly'=>'+1 months', 'yly'=>'+1 years');
    $range = array();
    while ($sdate <= $edate) {
        $range[] = ACIS_dateString($sdate);
        $sdate->modify($deltas[$interval]);
    }
    return $range;
}
    