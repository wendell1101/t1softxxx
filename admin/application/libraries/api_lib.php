<?php 
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/../exceptions/ApiException.php';

/**
 * API Library
 *
 * Provides a set of factory functions for handling API-related operations.
 * 
 * How to load:
 *   $this->CI->load->library('api_lib') 
 *   OR 
 *   $this->load->library('api_lib')
 *
 * Factory function usage:
 *   - Request: $this->api_lib->request(), api_lib()->request(), request()
 *   - Response: $this->api_lib->response(), api_lib()->response(), response()
 *   - Validation: $this->api_lib->validation(), api_lib()->validation(), validation()
 *   - Assertion: $this->api_lib->assertion(), api_lib()->assertion(), assertion()
 *
 * @author Melvin (melvin.php.ph)
 */
class Api_lib {
    protected $CI;
    protected $factory_function_request;
    protected $factory_function_response;
    protected $factory_function_validation;
    protected $factory_function_assertion;

    public $hint = [];
    public $storedData = null;
    public $storedRequestData = null;

    public $httpResponseStatusCodeList = [
        // Success Responses
        200 => [
            'status' => 'success',
            'code' => 200,
            'text' => 'OK',
            'message' => 'Success',
        ],
        201 => [
            'status' => 'success',
            'code' => 201,
            'text' => 'Created',
            'message' => 'Resource successfully created.',
        ],
        202 => [
            'status' => 'success',
            'code' => 202,
            'text' => 'Accepted',
            'message' => 'Request has been accepted for processing.',
        ],
        203 => [
            'status' => 'success',
            'code' => 203,
            'text' => 'Non-Authoritative Information',
            'message' => 'Request processed successfully, but response might be modified.',
        ],
        204 => [
            'status' => 'success',
            'code' => 204,
            'text' => 'No Content',
            'message' => 'Request successfully processed with no content to return.',
        ],
        205 => [
            'status' => 'success',
            'code' => 205,
            'text' => 'Reset Content',
            'message' => 'Request processed successfully; reset the view.',
        ],
        206 => [
            'status' => 'success',
            'code' => 206,
            'text' => 'Partial Content',
            'message' => 'Partial content returned for a specific range.',
        ],
    
        // Redirection Responses
        300 => [
            'status' => 'redirection',
            'code' => 300,
            'text' => 'Multiple Choices',
            'message' => 'Request has multiple possible responses.',
        ],
        301 => [
            'status' => 'redirection',
            'code' => 301,
            'text' => 'Moved Permanently',
            'message' => 'Resource has been permanently moved to a new URI.',
        ],
        302 => [
            'status' => 'redirection',
            'code' => 302,
            'text' => 'Found',
            'message' => 'Resource has been temporarily moved to a new URI.',
        ],
        303 => [
            'status' => 'redirection',
            'code' => 303,
            'text' => 'See Other',
            'message' => 'Client should retrieve resource using a GET method.',
        ],
        304 => [
            'status' => 'redirection',
            'code' => 304,
            'text' => 'Not Modified',
            'message' => 'Resource has not been modified since the last request.',
        ],
        305 => [
            'status' => 'redirection',
            'code' => 305,
            'text' => 'Use Proxy',
            'message' => 'Resource must be accessed through a proxy.',
        ],
        307 => [
            'status' => 'redirection',
            'code' => 307,
            'text' => 'Temporary Redirect',
            'message' => 'Resource has been temporarily moved to a new URI.',
        ],
        308 => [
            'status' => 'redirection',
            'code' => 308,
            'text' => 'Permanent Redirect',
            'message' => 'Resource has been permanently moved to a new URI.',
        ],
    
        // Client Error Responses
        400 => [
            'status' => 'error',
            'code' => 400,
            'text' => 'Bad Request',
            'message' => 'Request could not be understood due to malformed syntax.',
        ],
        401 => [
            'status' => 'error',
            'code' => 401,
            'text' => 'Unauthorized',
            'message' => 'Authentication is required to access the resource.',
        ],
        402 => [
            'status' => 'error',
            'code' => 402,
            'text' => 'Payment Required',
            'message' => 'Reserved for future use.',
        ],
        403 => [
            'status' => 'error',
            'code' => 403,
            'text' => 'Forbidden',
            'message' => 'Server refuses to authorize the request.',
        ],
        404 => [
            'status' => 'error',
            'code' => 404,
            'text' => 'Not Found',
            'message' => 'Requested resource could not be found.',
        ],
        405 => [
            'status' => 'error',
            'code' => 405,
            'text' => 'Method Not Allowed',
            'message' => 'HTTP method is not supported by the resource.',
        ],
        406 => [
            'status' => 'error',
            'code' => 406,
            'text' => 'Not Acceptable',
            'message' => 'Server cannot produce a response matching the Accept header.',
        ],
        407 => [
            'status' => 'error',
            'code' => 407,
            'text' => 'Proxy Authentication Required',
            'message' => 'Client must authenticate with the proxy.',
        ],
        408 => [
            'status' => 'error',
            'code' => 408,
            'text' => 'Request Timeout',
            'message' => 'Request timed out.',
        ],
        409 => [
            'status' => 'error',
            'code' => 409,
            'text' => 'Conflict',
            'message' => 'Request could not be completed due to a conflict.',
        ],
        410 => [
            'status' => 'error',
            'code' => 410,
            'text' => 'Gone',
            'message' => 'Resource is no longer available and will not be available again.',
        ],
        411 => [
            'status' => 'error',
            'code' => 411,
            'text' => 'Length Required',
            'message' => 'Request did not specify the length of its content.',
        ],
        412 => [
            'status' => 'error',
            'code' => 412,
            'text' => 'Precondition Failed',
            'message' => 'Server does not meet one of the preconditions.',
        ],
        413 => [
            'status' => 'error',
            'code' => 413,
            'text' => 'Payload Too Large',
            'message' => 'Request payload is too large.',
        ],
        414 => [
            'status' => 'error',
            'code' => 414,
            'text' => 'URI Too Long',
            'message' => 'Request URI is too long.',
        ],
        415 => [
            'status' => 'error',
            'code' => 415,
            'text' => 'Unsupported Media Type',
            'message' => 'Request media type is not supported.',
        ],
        416 => [
            'status' => 'error',
            'code' => 416,
            'text' => 'Range Not Satisfiable',
            'message' => 'Requested range cannot be satisfied.',
        ],
        417 => [
            'status' => 'error',
            'code' => 417,
            'text' => 'Expectation Failed',
            'message' => 'Expectation specified in the request header could not be met.',
        ],
        418 => [
            'status' => 'error',
            'code' => 418,
            'text' => 'I Am A Teapot',
            'message' => 'A humorous HTTP status code.',
        ],
    
        // Server Error Responses
        500 => [
            'status' => 'error',
            'code' => 500,
            'text' => 'Internal Server Error',
            'message' => 'The server encountered an unexpected condition.',
        ],
        501 => [
            'status' => 'error',
            'code' => 501,
            'text' => 'Not Implemented',
            'message' => 'Server does not support the request method.',
        ],
        502 => [
            'status' => 'error',
            'code' => 502,
            'text' => 'Bad Gateway',
            'message' => 'Server received an invalid response from the upstream server.',
        ],
        503 => [
            'status' => 'error',
            'code' => 503,
            'text' => 'Service Unavailable',
            'message' => 'Server is currently unavailable.',
        ],
        504 => [
            'status' => 'error',
            'code' => 504,
            'text' => 'Gateway Timeout',
            'message' => 'Upstream server failed to send a request in time.',
        ],
        505 => [
            'status' => 'error',
            'code' => 505,
            'text' => 'HTTP Version Not Supported',
            'message' => 'HTTP version used in the request is not supported by the server.',
        ],
    ];

    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_XML = 'application/xml';
    const CONTENT_TYPE_PLAIN = 'text/plain';
    const CONTENT_TYPE_HTML = 'text/html';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_PUT = 'PUT';

