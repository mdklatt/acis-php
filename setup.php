<?php
/**
 * Library installation and packaging script.
 *
 * This is based on python.distutils setup scripts.
 */
require_once 'acis/acis.php';


// The packages-specific configuration array.

$PACKAGE_CONFIG = array(
    "name" => "acis",
    "version" => ACIS_VERSION,
);



// Execute the script.

$_DEBUG = true;  // should be command-line setting.

try {
    if ($argc == 1) {
        echo 'usage: setup.php cmd'.PHP_EOL;
        exit(1);
    }
    exit(main($PACKAGE_CONFIG, $argv)); 
}
catch (Exception $ex) {
    if ($_DEBUG) {
        throw $ex;  // force a stack trace
    }
    echo $ex->getMessage().PHP_EOL;
    exit(1);
}


/** 
 * Script controller.
 *
 */
function main($config, $argv)
{
    // Define configuration defaults.
    if (!array_key_exists('path', $config)) {
        $config['path'] = $config['name'];
    }
    if (!array_key_exists('init', $config)) {
        $config['init'] = $config['name'].'.php';
    }
    
    // Run the given command.
    // TODO: Allow multiple commands.
    // TODO: Parse options from command line; options must come before the
    // command names, and all options are passed to all commands so each one
    // can decide which options it needs to deal with.
    list(, $command) = $argv;
    $command.= '_command';
    return $command($config, array());
}


/**
 * Execute the test suite for this package.
 *
 */
function test_command($config, $opts)
{
    system('php -f test/run_tests.php', $status);
    return $status;
}


/**
 * Create a PHP Archive (.phar) file for this package.
 *
 */
function archive_command($config, $opts)
{
    // Get stub code.
    
    $stub = file_get_contents('bootstrap.template');
    $stub = str_replace('{{init}}', "'{$config['init']}'", $stub);
    $length = strlen($stub) - strlen('{{length}}');
    $length += (strlen($length + strlen($length))); 
    $stub = str_replace('{{length}}', $length, $stub);
    
    // Create phar file. How to overwrite exiting archive instead of appending
    // files?
    
    $name = "{$config['name']}-{$config['version']}.phar";
    $path = $name;
    $phar = new Phar($path, 0, $name);
    $phar->buildFromDirectory($config['path'], '/\.php/'); 
    $phar->setStub($stub);
    printf("%d file(s) added to archive {$path}".PHP_EOL, $phar->count());
    return 0;
}


/**
 * Install this package.
 *
 */
function install_command($config, $opts)
{
    return 0;
}