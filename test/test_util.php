<?php
/**
 * PHPUnit tests for util.php.
 *
 * The tests can be executed using a PHPUnit test runner, e.g. the phpunit
 * command.
 */
require_once 'acis.php';


class SidsTypesFunctionTest extends PHPUnit_Framework_TestCase
{
    public function testNormal()
    {
        /**
         * Test normal operation.
         *
         */
         $sids = array('13967 1', '346661 2');
         $types = array('WBAN' => '13967', 'COOP' => '346661');
         $this->assertEquals(ACIS_sidsTypes($sids), $types);
         return;
    }
}
