<?php

namespace App\Library;

class Curl
{
    const OPT_HEADER_COOKIE = 'OPT_HEADER_COOKIE';
    const OPT_HEADER_USERAGENT = 'OPT_HEADER_USERAGENT';
    const OPT_HEADER_REFERER = 'OPT_HEADER_REFERER';
    const OPT_HEADER_CONTENTTYPE = 'OPT_HEADER_CONTENTTYPE';
    const OPT_HEADER_CONTENTTYPE_JSON = 'OPT_HEADER_CONTENTTYPE_JSON';
    const OPT_HEADER_CONTENTTYPE_JSON_UTF8 = 'OPT_HEADER_CONTENTTYPE_JSON_UTF8';
    const OPT_HEADER_CONTENTTYPE_FORM_URLENCODED = 'OPT_HEADER_CONTENTTYPE_FORM_URLENCODED';
    const OPT_HEADER_CONTENTTYPE_XML = 'OPT_HEADER_CONTENTTYPE_XML';
    const OPT_HEADER_ACCEPTTYPE = 'OPT_HEADER_ACCEPTTYPE';
    const OPT_HEADER_AUTH = 'OPT_HEADER_AUTH';
    const OPT_HEADER_AUTH_BASIC = 'OPT_HEADER_AUTH_BASIC';
    const OPT_HEADER_AUTH_USERPASS = 'OPT_HEADER_AUTH_USERPASS';
    const OPT_HEADER_AUTH_BEARER = 'OPT_HEADER_AUTH_BEARER';

    const OPT_COOKIE_FILE = 'OPT_COOKIE_FILE';
    const OPT_COOKIE_REQUEST_FILE = 'OPT_COOKIE_REQUEST_FILE';
    const OPT_COOKIE_RESPONSE_FILE = 'OPT_COOKIE_RESPONSE_FILE';

    const OPT_CONNECT_TIMEOUT = 'OPT_CONNECT_TIMEOUT';
    const OPT_TIMEOUT = 'OPT_TIMEOUT';
    const OPT_REDIRECT_NUMBER = 'OPT_REDIRECT_NUMBER';
    const OPT_PROXY = 'OPT_PROXY';
    const OPT_CACHE = 'OPT_CACHE';
    const OPT_RETRY_NUMBER = 'OPT_RETRY_NUMBER';
    const OPT_RETRY_DELAY = 'OPT_RETRY_DELAY';
    const OPT_CALLBACK_SHOULD_RETRY = 'OPT_CALLBACK_SHOULD_RETRY';

    const OPT_IS_POST_JSON = 'OPT_IS_POST_JSON';
    const OPT_IS_GET_HEADER = 'OPT_IS_GET_HEADER';
    const OPT_IS_GET_BODY = 'OPT_IS_GET_BODY';

    const OPT_CURL_OPTIONS = 'OPT_CURL_OPTIONS';

    const DEFAULT_COOKIE_FILE = '/tmp/curl_cookie.txt';
    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_JSON_UTF8 = 'application/json; charset=UTF-8';
    const CONTENT_TYPE_FORM_URLENCODED = 'application/x-www-form-urlencoded';
    const CONTENT_TYPE_XML = 'text/xml';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_OPTION = 'OPTION';

    protected $method = self::METHOD_GET;
    protected $queryParams = [];
    protected $postParams = [];
    protected $connectTimeout;
    protected $timeout = 30;
    protected $redirectNumber = 10;
    protected $proxy = ''; // format: IP:PORT[,USER:PASS]
    protected $cache = '';
    protected $retryNumber = 0;
    protected $retryDelay = 1;
    protected $callbackShouldRetry = null;
    protected $isPostJson = false;
    protected $isGetHeader = false;
    protected $isGetBody = true;

    protected $headers = [];
    protected $curlOptions = [];
    protected $options;

    public function __construct()
    {
        if (! function_exists('curl_init')) {
            throw new \Exception('cURL module must be enabled!');
        }
    }

    /**
     * @param $url
     * @param array $queryParams
     * @param array $headers
     * @param array $options
     * @return CurlResponse
     */
    public static function doGet($url, $queryParams = [], $headers = [], $options = [])
    {
        return self::doRequest($url, self::METHOD_GET, [], $queryParams, $headers, $options);
    }

