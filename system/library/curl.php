<?php
class Curl {

	private $_headers = array();

	public $curl;

	/**
     * @var booelan Whether an error occured or not
     */
    public $error = false;

    /**
     * @var int Contains the error code of the curren request, 0 means no error happend
     */
    public $error_code = 0;

    /**
     * @var string If the curl request failed, the error message is contained
     */
    public $error_message = null;

    /**
     * @var booelan Whether an error occured or not
     */
    public $curl_error = false;

    /**
     * @var int Contains the error code of the curren request, 0 means no error happend
     */
    public $curl_error_code = 0;

    /**
     * @var string If the curl request failed, the error message is contained
     */
    public $curl_error_message = null;

    /**
     * @var booelan Whether an error occured or not
     */
    public $http_error = false;

    /**
     * @var int Contains the error code of the curren request, 0 means no error happend
     */
    public $http_status_code = 0;

    /**
     * @var string If the curl request failed, the error message is contained
     */
    public $http_error_message = null;

    /**
     * @var string|array TBD (ensure type) Contains the request header informations
     */
    public $request_headers = null;

    /**
     * @var string|array TBD (ensure type) Contains the response header informations
     */
    public $response_headers = array();

    /**
     * @var string Contains the response from the curl request
     */
    public $response = null;
 	
	public function __construct() {
		$this->init();
	}

	private function init()
    {
        $this->curl = curl_init();
        $this->setOpt(CURLINFO_HEADER_OUT, true);
        $this->setOpt(CURLOPT_HEADER, false);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        return $this;
    }

    protected function exec()
    {
        $this->response_headers = array();
        $this->response = curl_exec($this->curl);
        $this->curl_error_code = curl_errno($this->curl);
        $this->curl_error_message = curl_error($this->curl);
        $this->curl_error = !($this->curl_error_code === 0);
        $this->http_status_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->http_error = in_array(floor($this->http_status_code / 100), array(4, 5));
        $this->error = $this->curl_error || $this->http_error;
        $this->error_code = $this->error ? ($this->curl_error ? $this->curl_error_code : $this->http_status_code) : 0;
        $this->request_headers = preg_split('/\r\n/', curl_getinfo($this->curl, CURLINFO_HEADER_OUT), null, PREG_SPLIT_NO_EMPTY);
        $this->http_error_message = $this->error ? (isset($this->response_headers['0']) ? $this->response_headers['0'] : '') : '';
        $this->error_message = $this->curl_error ? $this->curl_error_message : $this->http_error_message;

        return $this->error_code;
    }


    public function get($url, $data = array())
    {
        if (count($data) > 0) {
            $this->setOpt(CURLOPT_URL, $url.'?'.http_build_query($data));
        } else {
            $this->setOpt(CURLOPT_URL, $url);
        }
        $this->setOpt(CURLOPT_HTTPGET, true);
        $this->exec();
        return $this;
    }

	public function post($url, $data)
    {
        $this->setOpt(CURLOPT_URL, $url);
        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, $data);
        $this->exec();
        return $this;
    }

	public function reset()
    {
        $this->close();
        $this->_headers = array();
        $this->error = false;
        $this->error_code = 0;
        $this->error_message = null;
        $this->curl_error = false;
        $this->curl_error_code = 0;
        $this->curl_error_message = null;
        $this->http_error = false;
        $this->http_status_code = 0;
        $this->http_error_message = null;
        $this->request_headers = null;
        $this->response_headers = array();
        $this->response = null;
        $this->init();
        return $this;
    }

	public function close()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
        return $this;
    }

	public function setHeader($key, $value)
    {
        $this->_headers[$key] = $key.': '.$value;
        $this->setOpt(CURLOPT_HTTPHEADER, array_values($this->_headers));
        return $this;
    }

	public function setOpt($option, $value)
    {
        return curl_setopt($this->curl, $option, $value);
    }

    /**
     * Close the connection when the Curl object will be destroyed.
     */
    public function __destruct()
    {
        $this->close();
    }

    public function isInfo()
    {
        return $this->http_status_code >= 100 && $this->http_status_code < 200;
    }

    /**
     * Was an 'OK' response returned.
     * @return bool
     */
    public function isSuccess()
    {
        return $this->http_status_code >= 200 && $this->http_status_code < 300;
    }

    /**
     * Was a 'redirect' returned.
     * @return bool
     */
    public function isRedirect()
    {
        return $this->http_status_code >= 300 && $this->http_status_code < 400;
    }

    /**
     * Was an 'error' returned (client error or server error).
     * @return bool
     */
    public function isError()
    {
        return $this->http_status_code >= 400 && $this->http_status_code < 600;
    }

    /**
     * Was a 'client error' returned.
     * @return bool
     */
    public function isClientError()
    {
        return $this->http_status_code >= 400 && $this->http_status_code < 500;
    }

    /**
     * Was a 'server error' returned.
     * @return bool
     */
    public function isServerError()
    {
        return $this->http_status_code >= 500 && $this->http_status_code < 600;
    }
}