<?php
/* Set the include path to the local lib path.
 *
 */
$lib_path = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'lib';
set_include_path($lib_path.PATH_SEPARATOR.get_include_path());
