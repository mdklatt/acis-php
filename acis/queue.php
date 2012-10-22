<?php
/**
 * Parallel execution of multiple ACIS requests.
 *
 * USE AT YOUR OWN RISK.
 * In situations where server-side processing is the bottleneck, application
 * performance can be enhanced by executing requests in parallel on the ACIS
 * server.
 *
 * This is _very_ alpha and cannot handle things like server redirects (but 
 * ACIS isn't doing this...yet). An error with one request will take the whole
 * queue down. The interface should not be considered stable. 
 */

 /**
  * Manage parallel Requests.
  */
class ACIS_RequestQueue
{
    public $results = array();
    
    private $_queue;
    private $_queries = array();
    
    /**
     * Initialize a RequestQueue object.
     */
    public function __construct()
    {
        $this->_queue = new _HttpPostRequestQueue();
    }
    
    /**
     * Add a Request to the queue.
     *
     * The optional result paramater can be a Result class or anything that
     * accepts a query object as an argument; the query array will be 
     * converted to a result_type object.
     */
    public function add($request, $result_type=null)
    {
        $params = $request->params();
        $data = http_build_query(array('params' => json_encode($params)));
        $key = $this->_queue->add($request->url(), $data);
        $this->_queries[$key] = array($request->params(), $result_type);
        return;
    }
    
    /**
     * Execute all Requests in the queue.
     *
     * When execution is complete, each element of the results attribute will 
     * contain a query object or the optional result type specified for that 
     * request.     
     */
    public function execute()
    {
        $this->_queue->execute();
        foreach ($this->_queue->replies as $key => $reply) {
            list($code, $message) = $reply->status;
            if ($code != ACIS_HTTP_OK) {
                // This doesn't do the right thing for a "soft 404", e.g. an 
                // ISP redirects to a custom error or search page for a DNS
                // lookup failure and returns a 200 (OK) code.
                if ($code == ACIS_HTTP_BAD) {
                    // If the ACIS server returns this code it also provides a
                    // helpful plain text error message.
                    throw new ACIS_RequestException($reply->content);
                }
                throw new RuntimeException("HTTP error: {$code} {$message}");
            }
            list($params, $result_type) = $this->_queries[$key];
            $result = json_decode($reply->content, true);
            $query = array('params' => $params, 'result' => $result);
            if ($result_type == null) {
                $this->results[$key] = $query;
            }
            else {
                $this->results[$key] = new $result_type($query);
            }
        }
        return;
    }
}
 
 
/**
 * An HTTP reply. 
 */ 
class _HttpReply
{
    const CRLF = "\r\n";
    
    public $status;
    public $content;
    
    /**
     * Initialize an HttpReply object. 
     */     
    public function __construct($data)
    {
        $lines = explode(self::CRLF, $data);
        list(, $code, $message) = explode(' ', $lines[0], 3);
        $this->status = array((int)$code, trim($message));
        for ($pos = 1; $pos < count($lines); ++$pos) {
            // Find the blank line that separates the header from the content.
            if ($lines[$pos] === '') {
                ++$pos;  // content starts on next line
                break;
            }
        }
        $this->content = implode(self::CRLF, array_slice($lines, $pos));
        return;            
    }         
} 

/**
 * Execute HTTP POST requests asynchronously.
 */ 
class _HttpPostRequestQueue
{
    const CRLF = "\r\n";
    
    public $replies = array();
    
    private $_streams = array();
    private $_putBuffer = array();
    private $_getBuffer = array();
    
    public function add($url, $data)
    {
        $url = parse_url($url);
        $len = strlen($data);
        $post = "POST {$url['path']} HTTP/1.0".self::CRLF;
        $post.= 'Content-Type: application/x-www-form-urlencoded'.self::CRLF;
        $post.= "Content-Length: {$len}".self::CRLF;
        $post.= self::CRLF;
        $post.= $data;
        $fd = $this->_connect($url['host']);
        $this->_putBuffer[$fd] = $post;
        $this->_getBuffer[$fd] = '';
        $this->results[$fd] = null;
        return $fd;
    }
    
    /**
     * Execute all requests in the queue.
     */   
    public function execute()
    {
        // Continue until the queue is empty. As an EOF is detected on each
        // connection it is removed from the queue.
        while (count($this->_streams) > 0)
        {
            $readers = $writers = $errors = $this->_streams;
            stream_select($reader, $writers, $errors, 0);
            foreach ($writers as $stream) {
                $this->_put($stream);
            }
            foreach ($readers as $stream) {
                if ($this->_get($stream) == 0) {  // EOF
                    $fd = (int)$stream;
                    @fclose($this->_streams[$fd]);
                    unset($this->_streams[$fd]);
                    $this->replies[$fd] = new _HttpReply(
                        $this->_getBuffer[$fd]);
                }
            }
        }
        return; 
    }
    
    /**
     * Create a socket connection to a remote host.
     */   
    private function _connect($host)
    {
        $remote = "tcp://{$host}:80";
        $stream = @stream_socket_client($remote, $errnum, $errstr);
        if (!$stream) {
            throw new RuntimeException($errstr);            
        }
        $fd = (int)$stream;
        $this->_streams[$fd] = $stream;
        return $fd;
    }
    
    /**
     * Send data to the remote connection.
     */   
    private function _put($stream)
    {
        $data = &$this->_putBuffer[(int)$stream];
        $len = fputs($stream, $data);
        $data = substr($data, $len);
        return $len;
    }
    
    /**
     * Retrieve data from the remote connection.
     */
    private function _get($stream)
    {
        $data = fgets($stream);
        if (($len = strlen($data)) > 0) {
            $this->_getBuffer[(int)$stream].= $data;
        }
        return $len;
    }
}
