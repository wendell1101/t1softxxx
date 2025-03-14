<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_bbgame_game_logs_20220412 extends CI_Migration {

	private $tableName = 'bbgame_game_logs';

	public function up() {
		$fields = array(
			'betAmount' => array(
                'type' => 'DOUBLE',
                'null' => true
			),
		);
		

		if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('betAmount', $this->tableName)){
				$this->dbforge->add_column($this->tableName, $fields);
            }
        }
	}

	public function down() {
		if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('betAmount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'betAmount');
            }
        }
	}
}