    public function __construct() {
        $this->CI =& get_instance();
        $this->factory_function_request = request($this->storedRequestData);
        $this->factory_function_response = response();
        $this->factory_function_validation = validation();
        $this->factory_function_assertion = assertion();
    }

    public function testApi() {
        return __METHOD__;
    }

    /**
     * Returns the request object or function.
     *
     * This method retrieves the request object or function that has been assigned to
     * the `$factory_function_request` property. It serves as an accessor method.
     *
     * @return mixed The request object or function.
     */
    public function request() {
        return $this->factory_function_request;
    }

    /**
     * Returns the response object or function.
     *
     * This method retrieves the response object or function that has been assigned to
     * the `$factory_function_response` property. It serves as an accessor method.
     *
     * @return mixed The response object or function.
     */
    public function response() {
        return $this->factory_function_response;
    }

    /**
     * Returns the validation object or function.
     *
     * This method retrieves the validation object or function that has been assigned to
     * the `$factory_function_validation` property. It serves as an accessor method.
     *
     * @return mixed The validation object or function.
     */
    public function validation() {
        return $this->factory_function_validation;
    }

    /**
     * Returns the assertion object or function.
     *
     * This method retrieves the assertion object or function that has been assigned to
     * the `$factory_function_assertion` property. It serves as an accessor method.
     *
     * @return mixed The assertion object or function.
     */
    public function assertion() {
        return $this->factory_function_assertion;
    }

