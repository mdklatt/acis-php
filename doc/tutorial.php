<?php
/*
*** Introduction ***

The acis library provides tools for working with data from the Applied Climate
Information System (ACIS):
    <http://data.rcc-acis.org>.

This tutorial shows how the library can be used to retrieve and read ACIS data.
Familiarity with the ACIS Web Services documentation would be helpful in
understanding the terms used here:
    <http://data.rcc-acis.org/doc>

Follow along with the output of the examples in this file by executing it:
    php -f tutorial.php > tutorial.txt


*** Installing the Library ***

The latest version of the library can be found at GitHub:
    <https://github.com/mdklatt/acis-php>

The library source code can retrieved using git or downloaded as a zip file. 
The required files can be found in the acis direcotyr under teh project root
directory. The library can also be downloaded as a self-contained phar archive.
Either the library directory or the phar archive must be in the PHP include 
path, either a local directory or a directory defined in php.ini.

*** Using the Library ***

To use the library include it using one of the include or require statements;
it should not be inluded more than once, so use include_once or require_once
if necessary. It's important to set the default time zone or the DateTime 
library will throw an exception.

*/
require '../acis/acis.php';
//require 'acis.phar'  // this works too if acis.phar is in the include path

date_default_timezone_set('UTC');  // DateTime exception without this.


/*
*** Using WebServicesCall ***

The core component of the library is the ACIS_WebServicesCall class. This class
takes care of encoding the parameters for the ACIS call, communicating with the
server, and decoding the result. The user is responsible for creating the
params object and interpreting the result. This provides the most flexibility
but also requires the most knowledge of the ACIS protocol.

The first example will retrieve the maximum temperature for Oklahoma City on
August 3, 2012 using a StnData call. A params object (an associative array) is 
created, the call is made, and the result is displayed. Note that the data 
values are strings, which allows for special values like "T" or "M". The user 
must check for these values and convert to a number as necessary.

*/
print 'EXAMPLE 1'.PHP_EOL;
$acis_call = new ACIS_WebServicesCall("StnData");
$params = array('sid'   => 'OKC', 
                'date'  => '2012-08-03', 
                'elems' => 'maxt',
                'meta'  => 'name');
$result = $acis_call($params);
$name = $result['meta']['name'];
list($date, $maxt) = $result['data'][0];  // first and only record
print "The high temperature for {$name} on {$date} was {$maxt}F.".PHP_EOL;
print str_repeat('-', 40).PHP_EOL;


/*
If the params object is not valid the server will respond with an error
and ACIS_WebServicesCall will throw an exception--see next example.

*/
print 'EXAMPLE 2'.PHP_EOL;
$acis_call = new ACIS_WebServicesCall('StnData');
$params = array('date' => '2012-08-03', 'elems' => 'maxt');  // oops, no site
try {
    $result = $acis_call($params);
}
catch (ACIS_RequestException $ex) {
    print "Oops: {$ex->getMessage()}".PHP_EOL;
}
print str_repeat('-', 40).PHP_EOL;


/*
*** Using Requests ***

The Request class hierarchy simplifies the process of executing an ACIS call by
managing the params object. There is a Request class for each type of ACIS
call: StnMetaRequest, StnDataRequest, and MultiStnDataRequest (the GridData and
General calls are not currently supported--use a WebServicesCall). Each Request
has methods for defining the options appropriate to that request.

This is a repeat of the first example, this time using a StnDataRequest. The
user does not have to create a params object but does have to interpret the
result object. A Request returns a query object (another dict) containining
both the params object it created and the result object from the server.

*/
print 'EXAMPLE 3'.PHP_EOL;
$request = new ACIS_StnDataRequest();
$request->location(array('sid' => 'OKC'));  // must specify an sid or uid
$request->dates('2012-08-03');              // single date OK
$request->addElement('maxt', array());      // must add elements one at a time
$request->metadata(array('name'));          // multiple arguments OK
$query = $request->submit();                
$result = $query['result'];
$name = $result['meta']['name'];
list($date, $maxt) = $result['data'][0];
print "The high temperature for {$name} on {$date} was {$maxt}F.".PHP_EOL;
print str_repeat('-', 40).PHP_EOL;