    /**
     * @param $url
     * @param array|string $postParams
     * @param array $queryParams
     * @param array $headers
     * @param array $options
     * @return CurlResponse
     */
    public static function doPost($url, $postParams = [], $queryParams = [], $headers = [], $options = [])
    {
        return self::doRequest($url, self::METHOD_POST, $postParams, $queryParams, $headers, $options);
    }

    /**
     * @param $url
     * @param string $method
     * @param array|string $postParams
     * @param array $queryParams
     * @param array $headers
     * @param array $options
     * @return CurlResponse
     */
    public static function doRequest($url, $method = self::METHOD_GET, $postParams = [], $queryParams = [], $headers = [], $options = [])
    {
        $ch = @curl_init();
        self::setDefaultOptions($ch);

        $method = strtoupper($method);
        $responseHeaders = [];
        $requestHeaders = [];

        // headers
        foreach ($headers as $key => $value) {
            $requestHeaders[] = self::parseHeader($key, $value);
        }

        // curl options
        // header
        $optHeaders = self::parseHeadersFromOptions($options);
        $requestHeaders = array_merge($requestHeaders, $optHeaders);

        // cookie
        if (isset($options[self::OPT_COOKIE_FILE])) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $options[self::OPT_COOKIE_FILE] ? $options[self::OPT_COOKIE_FILE] : '');
            curl_setopt($ch, CURLOPT_COOKIEJAR, $options[self::OPT_COOKIE_FILE] ? $options[self::OPT_COOKIE_FILE] : '');
        } else {
            if (isset($options[self::OPT_COOKIE_REQUEST_FILE])) {
                curl_setopt($ch, CURLOPT_COOKIEFILE, $options[self::OPT_COOKIE_REQUEST_FILE] ? $options[self::OPT_COOKIE_REQUEST_FILE] : '');
            }
            if (isset($options[self::OPT_COOKIE_RESPONSE_FILE])) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $options[self::OPT_COOKIE_RESPONSE_FILE] ? $options[self::OPT_COOKIE_RESPONSE_FILE] : '');
            }
        }
        // advanced
        if (isset($options[self::OPT_CONNECT_TIMEOUT])) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $options[self::OPT_CONNECT_TIMEOUT]);
        }
        if (isset($options[self::OPT_TIMEOUT])) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $options[self::OPT_TIMEOUT]);
        }
        if (isset($options[self::OPT_REDIRECT_NUMBER])) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $options[self::OPT_REDIRECT_NUMBER] > 0 ? 1 : 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $options[self::OPT_REDIRECT_NUMBER]);
        }
        if (isset($options[self::OPT_PROXY])) {
            @list($proxyIp, $proxyUserPass) = explode(',', $options[self::OPT_PROXY]);
            curl_setopt($ch, CURLOPT_PROXY, $proxyIp ? $proxyIp : '');
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyUserPass ? $proxyUserPass : '');
        }
        if (isset($options[self::OPT_IS_GET_BODY])) {
            curl_setopt($ch, CURLOPT_NOBODY, $options[self::OPT_IS_GET_BODY] ? 0 : 1);
        }

        // features
        $cache = ! empty($options[self::OPT_CACHE]) ? true : false; // TODO: not use
        $maxRetry = ! empty($options[self::OPT_RETRY_NUMBER]) ? $options[self::OPT_RETRY_NUMBER] : 0;
        $sleepRetry = ! empty($options[self::OPT_RETRY_DELAY]) ? $options[self::OPT_RETRY_DELAY] : 0;
        $retryCallback = isset($options[self::OPT_CALLBACK_SHOULD_RETRY]) ? $options[self::OPT_CALLBACK_SHOULD_RETRY] : '';
        $postJson = isset($options[self::OPT_IS_POST_JSON]) ? $options[self::OPT_IS_POST_JSON] : false;
        $getHeader = isset($options[self::OPT_IS_GET_HEADER]) ? $options[self::OPT_IS_GET_HEADER] : false;

        // other options
        $others = isset($options[self::OPT_CURL_OPTIONS]) ? $options[self::OPT_CURL_OPTIONS] : [];

        if ($queryParams) {
            if (strpos($url, '?') === false) {
                $url .= '?';
            }
            $url .= '&'.http_build_query($queryParams, null, null, PHP_QUERY_RFC3986);
        }

        if ($postParams) {
            if (is_array($postParams) || is_object($postParams)) {
                $post = $postJson ? @json_encode($postParams) : http_build_query($postParams);
            } else {
                $post = $postParams;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        if ($getHeader) {
            curl_setopt($ch, CURLOPT_HEADERFUNCTION,
                function ($curl, $header) use (&$responseHeaders) {
                    $len = strlen($header);
                    $header = explode(':', $header, 2);
                    if (count($header) < 2) { // ignore invalid headers
                        return $len;
                    }

                    $responseHeaders[strtolower(trim($header[0]))][] = trim($header[1]);

                    return $len;
                }
            );
        }

        // others
        foreach ($others as $key => $value) {
            curl_setopt($ch, $key, $value);
        }

        if ($requestHeaders) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $execStart = microtime(true);

        self::debug('Starting curl at '.date('Y-m-d H:i:s'), ['url' => $url]);

        $retry = 0;
        do {
            if ($retry > 0) {
                self::debug("  > Retrying $retry ...", ['url' => $url]);
                if ($sleepRetry) {
                    sleep($sleepRetry);
                }
            }

            $response = curl_exec($ch);
            $execFinish = microtime(true);

            $curlResponse = new CurlResponse($ch, $response, $responseHeaders);
            $curlResponse->executeStart = $execStart;
            $curlResponse->executeFinish = $execFinish;
            $curlResponse->retried = $retry;

            $needRetry = $response === false;
            if ($retryCallback) {
                if (is_callable($retryCallback)) {
                    $needRetry = $retryCallback($curlResponse, $needRetry);
                }
            }

            $retry++;
        } while ($needRetry && $retry <= $maxRetry);

        self::debug('  > Finished curl at '.date('Y-m-d H:i:s'), ['url' => $url, 'body' => $curlResponse->body]);
        curl_close($ch);

        self::log($url, $method, $postParams, $headers, $curlResponse);

        return $curlResponse;
    }

    /**
     * @param $url
     * @param $method
     * @return CurlResponse
     */
    public function request($url, $method)
    {
        return self::doRequest($url, $method, $this->postParams, $this->queryParams, $this->headers, $this->options);
    }

    /**
     * @param $url
     * @return CurlResponse
     */
    public function get($url)
    {
        return $this->request($url, self::METHOD_GET);
    }

    /**
     * @param $url
     * @return CurlResponse
     */
    public function post($url)
    {
        return $this->request($url, self::METHOD_POST);
    }

    /**
     * @param $key
     * @param $value
     * @return string
     */
    public static function parseHeader($key, $value)
    {
        return (is_numeric($key) ? '' : ($key.': ')).$value;
    }

    /**
     * @param $options
     * @return array
     */
    public static function parseHeadersFromOptions($options)
    {
        $headers = [];
        if (isset($options[self::OPT_HEADER_COOKIE])) {
            $headers[] = 'Cookie: '.$options[self::OPT_HEADER_COOKIE];
        }
        if (isset($options[self::OPT_HEADER_USERAGENT])) {
            $headers[] = 'User-Agent: '.$options[self::OPT_HEADER_USERAGENT];
        }
        if (isset($options[self::OPT_HEADER_REFERER])) {
            $headers[] = 'Referer: '.$options[self::OPT_HEADER_REFERER];
        }
        if (isset($options[self::OPT_HEADER_CONTENTTYPE])) {
            $headers[] = 'Content-Type: '.$options[self::OPT_HEADER_CONTENTTYPE];
        }
        if (isset($options[self::OPT_HEADER_CONTENTTYPE_JSON])) {
            $headers[] = 'Content-Type: '.self::CONTENT_TYPE_JSON;
        }
        if (isset($options[self::OPT_HEADER_CONTENTTYPE_JSON_UTF8])) {
            $headers[] = 'Content-Type: '.self::CONTENT_TYPE_JSON_UTF8;
        }
        if (isset($options[self::OPT_HEADER_CONTENTTYPE_FORM_URLENCODED])) {
            $headers[] = 'Content-Type: '.self::CONTENT_TYPE_FORM_URLENCODED;
        }
        if (isset($options[self::OPT_HEADER_CONTENTTYPE_XML])) {
            $headers[] = 'Content-Type: '.self::CONTENT_TYPE_XML;
        }
        if (isset($options[self::OPT_HEADER_ACCEPTTYPE])) {
            $headers[] = 'Accept: '.$options[self::OPT_HEADER_ACCEPTTYPE];
        }
        if (isset($options[self::OPT_HEADER_AUTH])) {
            $headers[] = 'Authorization: '.$options[self::OPT_HEADER_AUTH];
        }
        if (isset($options[self::OPT_HEADER_AUTH_BASIC])) {
            $headers[] = 'Authorization: Basic '.$options[self::OPT_HEADER_AUTH_BASIC];
        }
        if (isset($options[self::OPT_HEADER_AUTH_USERPASS])) {
            $headers[] = 'Authorization: Basic '.base64_encode($options[self::OPT_HEADER_AUTH_USERPASS]);
        }
        if (isset($options[self::OPT_HEADER_AUTH_BEARER])) {
            $headers[] = 'Authorization: Bearer '.$options[self::OPT_HEADER_AUTH_BEARER];
        }

        return $headers;
    }

    /**
     * @param $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @param array $params
     */
    public function setQueryParams($params)
    {
        $this->queryParams = $params;
    }

    /**
     * @param array $params
     */
    public function setPostParams($params)
    {
        $this->postParams = $params;
    }

    /**
     * @param $cookie
     */
    public function setHeaderCookie($cookie)
    {
        $this->options[self::OPT_HEADER_COOKIE] = $cookie;
    }

    /**
     * @param $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->options[self::OPT_HEADER_USERAGENT] = $userAgent;
    }

    /**
     * @param $referer
     */
    public function setReferer($referer)
    {
        $this->options[self::OPT_HEADER_REFERER] = $referer;
    }

    /**
     * @param $contentType
     */
    public function setContentType($contentType)
    {
        $this->options[self::OPT_HEADER_CONTENTTYPE] = $contentType;
    }

    /**
     * @param $acceptType
     */
    public function setAcceptType($acceptType)
    {
        $this->options[self::OPT_HEADER_ACCEPTTYPE] = $acceptType;
    }

    /**
     * @param $token
     */
    public function setAuthToken($token)
    {
        $this->options[self::OPT_HEADER_AUTH] = $token;
    }

    /**
     * @param $token
     */
    public function setAuthBasic($token)
    {
        $this->options[self::OPT_HEADER_AUTH_BASIC] = $token;
    }

    /**
     * @param $user
     * @param $pass
     */
    public function setAuthUserPass($user, $pass)
    {
        $this->options[self::OPT_HEADER_AUTH_USERPASS] = "{$user}:{$pass}";
    }

    /**
     * @param $token
     */
    public function setAuthBearer($token)
    {
        $this->options[self::OPT_HEADER_AUTH_BEARER] = $token;
    }

    /**
     * @param $requestFile
     * @param $responseFile
     */
    public function setCookieFile($requestFile, $responseFile)
    {
        $this->options[self::OPT_COOKIE_REQUEST_FILE] = $requestFile;
        $this->options[self::OPT_COOKIE_RESPONSE_FILE] = $responseFile;
    }

    /**
     * @param $seconds
     */
    public function setConnectTimeout($seconds)
    {
        $this->options[self::OPT_CONNECT_TIMEOUT] = $seconds;
    }

    /**
     * @param $seconds
     */
    public function setTimeout($seconds)
    {
        $this->options[self::OPT_TIMEOUT] = $seconds;
    }

    /**
     * @param $redirectNumber
     */
    public function setRedirectNumber($redirectNumber)
    {
        $this->options[self::OPT_REDIRECT_NUMBER] = $redirectNumber;
    }

    /**
     * @param $proxy
     */
    public function setProxy($proxy)
    {
        $this->options[self::OPT_PROXY] = $proxy;
    }

    /**
     * @param $cache
     */
    public function setCache($cache)
    {
        $this->options[self::OPT_CACHE] = $cache;
    }

    /**
     * @param int $retryNumber
     * @param int $retryDelay
     * @param null|callable $callbackShouldRetry
     */
    public function setRetry(int $retryNumber, $retryDelay = 1, $callbackShouldRetry = null)
    {
        $this->options[self::OPT_RETRY_NUMBER] = $retryNumber;
        $this->options[self::OPT_RETRY_DELAY] = $retryDelay;
        $this->options[self::OPT_CALLBACK_SHOULD_RETRY] = $callbackShouldRetry;
    }

    /**
     * @param $postJson
     */
    public function setIsPostJson($postJson)
    {
        $this->options[self::OPT_IS_POST_JSON] = $postJson;
    }

    /**
     * @param $getHeader
     */
    public function setIsGetHeader($getHeader)
    {
        $this->options[self::OPT_IS_GET_HEADER] = $getHeader;
    }

    /**
     * @param $getBody
     */
    public function setIsGetBody($getBody)
    {
        $this->options[self::OPT_IS_GET_BODY] = $getBody;
    }

    /**
     * @param array $curlOptions
     */
    public function setCurlOptions($curlOptions)
    {
        $this->options[self::OPT_CURL_OPTIONS] = $curlOptions;
    }

    /**
     * @param $key
     * @param $value
     */
    public function appendHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * @param $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * @param $ch : a cURL handle
     */
    public static function setDefaultOptions($ch)
    {
        $defaults = [
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.39 Safari/537.36',
            // True to include the header in the output
            CURLOPT_HEADER => 0,
            // True to Exclude the body from the output
            CURLOPT_NOBODY => 0,
            // TRUE to follow any "Location: " header that the server
            // sends as part of the HTTP header (note this is recursive,
            // PHP will follow as many "Location: " headers that it is sent,
            // unless CURLOPT_MAXREDIRS is set).
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_MAXREDIRS => 10,
            // TRUE to return the transfer as a string of the return
            // value of curl_exec() instead of outputting it out directly.
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_BINARYTRANSFER => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_ENCODING => 'gzip, deflate',
        ];

        curl_setopt_array($ch, $defaults);
    }

    /**
     * @param $message
     * @param $data
     */
    private static function debug($message, $data)
    {
        /** @var \App\Library\ELogger $classLog */
        $classLog = '\App\Library\ELogger';
        if (class_exists($classLog)) {
            $classLog::debug($message, $data, $classLog::LOG_GROUP_CURL);
        }
    }

    /**
     * @param $url
     * @param $method
     * @param $postParams
     * @param $headers
     * @param $response
     */
    private static function log($url, $method, $postParams, $headers, $response)
    {
        /** @var \App\Library\RawDataStorage $classLog */
        $classLog = '\App\Library\RawDataStorage';
        if (! class_exists($classLog)) {
            return false;
        }

        $parse = parse_url($url);
        $baseUrl = $parse ? sprintf(
            '%s://%s%s',
            $parse['scheme'] ?? '',
            $parse['host'] ?? '',
            $parse['path'] ?? ''
        ) : '';

        $data = [
            'url' => $url,
            'method' => $method,
            'base_url' => $baseUrl,
            'query_params' => empty($parse['query']) ? [] : $parse['query'],
            'post_params' => $postParams,
            'headers' => $headers,
            'body' => $response->body === false ? '' : $response->body,
            'exec_start' => intval($response->executeStart),
            'exec_finish' => intval($response->executeFinish),
            'exec_time' => $response->executeFinish - $response->executeStart,
            'retried' => $response->retried,
            'error_msg' => $response->errorMessage,
            'http_code' => $response->httpCode,
        ];

        return $classLog::store(config('curl.log.db', 'log_curl'), $data);
    }
}

