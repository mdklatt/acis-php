<?php
/**
 * Package library files as a phar archive for distribution.
 *
 * For security, the ability to create phar archives is disabled by default. To 
 * change this the php.ini file must contain the following line:
 *     phar.readonly = Off
 */

// Package definition. In the future this would be a good place to use a 
// configuration file.

require_once 'acis/acis.php';
$pkg_name = 'acis';
$pkg_init = "{$pkg_name}.php";
$pkg_ver = ACIS_VERSION;
$pkg_path = $pkg_name;
$pkg_dest = 'dist';


// Create archive.

if (!is_dir($pkg_dest) && !mkdir($pkg_dest, 0755, true)) {
	throw Exception("could not create directory {$pkg_dest}");
}
$phar_name = "{$pkg_name}-${pkg_ver}.phar";
$phar_path = $pkg_dest.DIRECTORY_SEPARATOR.$phar_name;
$phar = new Phar($phar_path, 0, $phar_name);
$phar->buildFromDirectory($pkg_path, '/\.php/');
$phar->setStub(Phar::createDefaultStub($pkg_init));
printf("%d file(s) added to archive {$phar_path}".PHP_EOL, count($phar));
