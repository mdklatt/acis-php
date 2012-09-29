<?php
/* Set up the test environment.

The working directory is changed to the test directory and the module search
path is modified so that the local version of the library is imported.

*/

define('TEST_PATH', dirname(__FILE__));
define('LIB_PATH', dirname(TEST_PATH).DIRECTORY_SEPARATOR.'acis');

chdir(TEST_PATH);
set_include_path(LIB_PATH.PATH_SEPARATOR.get_include_path());

date_default_timezone_set('UTC');  // stop DateTime from complaining
