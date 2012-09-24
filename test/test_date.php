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
 * Unit testing for the ACIS_dateTrunc function.
 *
 */
class DateTruncFunctionTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test "mly" interval.
     *
     */
    public function testMly()
    {
        $this->assertEquals('2011-12', ACIS_dateTrunc('20111215', 'mly'));
        $this->assertEquals('2011-12', ACIS_dateTrunc('2011-12-15', 'mly'));
        $this->assertEquals('2011-12', ACIS_dateTrunc('2011-12', 'mly'));
        $this->assertEquals('2011-01', ACIS_dateTrunc('2011', 'mly'));
        return;       
    }

    /**
     * Test "yly" interval.
     *
     */
    public function testYly()
    {
        $this->assertEquals('2011', ACIS_dateTrunc('20111215', 'yly'));
        $this->assertEquals('2011', ACIS_dateTrunc('2011-12-15', 'yly'));
        $this->assertEquals('2011', ACIS_dateTrunc('2011-12', 'yly'));
        $this->assertEquals('2011', ACIS_dateTrunc('2011', 'yly'));
        return;       
    }

    /**
     * Test "yly" interval.
     *
     */
    public function testOther()
    {
        $this->assertEquals('2011-12-15', ACIS_dateTrunc('2011-12-15', 
                array(0, 1, 0)));
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
     * Test a daily default interval.
     *
     */
    public function testDefaults()
    {
        $expected = array('2011-12-31');
        $actual = ACIS_dateRange('2011-12-31');
        $this->assertEquals($expected, $actual);
        return;
    }   

    /**
     * Test interval 'dly'.
     *
     */
    public function testDailyStr()
    {
        $expected = array('2011-12-31', '2012-01-01', '2012-01-02');
        $actual = ACIS_dateRange('2011-12-31', '2012-01-02', 'dly');
        $this->assertEquals($expected, $actual);
        return;
    }   
    
    /**
     * Test interval array(0, 0, 2).
     *
     */
    public function testDailyYmd()
    {
        $expected = array('2011-12-31', '2012-01-02', '2012-01-04');
        $actual = ACIS_dateRange('2011-12-31', '2012-01-04', array(0, 0, 2));
        $this->assertEquals($expected, $actual);
        return;
    }

    /**
     * Test interval "mly".
     *
     */
    public function testMonthlyStr()
    {
        $expected = array('2011-12', '2012-01', '2012-02');
        $actual = ACIS_dateRange('2011-12-15', '2012-02-15', 'mly');
        $this->assertEquals($expected, $actual);
        return;
    }   


    /**
     * Test interval array(0, 2, 0).
     *
     */
    public function testMonthlyYmd()
    {
        $expected = array('2011-12-15', '2012-02-15', '2012-04-15');
        $actual = ACIS_dateRange('2011-12-15', '2012-04-15', array(0, 2, 0));
        $this->assertEquals($expected, $actual);
        return;
    }   

    /**
     * Test interval 'yly'.
     *
     */
    public function testYearlyStr()
    {
        $expected = array('2011', '2012', '2013');
        $actual = ACIS_dateRange('2011-12-15', '2013-12-15', 'yly');
        $this->assertEquals($expected, $actual);
        return;
    }   

    /**
     * Test interval array(2, 0, 0).
     *
     */
    public function testYearlyYmd()
    {
        $expected = array('2011-12-15', '2013-12-15', '2015-12-15');
        $actual = ACIS_dateRange('2011-12-15', '2015-12-15', array(2, 0, 0));
        $this->assertEquals($expected, $actual);
        return;
    }   
}