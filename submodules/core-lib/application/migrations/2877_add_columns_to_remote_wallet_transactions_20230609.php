<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_columns_to_remote_wallet_transactions_20230609 extends CI_Migration
{
    private $tableName = 'remote_wallet_transactions';

    public function up(){
		$fields = array(
			'error_code' => array(
				'type' => 'INT',
                'null' => TRUE,
			),
		);

		if(!$this->db->field_exists('error_code', $this->tableName)){
			$this->dbforge->add_column($this->tableName, $fields);
			$this->load->model('player_model');
			$this->player_model->addIndex($this->tableName, 'idx_error_code', 'error_code');
		}
	}

	public function down(){

	}

}
