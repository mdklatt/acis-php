<?php
/**
 * PHPUnit tests for error.php.
 *
 * The tests can be executed using a PHPUnit test runner, e.g. the phpunit
 * command.
 */
require_once 'acis.php';


abstract class _ExceptionTest extends PHPUnit_Framework_TestCase
{
	protected $_TEST_CLASS;

	protected function setUp()
	{
		$this->_message = "test error message";
		return;
	}

	public function testMessage()
	{
		$exception = new $this->_TEST_CLASS($this->_message);
		$this->assertEquals($exception->getMessage(), $this->_message);
		return;
	}

	public function testThrow()
	{
		$this->setExpectedException($this->_TEST_CLASS);
		throw new $this->_TEST_CLASS($this->_message);
		return;
	}
}


class RequestExceptionTest extends _ExceptionTest
{
    protected $_TEST_CLASS = 'ACIS_RequestException';
}


class ResultExceptionTest extends _ExceptionTest
{
    protected $_TEST_CLASS = 'ACIS_ResultException';
}