    /**
     * Stores data in the $storedData array with the specified key.
     *
     * This method allows you to store data in the $storedData array using a key. 
     * If the key already exists, the value will be overwritten.
     *
     * @param string $key   The key to associate with the data.
     * @param mixed  $data  The data to store. Defaults to an empty array if not provided.
     */
    public function storeData($key, $data = []) {
        $this->storedData[$key] = $data;
    }

    /**
     * Retrieves stored data based on the provided key.
     *
     * If no key is provided, it returns the entire $storedData array.
     * If a key is provided, it checks if that key exists in the $storedData 
     * array and returns the associated value, or null if the key doesn't exist.
     *
     * @param string $key  The key to look for in the stored data. Defaults to an empty string.
     * @return mixed       The stored data associated with the key, or the entire array if no key is provided. 
     *                     Returns null if the key is not found.
     */
    public function storedData($key = '') {
        if (empty($key)) {
            return $this->storedData;
        }

        return isset($this->storedData[$key]) ? $this->storedData[$key] : null;
    }

    /**
     * Stores request data in the $storedRequestData property.
     *
     * This method allows you to store data that represents the request 
     * in the $storedRequestData property. If no data is provided, it stores an empty array.
     *
     * @param mixed $data  The data to store. Defaults to an empty array if not provided.
     */
    public function storeRequestData($data = []) {
        $this->storedRequestData = $data;
    }

    /**
     * Retrieves stored request data based on the provided key.
     *
     * If no key is provided, it returns the entire $storedRequestData array.
     * If a key is provided, it checks if that key exists in the $storedRequestData 
     * array and returns the associated value, or null if the key doesn't exist.
     *
     * @param string $key  The key to look for in the stored request data. Defaults to an empty string.
     * @return mixed       The stored request data associated with the key, or the entire array if no key is provided. 
     *                     Returns null if the key is not found.
     */
    public function storedRequestData($key = '') {
        if (empty($key)) {
            return $this->storedRequestData;
        }

        return isset($this->storedRequestData[$key]) ? $this->storedRequestData[$key] : null;
    }

    public function throwException($code, $message, $status) {
        throw new ApiException($code, $message, $status);
    }

    public function httpResponse($code, $isObject = true) {
        $httpResponse = !empty($this->httpResponseStatusCodeList[$code]) ? $this->httpResponseStatusCodeList[$code] : $this->httpResponseStatusCodeList[200];

        return $isObject ? (object) $httpResponse : $httpResponse;
    }

    /**
     * Looks up a value in a multidimensional array using dot notation.
     *
     * This method allows you to access nested values in an array using dot notation for the key. 
     * If a key in the path doesn't exist, it returns a default value (which is null by default).
     * Additionally, if a string value is found that seems to be JSON-encoded, it will attempt to decode it 
     * into an associative array before continuing the lookup.
     *
     * @param array $array The array to search.
     * @param string $key The key or path to the value, using dot notation.
     * @param mixed $default The default value to return if the key is not found. Defaults to null.
     * 
     * @return mixed The value found at the given key, or the default value if the key is not found.
     */
    public function lookup($array, $key, $default = null) {
        if (!is_array($array) || empty($key)) {
            return $default;
        }

        $keys = explode('.', $key);

        foreach ($keys as $segment) {
            if (!array_key_exists($segment, $array)) {
                return $default;
            }

            $value = $array[$segment];

            // If the value is a string that is likely JSON, decode it into an array
            if (is_string($value) && $this->isLikelyJson($value)) {
                $value = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $array[$segment] = $value;
                }
            }

            $array = $value;
        }

