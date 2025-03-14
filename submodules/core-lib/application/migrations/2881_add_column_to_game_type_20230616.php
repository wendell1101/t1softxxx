<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_column_to_game_type_20230616 extends CI_Migration
{
    private $tableName = 'game_type';

    public function up(){
		$fields = array(
			'extra_info' => array(
				'type' => 'JSON',
                'null' => TRUE,
			),
		);

		if(!$this->db->field_exists('extra_info', $this->tableName)){
			$this->dbforge->add_column($this->tableName, $fields);
			$this->load->model('player_model');
		}
	}

	public function down(){

	}

}
