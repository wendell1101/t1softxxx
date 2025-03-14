<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_player_relay_20230912 extends CI_Migration {

	private $tableName = 'player_relay';

	public function up() {
		$fields = array(
			'second_deposit_datetime' => array(
                'type' => 'DATETIME',
				'null' => true,
			),
            'second_deposit_amount' => array(
				'type' => 'DOUBLE',
				'null' => false,
			),
		);


		if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('second_deposit_datetime', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields);

                $this->player_model->addIndex($this->tableName, 'idx_second_deposit_datetime', 'second_deposit_datetime');
            }
        }
	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('second_deposit_datetime', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'second_deposit_datetime');
            }
            if($this->db->field_exists('second_deposit_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'second_deposit_amount');
            }
        }
	}
}