        return !empty($array) ? $array : $default;
    }

    private function isLikelyJson($string) {
        return is_string($string) && (strpos($string, '{') === 0 || strpos($string, '[') === 0);
    }

    public function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function responseData($settings = [
        'data' => [],
        'httpStatusCode' => 200,
        'httpStatusText' => '',
        'contentType' => self::CONTENT_TYPE_JSON,
        'addOrigin' => true,
        'origin' => '*',
        'xml' => false,
        'standAlone' => false,
    ]) {
        $defaultSettings = [
            'data' => [],
            'httpStatusCode' => 200,
            'httpStatusText' => '',
            'contentType' => self::CONTENT_TYPE_JSON,
            'addOrigin' => true,
            'origin' => '*',
            'xml' => false,
            'standAlone' => false,
        ];

        $settings = array_merge($defaultSettings, $settings);
        $validContentTypes = [self::CONTENT_TYPE_JSON, self::CONTENT_TYPE_XML, self::CONTENT_TYPE_PLAIN];

        if (!in_array($settings['contentType'], $validContentTypes)) {
            $settings['contentType'] = self::CONTENT_TYPE_JSON;
            $settings['httpStatusCode'] = 415;
            $settings['data'] = $this->httpResponse($settings['httpStatusCode']);
        }

        if (!is_array($settings['data']) && !is_object($settings['data'])) {
            $settings['contentType'] = self::CONTENT_TYPE_JSON;
            $settings['httpStatusCode'] = 400;
            $settings['data'] = $this->httpResponse($settings['httpStatusCode']);
        }

        switch ($settings['contentType']) {
            case self::CONTENT_TYPE_JSON:
                $settings['data'] = json_encode($settings['data']);
                break;
            case self::CONTENT_TYPE_XML:
                $settings['data'] = $this->arrayToXml($settings['data'], $settings['xml'], $settings['standAlone']);
                break;
            case self::CONTENT_TYPE_PLAIN:
                $settings['data'] = trim(stripslashes(json_encode($settings['data'])), '"');
                break;
            default:
                $settings['data'] = json_encode($settings['data']);
                break;
        }

        if ($settings['addOrigin']) {
            $this->CI->output->set_header("Access-Control-Allow-Origin: {$settings['origin']}");
        }

        if (empty($settings['httpStatusText'])) {
            $httpResponse = $this->httpResponse($settings['httpStatusCode']);
            $settings['httpStatusText'] = !empty($httpResponse->text) ? $httpResponse->text : '';
        }

        // Set output headers and content
        $this->CI->output->set_status_header($settings['httpStatusCode'], $settings['httpStatusText']);
        $this->CI->output->set_content_type($settings['contentType']);
        $this->CI->output->set_output($settings['data']);

        return $this->CI->output;
    }

    public function isXml($string) {
        libxml_use_internal_errors(true);
        simplexml_load_string($string);
        return (count(libxml_get_errors()) === 0);
    }

    public function xmlToArray($xmlString) {
        $xmlObject = simplexml_load_string($xmlString);
        return json_decode(json_encode($xmlObject), true);
    }

    public function arrayToXml($data, $xml = false, $standAlone = false) {
        if ($xml === false) {
            $xml = new SimpleXMLElement($standAlone ? '<?xml version="1.0" standalone="yes"?>' : '<?xml version="1.0" encoding="UTF-8"?>' . '<root/>');
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->arrayToXml($value, $xml->addChild($key), $standAlone);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }

        return $xml->asXML();
    }

    public function arrayToHtmlTable($data) {
        if (!empty($data)) {
            $table = "<table border='1' style='width:50%; text-align: center;'>";
            $table .= '<thead>';
                foreach ($data as $headKey => $headValue) {
                    $table .= '<tr>';
                        foreach ($headValue as $dataKey => $dataValue) {
                            if ($dataKey) {
                                $table .= "<th>";
                                $table .= $dataKey;
                                $table .= "</th>";
                            }
                        }
                    $table .= '</tr>';
                    break;
                }
            $table .= '</thead>';
            $table .= '<tbody>';
                foreach ($data as $bodyKey => $bodyValue) {
                    $table .= '<tr>';
                        foreach ($bodyValue as $dataKey => $dataValue) {
                            $table .= "<td>";
                            $table .= $dataValue;
                            $table .= "</td>";
                        }
                    $table .= '</tr>';
                }
            $table .= '</tbody>';
            $table .= '</table>';

            return $table;
        }

        return 'noData';
    }

    public function setHint($key, $value, $refresh = false) {
        if ($refresh) {
            $this->clearHint();
        }

        $this->hint[$key] = $value;

        return [$key => $value];
    }

    public function clearHint() {
        $this->hint = [];
    }

    public function hint($key = null) {
        if (!empty($key)) {
            if (isset($this->hint[$key])) {
                return $this->hint[$key];
            } else {
                return false;
            }
        }

        return $this->hint;
    }
}