/**
 * Class CurlResponse.
 */
class CurlResponse
{
    public $ch;

    public $headers;
    protected $convertedHeaders;
    public $body;

    public $errorNo;
    public $errorMessage;
    public $httpCode;
    public $executeStart;
    public $executeFinish;
    public $retried;

    /**
     * CurlResponse constructor.
     * @param $curlHandle
     * @param $rawResponse
     * @param $responseHeaders
     */
    public function __construct($curlHandle, $rawResponse, $responseHeaders)
    {
        $this->ch = $curlHandle;
        $this->headers = $responseHeaders;
        $this->convertedHeaders = [];
        if ($responseHeaders) {
            foreach ($responseHeaders as $key => $header) {
                $this->convertedHeaders[strtolower($key)] = $header;
            }
        }

        $this->errorMessage = $rawResponse === false ? curl_error($this->ch) : '';
        $this->errorNo = $rawResponse === false ? curl_errno($this->ch) : 0;
        $this->body = $rawResponse;
        $this->httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    }

    /**
     * @param bool $assoc
     * @return array|null
     */
    public function getJson($assoc = true)
    {
        return @json_decode($this->body, $assoc);
    }

    /**
     * @param $key
     * @return string|null
     */
    public function getHeader($key)
    {
        if (! $this->convertedHeaders) {
            return null;
        }

        $key = strtolower($key);
        if (isset($this->convertedHeaders[$key])) {
            return $this->convertedHeaders[$key];
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getHeaderCookie()
    {
        return $this->getHeader('set-cookie');
    }

    /**
     * @return float
     */
    public function getExecuteTotal()
    {
        return $this->executeFinish - $this->executeStart;
    }
}
