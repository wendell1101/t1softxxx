<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_wickets9_game_logs_20211224 extends CI_Migration {

	private $tableName = 'wickets9_game_logs';

	public function up() {
		//add column
        $fields = array(
            'matchOddsReq' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
        );
		
        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('matchOddsReq', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
	}

	public function down() {

	}
}