class Request {
    protected $CI;
    public $rawInput = null;
    public $parsedData;

    public function __construct($parsedData = null) {
        $this->CI =& get_instance();

        // for caching data
        $this->parsedData = $parsedData;
    }

    public function testRequest() {
        return __METHOD__;
    }

    public function params($key = null, $default = null, $httpMethod = 'POST', $isObject = false) {
        if ($httpMethod == Api_lib::HTTP_METHOD_GET) {
            $data = $_GET;
        } else {
            if ($this->parsedData === null) {
                if ($this->rawInput === null) {
                    $this->rawInput = file_get_contents("php://input");
                }

                $input = $this->rawInput;

                if (api_lib()->isXml($input)) {
                    $this->parsedData = api_lib()->xmlToArray($input);
                } else {
                    $this->parsedData = json_decode($input, true);
                }
            }

            $data = $this->parsedData;
        }

        $result = is_array($data) ? $data : [];
    
        if (!empty($key)) {
            return api_lib()->lookup($result, $key, $default);
        }

        return $isObject ? (object) $result : $result;
    }

    public function headers($key = null, $default = null) {
        static $cachedHeaders = null;
    
        if ($cachedHeaders === null) {
            $cachedHeaders = getallheaders();
        }
    
        if (!empty($key)) {
            return isset($cachedHeaders[$key]) ? $cachedHeaders[$key] : $default;
        }
    
        return $cachedHeaders;
    }
}

class Response {
    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    public function testResponse() {
        return __METHOD__;
    }

    public function json($data = [], $status = 200, $headers = []) {
        if (!is_array($data) && !is_object($data)) {
            $status = 400;
            $data = api_lib()->httpResponse($status);
        }

        if (!empty($headers)) {
            foreach ($headers as $header) {
                $this->CI->output->set_header($header);
            }
        }

        $this->CI->output
            ->set_status_header($status, api_lib()->httpResponse($status)->text)
            ->set_content_type(Api_lib::CONTENT_TYPE_JSON)
            ->set_output(json_encode($data, JSON_PRETTY_PRINT));

        return $this->CI->output;
    }

    public function xml($data = [], $status = 200, $headers = [], $isXml = false, $standAlone = false) {
        if (!is_array($data) && !is_object($data)) {
            $status = 400;
            $data = api_lib()->httpResponse($status);
        }

        if (!empty($headers)) {
            foreach ($headers as $header) {
                $this->CI->output->set_header($header);
            }
        }

        $data = api_lib()->arrayToXml($data, $isXml, $standAlone);

        $this->CI->output
            ->set_status_header($status, api_lib()->httpResponse($status)->text)
            ->set_content_type(Api_lib::CONTENT_TYPE_XML)
            ->set_output($data);

        return $this->CI->output;
    }

    public function plain($data = '', $status = 200, $headers = []) {
        if (!empty($headers)) {
            foreach ($headers as $header) {
                $this->CI->output->set_header($header);
            }
        }

        $this->CI->output
            ->set_status_header($status, api_lib()->httpResponse($status)->text)
            ->set_content_type(Api_lib::CONTENT_TYPE_PLAIN)
            ->set_output($data);

        return $this->CI->output;
    }

