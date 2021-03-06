<?php
/**
 * PHPUnit tests for date.php.
 *
 * The tests can be executed using a PHPUnit test runner, e.g. the phpunit
 * command.
 */
require_once 'acis.php';


class WebServicesCallTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $test_data = json_decode(file_get_contents('data/StnData.json'), true);
        $this->_params = $test_data['params'];
        $this->_result = $test_data['result'];
        $this->_call = new ACIS_WebServicesCall('StnData');
        return;
    }

    public function testUrl()
    {
        $url = 'http://data.rcc-acis.org/StnData';
        $this->assertEquals($this->_call->url, $url);
        return;
    }

    public function testCall()
    {
        $result = $this->_call->execute($this->_params);
        $this->assertEquals($result, $this->_result);
        return;
    }

    public function testException()
    {
        $this->setExpectedException('ACIS_RequestException', 'Need sId');
        $this->_call->execute(array('elems' => 'mint'));
        return;
    }
}
