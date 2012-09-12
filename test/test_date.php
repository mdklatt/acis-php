<?php
/**
 * PHPUnit tests for date.php.
 *
 * The tests can be executed using a PHPUnit test runner, e.g. the phpunit
 * command.
 */
require_once 'date.php';


class DateObjectFunctionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Unit testing for the ACIS_dateObject function.
	 *
	 */

	public function testDayHyphen()
	{
        /**
         * Test a YYYY-MM-DD string.
		 *
		 */
        $string = '2011-12-31';
        $object = new DateTime();
        $object->setDate(2011, 12, 31);
        $object->setTime(0, 0, 0);
        $this->assertEquals(ACIS_DateObject($string), $object);
        return;
    }

	public function testDayNoHyphen()
	{
        /**
         * Test a YYYYMMDD string.
		 *
		 */
        $string = '20111231';
        $object = new DateTime();
        $object->setDate(2011, 12, 31);
        $object->setTime(0, 0, 0);
        $this->assertEquals(ACIS_DateObject($string), $object);
        return;
    }

	public function testMonthHyphen()
	{
        /**
         * Test a YYYY-MM string.
		 *
		 */
        $string = '2011-12';
        $object = new DateTime();
        $object->setDate(2011, 12, 1);
        $object->setTime(0, 0, 0);
        $this->assertEquals(ACIS_DateObject($string), $object);
        return;
    }

	public function testMonthNoHyphen()
	{
        /**
         * Test a YYYYMM string.
		 *
		 */
        $string = '201112';
        $object = new DateTime();
        $object->setDate(2011, 12, 1);
        $object->setTime(0, 0, 0);
        $this->assertEquals(ACIS_DateObject($string), $object);
        return;
    }

	public function testYear()
	{
        /**
         * Test a YYYY string.
		 *
		 */
        $string = '2011';
        $object = new DateTime();
        $object->setDate(2011, 1, 1);
        $object->setTime(0, 0, 0);
        $this->assertEquals(ACIS_DateObject($string), $object);
        return;
    }
}


class DateStringFunctionTest extends PHPUnit_Framework_TestCase
{
	/**
     * Unit testing for the ACIS_dateString function.
     *
     */
    public function testNormal()
    {
        /** 
         * Test normal operation.
		 *
		 */
        $object = new DateTime();
        $object->setDate(2012, 1, 1);
        $object->setTime(0, 0, 0);
        $string = "2012-01-01";  // test for zero fill
        $this->assertEquals(ACIS_DateString($object), $string);
        return;
    }
}


class DateRangeFunctionTest extends PHPUnit_Framework_TestCase
{
	/**
     * Unit testing for the ACIS_dateRange function.
     *
     */
	public function testDefault()
	{
		/**
		 * Test a default daily interval.
		 *
		 */
		$params = array('sdate' => '2011-12-31', 'edate' => '2012-01-01',
				'elems' => 'mint');  // no interval given
		$dates = array('2011-12-31', '2012-01-01');
		$this->assertEquals(ACIS_dateRange($params), $dates);
	}	

	public function testDaily()
	{
		/**
		 * Test a daily ("dly") interval.
		 *
		 */
		$params = array('sdate' => '2011-12-31', 'edate' => '2012-01-01',
				'elems' => 'mint', 'interval' => 'dly');
		$dates = array('2011-12-31', '2012-01-01');
		$this->assertEquals(ACIS_dateRange($params), $dates);
	}	

	public function testMonthly()
	{
		/**
		 * Test a monthly ("mly") interval.
		 *
		 */
		$params = array('sdate' => '2011-12', 'edate' => '2012-01',
			'elems' => array(array('name' => 'mint', 'interval' => 'mly')));
		$dates = array('2011-12-01', '2012-01-01');
		$this->assertEquals(ACIS_dateRange($params), $dates);
	}	

	public function testYearly()
	{
		/**
		 * Test a monthly ("yly") interval.
		 *
		 */
		$params = array('sdate' => '2011', 'edate' => '2012',
			'elems' => array(array('name' => 'mint', 'interval' => 'yly')));
		$dates = array('2011-01-01', '2012-01-01');
		$this->assertEquals(ACIS_dateRange($params), $dates);
	}	

	public function testBadParams()
	{
		/**
		 * Test exception for invalid date range specification.
		 *
		 */
		$params = array('sdate' => '2012-01-01', 'elems' => 'mint');
		$message = 'invalid date range specification';
		$this->setExpectedException('ACIS_ParameterError', $message);
		ACIS_dateRange($params);
		return;
	}	
}

/*
    def test_bad_params(self):
        """ Test exception for invalid date range specification.

        """
        params = {"sdate": "2013-01-01", "elems": "mint"}  # no edate
        with self.assertRaises(ParameterError) as context:
            list(date_range(params))  # list needed to trigger iteration
        message = "invalid date range specification"
        self.assertEqual(context.exception.message, message)
        return
*/