    public function htmlTable($data = [], $status = 200, $headers = []) {
        $this->CI->load->library('table');

        if (!is_array($data) && !is_object($data)) {
            $status = 400;
            $data = api_lib()->httpResponse($status);
        }

        if (!empty($headers)) {
            foreach ($headers as $header) {
                $this->CI->output->set_header($header);
            }
        }

        $table = $this->CI->table->generate($data);

        $this->CI->output
            ->set_status_header($status, api_lib()->httpResponse($status)->text)
            ->set_content_type(Api_lib::CONTENT_TYPE_HTML)
            ->set_output($table);

        return $this->CI->output;
    }
}

class Validation {
    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    private function isDotNotationValid($dotNotation) {
        return is_string($dotNotation) && strpos(trim($dotNotation), '.') !== false;
    }

    private function containsColon($param) {
        return strpos($param, ':') !== false;
    }

    private function isMultidimensionalArray($array, $any_array_key_type = false) {
        // return is_array($array) && count(array_filter($array, 'is_array')) > 0;

        // default, will check array key numeric
        if (isset($array[0])) {
            return true;
        }

        // will check array key numeric and string
        if ($any_array_key_type) {
            foreach ($array as $value) {
                if (isset($value) && is_array($value)) {
                    return true;
                } else {
                    break;
                }
            }
        }

        return false;
    }

    private function isValidDateFormat($date, $format) {
        $dateTime = DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }

