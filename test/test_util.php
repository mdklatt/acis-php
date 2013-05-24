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
    /**
     * Test normal operation.
     *
     */
    public function test()
    {
        $encoded = array('13967 1', '346661 2', '346664 2', 'A123 9999');
        $decoded = array(
            'WBAN' => array('13967'), 
            'COOP' => array('346661', '346664'),
            '9999' => array('A123')
        );
        $this->assertEquals($decoded, ACIS_decodeSids($encoded));
        return;
    }
}
