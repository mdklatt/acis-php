<?php
/* Package required files as a phar archive for distribution.

For security, the ability to create phar archives is disable by default. To 
change this the php.ini file must contain the following line:
     phar.readonly = Off

*/

// Check for the correction configuration in php.ini.

if (!Phar::canWrite()) {
	throw new Exception('php.ini prohibits the creation of phar archives');
}


// Define the package. In the future this would be a good place to use a 
// configuration file.

$pkg_name = 'acis';
$pkg_init = "{$pkg_name}.php";
$pkg_ver = '0.1.dev';
$pkg_path = 'lib';
$pkg_dest = 'dist';


// Create the phar archive.

if (!is_dir($pkg_dest) && !mkdir($pkg_dest, 0755, true)) {
	throw Exception("could not create directory {$pkg_dest}");
}
$phar_name = "{$pkg_name}-${pkg_ver}.phar";
$phar_path = $pkg_dest.DIRECTORY_SEPARATOR.$phar_name;
$phar = new Phar($phar_path, 0, $phar_name);
$phar->buildFromDirectory($pkg_path);
$phar->setStub(Phar::createDefaultStub('acis.php'));
printf("%d file(s) added to archive {$phar_path}".PHP_EOL, count($phar));
