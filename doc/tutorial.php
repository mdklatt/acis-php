<?php
require_once 'acis.phar';

date_default_timezone_set('UTC');  // stop DateTime from complaining

print 'EXAMPLE 1'.PHP_EOL;
$acis_call = new ACIS_WebServicesCall("StnData");
$params = array("sid"   => "OKC", 
                "date"  => "2012-08-03", 
                "elems" => "maxt",
                "meta"  => "name");
$result = $acis_call($params);
$name = $result["meta"]["name"];
list($date, $maxt) = $result["data"][0];  // first and only record
print "The high temperature for {$name} on {$date} was {$maxt}F.".PHP_EOL;
print str_repeat('-', 40).PHP_EOL;



print 'EXAMPLE 2'.PHP_EOL;
$acis_call = new ACIS_WebServicesCall("StnData");
$params = array("date" => "2012-08-03", "elems" => "maxt");  // oops, no site
try {
    $result = $acis_call($params);
}
catch (ACIS_RequestException $ex) {
    print "Oops: {$ex->getMessage()}".PHP_EOL;
}
print str_repeat('-', 40).PHP_EOL;


print 'EXAMPLE 3'.PHP_EOL;
$request = new ACIS_StnDataRequest();
$request->location(array("sid" => "OKC"));  // must specify an sid or uid
$request->dates("2012-08-03");              // single date OK
$request->addElement("maxt", array());      // must add elements one at a time
$request->metadata(array("name"));          // multiple arguments OK
$query = $request->submit();                
$result = $query["result"];
$name = $result["meta"]["name"];
list($date, $maxt) = $result["data"][0];
print "The high temperature for {$name} on {$date} was {$maxt}F.".PHP_EOL;
print str_repeat('-', 40).PHP_EOL;


print 'EXAMPLE 4'.PHP_EOL;
print str_repeat('-', 40).PHP_EOL;


print 'EXAMPLE 5'.PHP_EOL;
$request = new ACIS_StnDataRequest();
$request->location(array('sid' => 'OKC'));
$request->dates('2012-09-01', 'por');
$request->addElement("maxt", array());
$request->addElement("mint", array());
$request->metadata(array("name"));
$query = $request->submit();
print $query["result"]["meta"]["name"].PHP_EOL;
foreach ($query["result"]["data"] as $record) {
    list($date, $maxt, $mint) = $record;
    print "On {$date} the high was {$maxt}F and the low was {$mint}F.".PHP_EOL;
}
print str_repeat('-', 40).PHP_EOL;


print 'EXAMPLE 6'.PHP_EOL;
$request = new ACIS_StnDataRequest();
$request->location(array('sid' => 'OKC'));
$request->interval('mly');
$request->dates('2012-08');  // monthly, don't need day
$request->addElement("maxt", array("reduce" => array("reduce" => "max",
    "add" => "date")));
$request->addElement("mint", array("reduce" => array("reduce" => "min",
    "add" => "date")));
$request->addElement("pcpn", array("reduce" => array("reduce" => "max",
    "add" => "date")));
$request->addElement("pcpn", array("reduce" => "sum"));
$request->metadata(array("name"));
$query = $request->submit();
list($date, $maxt, $mint, $pcpn_max, $pcpn_sum) = $query["result"]["data"][0];
list($date, $maxt, $mint, $pcpn_max) = $query["result"]["data"][0];
print "***{$query["result"]["meta"]["name"]}***".PHP_EOL;
print "The max temperature of {$maxt[0]}F occurred on {$maxt[1]}.".PHP_EOL;
print "The min temperature of {$mint[0]}F occurred on {$mint[1]}.".PHP_EOL;
print "The max daily rainfall of {$pcpn_max[0]}\" occurred on {$pcpn_max[1]}.".PHP_EOL;
print "The total rainfall from the month was ${pcpn_sum}\"".PHP_EOL;
print str_repeat('-', 40).PHP_EOL;


print 'EXAMPLE 7'.PHP_EOL;
$request = new ACIS_StnDataRequest();
$request->location(array("sid" => "OKC"));  
$request->dates("2012-08-03");              
$request->addElement("maxt", array());      
$request->metadata(array("name"));   
$result = new ACIS_StnDataResult($request->submit());
foreach ($result as $record) {
    list($uid, $date, $maxt) = $record;
    print "The high temperature for {$result->meta[$uid]["name"]} on ${date} "
        ."was {$maxt}F.".PHP_EOL;
}
print count($result)." records returned".PHP_EOL;  // results are countable
print str_repeat('-', 40).PHP_EOL;


print 'EXAMPLE 8'.PHP_EOL;
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


print 'EXAMPLE 9'.PHP_EOL;
$request = new ACIS_MultiStnDataRequest();
$request->location(array('sids' => 'OKC,TUL,LAW,MLC,GAG'));  
$request->dates('2012-08-01', '2012-08-03');              
$request->addElement('maxt', array());      
$request->metadata(array('name'));   
$result = new ACIS_MultiStnDataResult($request->submit());
// foreach ($result as $record) {
//     list($uid, $date) = $record;
//     $elems = array_combine($result->fields, array_slice($record, 2));
//     print "The high temperature for {$result->meta[$uid]["name"]} on ${date} ".
//         "was {$elems['maxt']}F.".PHP_EOL;
// }
// print count($result).' records returned'.PHP_EOL;
print str_repeat('-', 40).PHP_EOL;


print 'EXAMPLE 10'.PHP_EOL;
$stream = new ACIS_MultiStnDataStream();
$stream->location(array('sids' => 'OKC,TUL,LAW,MLC,GAG'));  
$stream->date('2012-08-03');  // date() not dates()              
$stream->addElement('maxt', array());
$record_count = 0;
foreach ($stream as $record) {
    ++$record_count;
    list($sid, $date) = $record;
    $elems = array_combine($stream->elems(), array_slice($record, 2));
    print "The high temperature for {$stream->meta[$sid]["name"]} on ${date} ".
        "was {$elems['maxt']}F.".PHP_EOL;
}    
print count($result).' records returned'.PHP_EOL;  // Streams aren't countable
print str_repeat('-', 40).PHP_EOL;
