<?php
/**
 * PHPUnit tests for date.php.
 *
 * The tests can be executed using a PHPUnit test runner, e.g. the phpunit
 * command.
 */
require_once 'acis.php';


/**
 * Unit testing for the ACIS_dateObject function.
 *
 */
class DateObjectFunctionTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test a YYYY-MM-DD string.
     *
     */
    public function testDayHyphen()
    {
        $date_str = '2011-12-31';
        $date_obj = new DateTime();
        $date_obj->setDate(2011, 12, 31);
        $date_obj->setTime(0, 0, 0);
        $this->assertEquals($date_obj, ACIS_DateObject($date_str));
        return;
    }

    /**
     * Test a YYYYMMDD string.
     *
     */
    public function testDayNoHyphen()
    {
        $date_str = '20111231';
        $date_obj = new DateTime();
        $date_obj->setDate(2011, 12, 31);
        $date_obj->setTime(0, 0, 0);
        $this->assertEquals($date_obj, ACIS_DateObject($date_str));
        return;
    }

    /**
     * Test a YYYY-MM string.
     *
     */
    public function testMonthHyphen()
    {
        $date_str = '2011-12';
        $date_obj = new DateTime();
        $date_obj->setDate(2011, 12, 1);
        $date_obj->setTime(0, 0, 0);
        $this->assertEquals($date_obj, ACIS_DateObject($date_str));
        return;
    }

    /**
     * Test a YYYYMM string.
     *
     */
    public function testMonthNoHyphen()
    {
        $date_str = '201112';
        $date_obj = new DateTime();
        $date_obj->setDate(2011, 12, 1);
        $date_obj->setTime(0, 0, 0);
        $this->assertEquals($date_obj, ACIS_DateObject($date_str));
        return;
    }

    /**
     * Test a YYYY string.
     *
     */
    public function testYear()
    {
        $date_str = '2011';
        $date_obj = new DateTime();
        $date_obj->setDate(2011, 1, 1);
        $date_obj->setTime(0, 0, 0);
        $this->assertEquals($date_obj, ACIS_DateObject($date_str));
        return;
    }
}


/**
 * Unit testing for the ACIS_dateString function.
 *
 */
class DateStringFunctionTest extends PHPUnit_Framework_TestCase
{
    /** 
     * Test normal operation.
     *
     */
    public function testNormal()
    {
        $date_obj = new DateTime();
        $date_obj->setDate(2012, 1, 1);
        $date_obj->setTime(0, 0, 0);
        $date_str = "2012-01-01";  // test for zero fill
        $this->assertEquals($date_str, ACIS_DateString($date_obj));
        return;
    }
}


/**
 * Unit testing for the ACIS_dateRange function.
 *
 */
class DateRangeFunctionTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test a default daily interval.
     *
     */
    public function testDefault()
    {
        $params = array('sdate' => '2011-12-31', 'edate' => '2012-01-01', 
            'elems' => 'mint');
        $dates = array('2011-12-31', '2012-01-01');
        $this->assertEquals($dates, ACIS_dateRange($params));
    }   

    /**
     * Test a daily interval 'dly'.
     *
     */
    public function testDailyStr()
    {
        $params = array('sdate' => '2011-12-31', 'edate' => '2012-01-01',
                'elems' => array(array('interval' => 'dly')));
        $dates = array('2011-12-31', '2012-01-01');
        $this->assertEquals($dates, ACIS_dateRange($params));
    }   
    
    /**
     * Test daily interval '0, 0, 2'.
     *
     */
    public function testDailyYmdStr()
    {
        $params = array('sdate' => '2011-12-31', 'edate' => '2012-01-05',
                'elems' => array(array('interval' => '0, 0, 2')));
        $dates = array('2011-12-31', '2012-01-02', '2012-01-04');
        $this->assertEquals($dates, ACIS_dateRange($params));
    }

    /**
     * Test daily interval array(0, 0, 2).
     *
     */
    public function testDailyYmdArr()
    {
        $params = array('sdate' => '2011-12-31', 'edate' => '2012-01-05',
                'elems' => array(array('interval' => array(0, 0, 2))));
        $dates = array('2011-12-31', '2012-01-02', '2012-01-04');
        $this->assertEquals($dates, ACIS_dateRange($params));
    }

    /**
     * Test monthly interval "mly".
     *
     */
    public function testMonthlyStr()
    {
        $params = array('sdate' => '2011-12', 'edate' => '2012-01',
            'elems' => array(array('interval' => 'mly')));
        $dates = array('2011-12-01', '2012-01-01');
        $this->assertEquals($dates, ACIS_dateRange($params));
    }   

    /**
     * Test monthly interval '0, 2, 0'.
     *
     */
    public function testMonthlyYmdStr()
    {
        $params = array('sdate' => '2011-12', 'edate' => '2012-05',
            'elems' => array(array('interval' => '0, 2, 0')));
        $dates = array('2011-12-01', '2012-02-01', '2012-04-01');
        $this->assertEquals($dates, ACIS_dateRange($params));
    }   

    /**
     * Test monthly interval array(0, 2, 0).
     *
     */
    public function testMonthlyYmdArr()
    {
        $params = array('sdate' => '2011-12', 'edate' => '2012-05',
            'elems' => array(array('interval' => array(0, 2, 0))));
        $dates = array('2011-12-01', '2012-02-01', '2012-04-01');
        $this->assertEquals($dates, ACIS_dateRange($params));
    }   

    /**
     * Test a yearly interval 'yly'.
     *
     */
    public function testYearlyStr()
    {
        $params = array('sdate' => '2011', 'edate' => '2012',
            'elems' => array(array('interval' => 'yly')));
        $dates = array('2011-01-01', '2012-01-01');
        $this->assertEquals($dates, ACIS_dateRange($params));
    }   

    /**
     * Test a yearly interval '2, 0, 0'.
     *
     */
    public function testYearlyYmdStr()
    {
        $params = array('sdate' => '2011', 'edate' => '2016',
            'elems' => array(array('interval' => '2, 0, 0')));
        $dates = array('2011-01-01', '2013-01-01', '2015-01-01');
        $this->assertEquals($dates, ACIS_dateRange($params));
    }   

    /**
     * Test a yearly interval array(2, 0, 0).
     *
     */
    public function testYearlyYmdArr()
    {
        $params = array('sdate' => '2011', 'edate' => '2016',
            'elems' => array(array('interval' => array(2, 0, 0))));
        $dates = array('2011-01-01', '2013-01-01', '2015-01-01');
        $this->assertEquals($dates, ACIS_dateRange($params));
    }   

    /**
     * Test that y, m, d specifications are mutually exclusive.
     *
     * The least significant place take precedence.
     */
    public function testYmdMutex()
    {
        $params = array('sdate' => '2011-01-01', 'edate' => '2011-02-02',
                'elems' => array(array('interval' => '0, 1, 1')));
        $dates = array('2011-01-01', '2011-01-02');
        $this->assertEquals($dates, array_slice(ACIS_dateRange($params), 
            0, 2));
        $params = array('sdate' => '2011-01-01', 'edate' => '2012-02-01',
                'elems' => array(array('interval' => '1, 1, 0')));
        $dates = array('2011-01-01', '2011-02-01');
        $this->assertEquals($dates, array_slice(ACIS_dateRange($params), 
            0, 2));
        $params = array('sdate' => '2011-01-01', 'edate' => '2012-01-02',
                'elems' => array(array('interval' => '1, 0, 1')));
        $dates = array('2011-01-01', '2011-01-02');
        $this->assertEquals($dates, array_slice(ACIS_dateRange($params), 
            0, 2));
    }
}