    public function validateRequest($request_params, $rule_sets, $strict = true) {
        $is_valid = true;
        $message = 'valid';
        $param = null;

        foreach ($rule_sets as $param => $rules) {
            $is_dot_notation_valid = $this->isDotNotationValid($param);
    
            if ($is_dot_notation_valid) {
                $request_param = api_lib()->lookup($request_params, $param);
            } else {
                $request_param = isset($request_params[$param]) ? $request_params[$param] : null;
            }
    
            foreach ($rules as $key => $rule) {
                if (is_string($rule) && $this->containsColon($rule)) {
                    $rule = !empty(explode(':', $rule)[0]) ? explode(':', $rule)[0] : $rule;
                    $value = !empty(explode(':', $rule)[1]) ? explode(':', $rule)[1] : 0;
                }
    
                if (is_string($key)) {
                    if (is_array($rule)) {
                        $rule = $key;
                        $value = $rules[$key];
                    } else {
                        $value = $rule;
                        $rule = $key;
                    }
                }
    
                switch ($rule) {
                    case 'optional':
                        $is_valid = true;
                        break;
                    case 'array':
                        if (!is_null($request_param) && !is_array($request_param)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be an ' . $rule;
                        }
                        break;
                    case 'multidimensional_array':
                        if (!is_null($request_param) && !$this->isMultidimensionalArray($request_param, true)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'nullable':
                        continue 2;
                    case 'required':
                        if ($is_dot_notation_valid) {
                            if (!in_array('nullable', $rules)) {
                                if (empty($request_param)) {
                                    $is_valid = false;
                                    $message = 'Parameter ' . $param . ' is ' . $rule;
                                }
                            }
                        } else {
                            if (in_array('nullable', $rules) || in_array('boolean', $rules)) {
                                if (!array_key_exists($param, $request_params)) {
                                    $is_valid = false;
                                    $message = 'Parameter ' . $param . ' is ' . $rule;
                                }
                            } else {
                                if (!array_key_exists($param, $request_params) || empty($request_param)) {
                                    $is_valid = false;
                                    $message = 'Parameter ' . $param . ' is ' . $rule;
                                }
                            } 
                        }
                        break;
                    case 'string':
                        if (!is_null($request_param) && !is_string($request_param)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'integer':
                        if (!is_null($request_param) && !is_integer($request_param)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'float':
                        if (!is_null($request_param) && !is_float($request_param)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'double':
                        if (!is_null($request_param) && !is_double($request_param)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'numeric':
                        if (!is_null($request_param) && !is_numeric($request_param)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'positive':
                        if (!is_null($request_param) && $request_param < 0) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'negative':
                        if (!is_null($request_param) && $request_param > 0) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'greater_than':
                        if (!is_null($request_param) && intval($request_param) <= $value) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' is expected to be ' . str_replace('_', ' ', $rule) . ' ' . $value;
                        }
                        break;
                    case 'less_than':
                        if (!is_null($request_param) && intval($request_param) >= $value) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . str_replace('_', ' ', $rule) . ' ' . $value;
                        }
                        break;
                    case 'minimum_size':
                        if (!is_null($request_param) && ($value !== null) && (strlen($request_param) < $value)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . str_replace('_', ' ', $rule) . ' ' . $value;
                        }
                        break;
                    case 'maximum_size':
                        if (!is_null($request_param) && ($value !== null) && (strlen($request_param) > $value)) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' must be ' . str_replace('_', ' ', $rule) . ' ' . $value;
                        }
                        break;
                    case 'boolean':
                        if (!is_null($request_param) && !is_bool($request_param)) {
                            if (in_array($request_param, [0, 1], true)) {
                                $is_valid = true;
                            } else {
                                $is_valid = false;
                                $message = 'Parameter ' . $param . ' must be ' . $rule . ' type';
                            }
                        }
                        break;
                    case 'expected_value':
                        if ($strict) {
                            if (!is_null($request_param) && $request_param != $value) {
                                $is_valid = false;
                                $message = "Invalid parameter {$param}";
                            }
                        } else {
                            if (!is_null($request_param) && strtolower($request_param) != strtolower($value)) {
                                $is_valid = false;
                                $message = "Invalid parameter {$param}";
                            }
                        }
                        break;
                    case 'expected_value_in':
                        if (!is_array($value)) {
                            $is_valid = false;
                            $message = "Rule ({$rule}): must be an array";
                            break;
                        }
    
                        if ($strict) {
                            if (!is_null($request_param) && is_array($value) && !in_array($request_param, $value)) {
                                $is_valid = false;
                                $message = "Invalid parameter {$param}";
                            }
                        } else {
                            if (!is_null($request_param) && is_array($value) && !in_array(strtolower($request_param), $value)) {
                                $is_valid = false;
                                $message = "Invalid parameter {$param}";
                            }
                        }
                        break;
                    case 'ip_address':
                        if (!is_null($request_param) && !filter_var($request_param, FILTER_VALIDATE_IP, [FILTER_FLAG_IPV4, FILTER_FLAG_IPV6])) {
                            $is_valid = false;
                            $message = "Invalid parameter {$param}, must be a valid IP address";
                        }
                        break;
                    case 'min':
                        if (!is_null($request_param)) {
                            if (is_string($request_param) && strlen($request_param) < $value) {
                                $is_valid = false;
                                $message = 'Parameter ' . $param . ' ' . str_replace('_', ' ', $rule) . ' length ' . $value;
                                break;
                            }
    
                            if (is_numeric($request_param) && $request_param < $value) {
                                $is_valid = false;
                                $message = "Parameter {$param} {$rule} {$value}";
                                break;
                            }
                        }
                        break;
                    case 'max':
                        if (!is_null($request_param)) {
                            if (is_string($request_param) && strlen($request_param) > $value) {
                                $is_valid = false;
                                $message = 'Parameter ' . $param . ' ' . str_replace('_', ' ', $rule) . ' length ' . $value;
                                break;
                            }
    
                            if (is_numeric($request_param) && $request_param > $value) {
                                $is_valid = false;
                                $message = "Parameter {$param} {$rule} {$value}";
                                break;
                            }
                        }
                        break;
                    case 'min_length':
                        if (!is_null($request_param) && strlen($request_param) < $value) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' ' . str_replace('_', ' ', $rule) . ' length ' . $value;
                            break;
                        }
                        break;
                    case 'max_length':
                        if (!is_null($request_param) && strlen($request_param) > $value) {
                            $is_valid = false;
                            $message = 'Parameter ' . $param . ' ' . str_replace('_', ' ', $rule) . ' length ' . $value;
                            break;
                        }
                        break;
                    case 'date_format':
                    if (!is_null($request_param) && !$this->isValidDateFormat($request_param, $value)) {
                        $is_valid = false;
                        $message = 'Parameter ' . $param . ' must be in the format ' . $value;
                    }
                    break;
                    default:
                        $is_valid = false;
                        $message = "Invalid rule '" . $rule . "' on parameter '" . $param . "'";
                        break;
                }
            }

            if (!$is_valid) {
                break;
            }
        }

        $result = [
            'is_valid' => $is_valid,
            'message' => $message,
        ];

        if (!$is_valid) {
            $result['key'] = $param;
        }
    
        return (object) $result;
    }

    public function validateBasicAuthRequest($username, $password, $separator = ':', $allowEmptyPassword = false) {
        $authorizationHeader = api_lib()->request()->headers('Authorization');
        $expectedAuthorization = 'Basic ' . base64_encode($this->CI->utils->mergeArrayValues([$username, $password], $separator));

        $explodeAuthHeader = explode(' ', $authorizationHeader);
        $providedAuthorization = isset($explodeAuthHeader[1]) ? $explodeAuthHeader[1] : null;

        $decodedAuth = base64_decode($providedAuthorization);
        $authParts = explode($separator, $decodedAuth);
    
        $authUsername = isset($authParts[0]) ? $authParts[0] : null;
        $authPassword = isset($authParts[1]) ? $authParts[1] : null;
    
        if ($authUsername != $username) {
            $message = 'Unauthorized: Invalid Username';
            $isValid = false;
        } elseif ($authPassword != $password) {
            $message = 'Unauthorized: Invalid Password';
            $isValid = false;
        } elseif (!$allowEmptyPassword && (empty($authPassword) || empty($password))) {
            $message = 'Unauthorized: Empty password not allowed';
            $isValid = false;
        } elseif ($authorizationHeader != $expectedAuthorization) {
            $message = 'Unauthorized: Authorization mismatch';
            $isValid = false;
        } else {
            $isValid = true;
            $message = 'Authorized';
        }
    
        return compact('isValid', 'message');
    }
}

class Assertion {
    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    private function throwException($code, $message, $status) {
        api_lib()->throwException($code, $message, $status);
    }

    public function assertTrue($value, $code = 0, $message = 'Expected value to be true, but it was false', $status = 200) {
        if (!$value) {
            $this->throwException($code, $message, $status);
        }
    }

    public function assertFalse($value, $code = 0, $message = 'Expected value to be false, but it was true', $status = 200) {
        if ($value) {
            $this->throwException($code, $message, $status);
        }
    }

    public function assertEmpty($value, $code = 0, $message = 'Expected value to be empty, but it was not', $status = 200) {
        if (!empty($value)) {
            $this->throwException($code, $message, $status);
        }
    }

    public function assertNotEmpty($value, $code = 0, $message = 'Expected value to be not empty, but it was', $status = 200) {
        if (empty($value)) {
            $this->throwException($code, $message, $status);
        }
    }

    public function assertEquals($actual, $expected, $code = 0, $message = 'Expected values to be equal, but they were not', $status = 200, $strict = false) {
        if (($strict && $actual !== $expected) || (!$strict && $actual != $expected)) {
            $this->throwException($code, $message, $status);
        }
    }

    public function assertNotEquals($actual, $expected, $code = 0, $message = 'Expected values to be different, but they were equal', $status = 200, $strict = false) {
        if (($strict && $actual === $expected) || (!$strict && $actual == $expected)) {
            $this->throwException($code, $message, $status);
        }
    }

    public function assertInArray($value, $array, $code = 0, $message = 'Value is not in the expected array', $status = 200, $strict = false) {
        if (!in_array($value, $array, $strict)) {
            $this->throwException($code, $message, $status);
        }
    }

    public function assertNotInArray($value, $array, $code = 0, $message = 'Value should not be in the array, but it was', $status = 200, $strict = false) {
        if (in_array($value, $array, $strict)) {
            $this->throwException($code, $message, $status);
        }
    }

    public function assertNull($value, $code = 0, $message = 'Expected value to be null, but it was not', $status = 200) {
        if (!is_null($value)) {
            $this->throwException($code, $message, $status);
        }
    }

    public function assertNotNull($value, $code = 0, $message = 'Expected value to be not null, but it was', $status = 200) {
        if (is_null($value)) {
            $this->throwException($code, $message, $status);
        }
    }
}

function api_lib() {
    return new Api_lib();
}

function request($storedRequestData = null) {
    return new Request($storedRequestData);
}

function response() {
    return new Response();
}

function validation() {
    return new Validation();
}

function assertion() {
    return new Assertion();
}