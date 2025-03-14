<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_player_session_files_relay_20230801 extends CI_Migration {

	private $tableName = 'player_session_files_relay';

	public function up() {

        # remove field, deleted_at
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('deleted_at', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'deleted_at');
            }
        }

	}

	public function down() {

	}
}
