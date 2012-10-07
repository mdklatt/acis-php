acis-php
========

Overview
--------
The [acis-php][1] library provides tools for [ACIS Web Services][5] client 
applications. There is also a [Python version][7].

Requirements
------------
PHP >= 5.2


Installation
------------
All the required files are in the [acis][2] directory, or a self-contained
[phar archive][3] can be downloaded. The directory or the phar file must be
visible in the [PHP include path][6].


Usage
-----
To use the library either `acis/acis.php` *or* `acis.phar` must be included.
    
    require_once 'acis/acis.php'; 
    require_once 'acis.phar'; 
    
The [tutorial][4] has examples of how to use the library.
    
    
Known Issues
------------
* ACIS_MultiStnDataResult will give the wrong dates when iterating over "groupby" results.


<!-- REFERENCES -->

[1]: http://github.com/mdklatt/acis-php "acis-php"
[2]: http://github.com/mdklatt/acis-php/tree/master/acis "acis"
[3]: http://github.com/mdklatt/acis-php/downloads "downloads"
[4]: http://github.com/mdklatt/acis-php/blob/master/doc/tutorial.php "tutorial"
[5]: http://data.rcc-acis.org "ACIS WS"
[6]: http://us.php.net/manual/en/ini.core.php#ini.include-path "PHP include"
[7]: http://github.com/mdklatt/acis-python "acis-python"