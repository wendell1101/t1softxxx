<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_columns_to_remote_wallet_transactions_20230417 extends CI_Migration
{
    private $tableName = 'remote_wallet_transactions';

    public function up(){
		$fields = array(
			'uniqueid' => array(
				'type' => 'VARCHAR',
                'constraint' => '200',
				'null' => TRUE,
			),
		);

		if(!$this->db->field_exists('uniqueid', $this->tableName)){
			$this->dbforge->add_column($this->tableName, $fields);
			$this->load->model('player_model');
			$this->player_model->addIndex($this->tableName, 'idx_uniqueid', 'uniqueid');
		}
	}

	public function down(){

	}

}
