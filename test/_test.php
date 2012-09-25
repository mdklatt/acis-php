<?php
 
function convert_json($text)
{
    return json_decode($text, true);
}

function convert_array($text)
{
    return eval("return {$text};");
}
    
class TestData
{
    private static function _convert($text, $dtype)
    {
        $conversions = array(
            'int'   => 'intval',
            'float' => 'floatval',
            'str'   => 'strval',
            'json'  => 'convert_json',
            'array' => 'convert_array',
        );
        $func = $conversions[$dtype];
        return $func(trim($text));
    }
    
    private $_data = array();
    
    public function __construct($data_file)
    {
        $xml = new DOMDocument();
        $xml->loadXML(file_get_contents($data_file));
        $elements = $xml->getElementsByTagName('value');
        foreach ($elements as $elem) {
            $name = $elem->getAttribute('name');
            $dtype = $elem->getAttribute('dtype');
            $this->_data[$name] = TestData::_convert($elem->nodeValue, $dtype);
        }
    }
    
    public function __get($name)
    {
        if (!array_key_exists($name, $this->_data)) {
            throw new Exception("unknown value: {$name}");
        }
        return $this->_data[$name];
    }
}