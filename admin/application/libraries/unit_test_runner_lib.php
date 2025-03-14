<?php

/**
 * Undocumented class
 */
abstract class Unit_test_runner_lib {
    const REQUEST_DATA_GET = 2 ** 0;
    const REQUEST_DATA_POST = 2 ** 1;
    const REQUEST_DATA_REQUEST = 2 ** 2;
    const REQUEST_DATA_INPUT = 2 ** 3;

    const REQUEST_DATA_INPUT_JSON = 'json';
    const REQUEST_DATA_INPUT_FORM = 'form';

    /** @var Unit_test_runner */
    protected $CI;

    protected $_excludeMethods = ['test', 'testTarget', 'testAll'];

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    protected function _excludeTest($methodName)
    {
        $this->_excludeMethods[] = $methodName;
    }

    function getCallingFunctionName()
    {
        $trace = debug_backtrace();
        if (isset($trace[2]['function'])) {
            return $trace[2]['function'];
        }
        return '';
    }

    /**
     * setInput function
     *
     * @param mixed $input
     * @param int $flag static::REQUEST_DATA_GET | static::REQUEST_DATA_POST | static::REQUEST_DATA_REQUEST | static::REQUEST_DATA_INPUT
     * @param string $type
     * @return self
     */
    public function setRequestData($input, $flag = null, $type = 'json')
    {
        global $_GET, $_POST, $_REQUEST;

        if(static::REQUEST_DATA_GET === ($flag & static::REQUEST_DATA_GET)) {
            $_GET = $input;
        }

        if(static::REQUEST_DATA_POST === ($flag & static::REQUEST_DATA_POST)) {
            $_POST = $input;
        }

        if(static::REQUEST_DATA_REQUEST === ($flag & static::REQUEST_DATA_REQUEST)) {
            $_REQUEST = $input;
        }
        
        if(static::REQUEST_DATA_INPUT === ($flag & static::REQUEST_DATA_INPUT)) {
            if($type == 'json') {
                $this->CI->mock_php_stream->set_data(json_encode($input));
            } else {
                $this->CI->mock_php_stream->set_data(http_build_query($input));
            }
        }

        return $this;
    }

    abstract public function init();

    public function assert($test, $expected = TRUE, $notes = '', &$result = false)
    {
        $notes = (is_object($notes) || is_array($notes)) ? @var_export($notes, true) : $notes;

        $this->CI->unit->run($test, $expected, $this->getCallingFunctionName(), $notes, $result);

        if(FALSE === $result) {
            throw new \Exception();
        }
    }

    public function getTestCases()
    {
        $classMethods = get_class_methods($this);
        $testCases = [];
        foreach ($classMethods as $method) {
            if (strpos($method, 'test') !== 0 || in_array($method, $this->_excludeMethods)) {
                continue;
            }

            $testCases[] = $method;
        }

        return $testCases;
    }

    public function testAll()
    {
        $testCases = $this->getTestCases();
        foreach($testCases as $method) {
            $this->$method();
        }
    }
}