/*
StnDataRequest and MultiStnDataRequests both require date ranges, as given in
a call to dates(). Use a single argument for a single date, or two arguments
to specify the a date range (inclusive). The dates must be in an acceptable
string format, i.e. YYYY, YYYY-MM, or YYYY-MM-DD. The hyphens are optional,
but the year must be 4 digits and the month and day must be 2 digits. A StnData
call also accepts "por" insted of a date string; this means extend to the end
of the period of record in that direction. A single "por" value will retrieve
the entire period of record for that site.

The next example will retrieve all max and min temps for Oklahoma City since
September 1, 2012.

*/
print 'EXAMPLE 4'.PHP_EOL;
$request = new ACIS_StnDataRequest();
$request->location(array('sid' => 'OKC'));
$request->dates('2012-09-01', 'por');
$request->addElement('maxt', array());
$request->addElement('mint', array());
$request->metadata(array('name'));
$query = $request->submit();
print $query['result']['meta']['name'].PHP_EOL;
foreach ($query['result']['data'] as $record) {
    list($date, $maxt, $mint) = $record;
    print "On {$date} the high was {$maxt}F and the low was {$mint}F.".PHP_EOL;
}
print str_repeat('-', 40).PHP_EOL;


/*
By default an ACIS call retrieves daily data, but this can be changed using
the interval() method for StnDataRequest and MultiStnDataRequest. When using
using an interval other than daily, a reduction must be specified. Each element
will have the same interval, but they have their own reductions and summary
values.

This example will retrieve the monthly max temperature, min temperature, max
daily rainfall, and total monthly rainfall for Oklahoma City for August 2012,
along with the dates of occurrence. The additional options for each element
must be specified using keyword arugments. Note that the reduce option in this
case must be specified as a dict because of the "add" option.

*/
print 'EXAMPLE 5'.PHP_EOL;
$request = new ACIS_StnDataRequest();
$request->location(array('sid' => 'OKC'));
$request->interval('mly');
$request->dates('2012-08');  // monthly, don't need day
$request->addElement('maxt', array('reduce' => array('reduce' => 'max',
    'add' => 'date')));
$request->addElement('mint', array('reduce' => array('reduce' => 'min',
    'add' => 'date')));
$request->addElement('pcpn', array('reduce' => array('reduce' => 'max',
    'add' => 'date')));
$request->addElement('pcpn', array('reduce' => 'sum'));  // no date
$request->metadata(array('name'));
$query = $request->submit();
list($date, $maxt, $mint, $pcpn_max, $pcpn_sum) = $query['result']['data'][0];
list($date, $maxt, $mint, $pcpn_max) = $query['result']['data'][0];
print "***{$query['result']['meta']['name']}***".PHP_EOL;
print "The max temperature of {$maxt[0]}F occurred on {$maxt[1]}.".PHP_EOL;
print "The min temperature of {$mint[0]}F occurred on {$mint[1]}.".PHP_EOL;
print "The max daily rainfall of {$pcpn_max[0]}\" occurred on {$pcpn_max[1]}.".PHP_EOL;
print "The total rainfall from the month was ${pcpn_sum}\"".PHP_EOL;
print str_repeat('-', 40).PHP_EOL;


/*
*** Using Results ***

The Result class hierarchy simplifies the interpretation of an ACIS result
object. These classes are designed to be used with their corresponding Request
classes, but this is not required. There is a Result class for each type of
ACIS call: StnMetaResult, StnDataResult, and MultiStnDataResult (the GridData
and General calls are not currently supported).

The interface for each type of Result is the same even though the underlying
result object has a different structure for each type of call. ACIS Results
have a meta attribute for accessing metadata. This is keyed to the ACIS site
UID, so this must be requested as part of the metadata (using a Request
automatically takes care of this). StnDataResult and MultiStnDataResult also
have a data attribute and smry attribute for accessing the result's data and
summary values, respectively. Like the meta attribute, these attributes are
keyed to the ACIS site UID. Results with a data attribute support iteration,
which yields each record in the same format regardless of the call type. A
Result is initialized using a query object containing the params object sent to
the server and the result object it sent back. Note that this is conveniently
the output of a Request submit() call.

This is a repeat of the first example, retrieving the maximum temperature for
Oklahoma City for August 3, 2012. Iteration is used to illustrate the concept
even though this is for a single day at a single site.

*/
print 'EXAMPLE 6'.PHP_EOL;
$request = new ACIS_StnDataRequest();
$request->location(array('sid' => 'OKC'));  
$request->dates('2012-08-03');              
$request->addElement('maxt', array());      
$request->metadata(array('name'));   
$result = new ACIS_StnDataResult($request->submit());
foreach ($result as $record) {
    list($uid, $date, $maxt) = $record;
    print "The high temperature for {$result->meta[$uid]['name']} on ${date} "
        ."was {$maxt}F.".PHP_EOL;
}
print count($result).' records returned'.PHP_EOL;  // results are countable
print str_repeat('-', 40).PHP_EOL;


