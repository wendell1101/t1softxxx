<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class ApiException extends \Exception {
    private $stringCode;
    private $status;

    public function __construct($code, $message, $status = 200, \Exception $previous = null) {
        parent::__construct($message, $status, $previous);
        $this->stringCode = $code;
        $this->status = $status;
    }

    public function getStringCode() {
        return $this->stringCode;
    }

    public function getStatus() {
        return $this->status;
    }
}
