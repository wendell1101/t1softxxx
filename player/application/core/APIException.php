<?php

class APIException extends \Exception
{
    protected $_data = null;

    public function __construct($message = "", $code = 0, $data = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->_data = $data;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function getResult()
    {
        $result = [
            'code' => $this->getCode(),
            'data' => $this->getData(),
            'errorMessage' => $this->getMessage()
        ];

        return $result;
    }
}