/*
Using a MultiStnDataResult is the same as using a StnDataResult even though the
actual result object from a MultiStnData call will be much more complicated. A
MultiStnDataResult calculates the date for each data record based on the params
object used to generate the data. (NOTE: due to a limiation in the current
version, "groupby" results will NOT give the correct date.)

This is a repeat of the previous example, but this time with multiple dates and
sites. Very little code has to be changed, and the output code doesn't have to
be changed at all.

*/
print 'EXAMPLE 7'.PHP_EOL;
$request = new ACIS_MultiStnDataRequest();
$request->location(array('sids' => 'OKC,TUL,LAW,MLC,GAG'));  
$request->dates('2012-08-01', '2012-08-03');              
$request->addElement('maxt', array());      
$request->metadata(array('name'));   
$result = new ACIS_MultiStnDataResult($request->submit());
foreach ($result as $record) {
    list($uid, $date, $maxt) = $record;
    print "The high temperature for {$result->meta[$uid]['name']} on ${date} ".
        "was {$maxt}F.".PHP_EOL;
}
print count($result).' records returned'.PHP_EOL;
print str_repeat('-', 40).PHP_EOL;


/*
A Result with a data attribute also has an elems attribute, which is a tuple of
the element names in the result. This can be used to refer to record fields by
name instead of index. If there is more than one element with the same name
they will be indexed, e.g. pcpn0, pcpn1, etc. Here's another version of the
previous example using named fields.

*/
print 'EXAMPLE 8'.PHP_EOL;
$request = new ACIS_MultiStnDataRequest();
$request->location(array('sids' => 'OKC,TUL,LAW,MLC,GAG'));  
$request->dates('2012-08-01', '2012-08-03');              
$request->addElement('maxt', array());      
$request->metadata(array('name'));   
$result = new ACIS_MultiStnDataResult($request->submit());
foreach ($result as $record) {
    list($uid, $date) = $record;
    $elems = array_combine($result->elems(), array_slice($record, 2));
    print "The high temperature for {$result->meta[$uid]['name']} on ${date} ".
        "was {$elems['maxt']}F.".PHP_EOL;
}
print count($result).' records returned'.PHP_EOL;
print str_repeat('-', 40).PHP_EOL;


/*
*** Using Streams ***

A potential drawback of using a Request/Result is that the entire result object
has to be received before the first record can be processed. With the Stream
classes, however, records can be streamed one by one from the server as soon as
its ready to return data. The total execution time will probably be the same
or even slightly longer for a Stream, but for large requests the delay between
executing the call and the start of data processing might be shorter.

The StnDataStream and MultiStnDataStream classes are used to both generate the
data request (like a Request) and iterate over the result (like a Result).
Streams are implemented using ACIS CSV output, so they are only available for
a subset of StnData and MultiStnData calls. Metadata options are fixed for each
type of call. Advanced element options, like "add", are not allowed. Only one
date is allowed for a MultiStnData call. Like a Result, streams have an elems
attribute, and a meta attribute that is populated as records are received.

This is a MultiStnDataStream version of the previous example, except that it's
limited to a single date. The code is similar to the Request/Result version.

*/
print 'EXAMPLE 9'.PHP_EOL;
$stream = new ACIS_MultiStnDataStream();
$stream->location(array('sids' => 'OKC,TUL,LAW,MLC,GAG'));  
$stream->date('2012-08-03');  // date() not dates()              
$stream->addElement('maxt', array());
$record_count = 0;
foreach ($stream as $record) {
    ++$record_count;
    list($sid, $date) = $record;
    $elems = array_combine($stream->elems(), array_slice($record, 2));
    print "The high temperature for {$stream->meta[$sid]['name']} on ${date} ".
        "was {$elems['maxt']}F.".PHP_EOL;
}    
print "{$record_count} records returned".PHP_EOL;  // Streams aren't countable
print str_repeat('-', 40).PHP_EOL;


/*
*** Utility Functions ***

The sids_table function can be used to interpret the "sids" metadata field.
This example will find all CoCoRaHS sites in Cleveland County, OK.

*/
print 'EXAMPLE 10'.PHP_EOL;
$request = new ACIS_StnMetaRequest();
$request->location(array('county' => '40027'));
$request->metadata(array('sids', 'name'));
$result = new ACIS_StnMetaResult($request->submit());
foreach ($result->meta as $uid => $info) {
    $table = ACIS_sidsTable($info['sids']);
    if (($sid = @$table['CoCoRaHS']) === null) {
        continue;
    }
    print "{$sid}: {$info['name']}".PHP_EOL;
}
print str_repeat('-', 40).PHP_EOL;
