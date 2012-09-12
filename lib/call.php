<?php
/** 
 *
 *
 */
require_once 'error.php';

class ACIS_WebServicesCall
{
    /**
     *
     *
     */
    const _SERVER = 'http://data.rcc-acis.org';
    
    public $url;
    
    public function __construct($call_type)
    {
        $this->url = implode('/', array(ACIS_WebServicesCall::_SERVER, 
                $call_type));
        return;
    }

    public function __invoke($params)
    {
        $json_flag = !empty($params) ? 0 : JSON_FORCE_OBJECT;
        if (!($json = json_encode($params, $json_flag))) {
            throw Exception('JSON encoding for params failed');
        } 
        $stream = $this->_post(http_build_query(array("params" => $json)));
        if (array_key_exists('output', $params) && 
                strtolower($params['output']) != 'json') {
            return $stream;
        }
        $result = json_decode(stream_get_contents($stream), true);
        if (!$result) {
            throw new ACIS_RequestError('server returned an invalid result');
        }
        return $result;
    }
    
    private function _post($data)
    {
        $HTTP_OK = 200;
        $HTTP_BAD = 400;
        $options = array('method' => 'POST', 'content' => $data, 
                         'ignore_errors'=>true);
        $context = stream_context_create(array('http' => $options));
        if (!($stream = @fopen($this->url, 'rb', false, $context))) {
            throw new Exception("could not open connection to {$this->url}");
        }
        list(, $code, $message) = explode(' ', $http_response_header[0], 3);
        if ($code != $HTTP_OK) {
            // This doesn't do the right thing for a "soft 404", e.g. an ISP
            // redirects to a custom error or search page for a DNS lookup
            // failure and returns a 200 (OK) code.
            if ($code == $HTTP_BAD) {  // plain text error from ACIS server
                throw new ACIS_RequestError(stream_get_contents($stream));
            }
            throw new Exception("HTTP error: {$code} {$message}");
        }
        return $stream;
    }
}
    
