<?php
/* Unit testing for error.php.

*/

require_once 'unittest.php';
require_once 'libpath.php';
require_once 'error.php';


abstract class _TestError extends UnitTest_TestCase
{   
    private $_message;
    
    protected function setUp()
    {
        $this->_message = "_TestError error message";
        return;
    }
    
    public function testInit() 
    {
        $error = new $this->_ERROR_CLASS($this->_message);
        assert($error->getMessage() == $this->_message);
        return;
    }
}


class TestParameterError extends _TestError
{
    protected $_ERROR_CLASS = 'ACIS_ParameterError';
}


class TestResultError extends _TestError
{
    protected $_ERROR_CLASS = 'ACIS_ResultError';
}


class TestRequestError extends _TestError
{
    protected $_ERROR_CLASS = 'ACIS_RequestError';
}


$suite = new UnitTest_TestSuite();
$suite->addTests('TestParameterError');
$suite->addTests('TestResultError');
$suite->addTests('TestRequestError');
$suite->run();