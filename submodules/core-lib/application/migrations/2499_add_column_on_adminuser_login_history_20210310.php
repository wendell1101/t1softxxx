<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_on_adminuser_login_history_20210310 extends CI_Migration {

	private $tableName = 'adminuser_login_history';

	public function up() {

		$fields = array(
            'enable_otp_on_adminusers' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'enable_otp_on_this_user' => array(
                'type' => 'INT',
                'null' => true,
            ),
		);

		if (!$this->db->field_exists('enable_otp_on_adminusers', $this->tableName)) {
            $this->dbforge->add_column($this->tableName, $fields);
		}

	}

    public function down(){
    }
}