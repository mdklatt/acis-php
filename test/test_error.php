<?php
/**
 * PHPUnit tests for error.php.
 *
 * The tests can be executed using a PHPUnit test runner, e.g. the phpunit
 * command.
 */
require_once 'error.php';


abstract class _ErrorTest extends PHPUnit_Framework_TestCase
{
	protected $_ERROR_CLASS;

	protected function setUp()
	{
		$this->_message = "test error message";
		return;
	}

	public function testMessage()
	{
		$error = new $this->_ERROR_CLASS($this->_message);
		$this->assertEquals($error->getMessage(), $this->_message);
		return;
	}

	public function testThrow()
	{
		$this->setExpectedException($this->_ERROR_CLASS);
		throw new $this->_ERROR_CLASS($this->_message);
		return;
	}
}


class ParameterErrorTest extends _ErrorTest
{
    protected $_ERROR_CLASS = 'ACIS_ParameterError';
}


class ResultErrorTest extends _ErrorTest
{
    protected $_ERROR_CLASS = 'ACIS_ResultError';
}


class RequestErrorTest extends _ErrorTest
{
    protected $_ERROR_CLASS = 'ACIS_RequestError';
}
