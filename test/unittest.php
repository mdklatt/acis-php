<?php
/* A poor man's unit test framework loosely based on Python's unittest.

*/

abstract class UnitTest_TestCase
{
    /* A group of tests that share a test fixure.
    
    */
    
    protected function setUp()
    {
        /* Set up a test fixture.
        
        This is called before every test is executed so that each one is run
        in isolation.
        
        */        
        return;
    }
    
    protected function tearDown()
    {
        /* Tear down up a test fixture.
        
        This is called after every test is executed so that each one is run
        in isolation.
        
        */
        
        return;
    }
    
    public function run($test)
    {
        $this->setUp();
        $this->$test();
        $this->tearDown();
        return;
    }
}


class UnitTest_TestSuite
implements Countable
{
    private $_tests = array();
     
    public function __construct($testCase=null)
    {
        if ($testCase != null) {
            $this->addTests($testCase);
        }
        return;
    }
        
    public function addTests($testCase)
    {
        /* Add all tests found in a TestCase class to this suite. 
        
        The $testCase parameter is a string referring to a TestCase subclass.
        Any method name in that class starting with 'test' is considered to be
        a test.
        
        */        
        $this->_tests[$testCase] = array();
        $refl = new ReflectionClass($testCase);
        foreach ($refl->getMethods() as $method) {
            if (!preg_match('/^test/', $method->name)) {
                continue;
            }
            $this->_tests[$testCase][] = $method->name;
        }
        return;
    }
        
    public function run()
    {
    	// set_error_handler($this->_errorHandler);
    	// set_exception_handler($this->_exceptionHandler);
    	
        foreach ($this->_tests as $testCase => $tests) {
            $object = new $testCase();
            foreach ($tests as $test) {
                print "Running {$testCase}::{$test}".PHP_EOL;
                $object->run($test);
            }
        }
        // reset error_handler
        // reset exceptoin_handler
        return;
    }
    
    public function count()
    {
        /* Implement the Countable interface. */
        
        $count = 0;
        foreach ($this->_tests as $testCase) {
            $count += count($testCase);
        }
        return $count;
    }
    
    //private function _errorHandler();  // log failure, print message
    //private fucntion _exceptionHandler(); // log failure, print message    
}
