<?php
/**
 * PHPUnit tests for util.php.
 *
 * The tests can be executed using a PHPUnit test runner, e.g. the phpunit
 * command.
 */
require_once 'acis.php';


class DecodeSidsFunctionTest extends PHPUnit_Framework_TestCase
{
    public function testNormal()
    {
        /**
         * Test normal operation.
         *
         */
        $sids = array('13967 1', '346661 2', '346664 2');
        $table = array(
            'WBAN' => array('13967'), 
            'COOP' => array('346661', '346664')
        );
        $this->assertEquals($table, ACIS_decodeSids($sids));
        return;
    }
}
