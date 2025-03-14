<?php

/**
 *
 * @deprecated
 *
 */
class Api_controller extends CI_Controller {

	function __construct() {
		parent::__construct();
		exit(1);
	}

	function index() {
		echo 'Initialize test!';
	}

}