<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_spadegaming_game_logs_20230703 extends CI_Migration {

	private $tableName = 'spadegaming_game_logs';

	public function up() {
        $this->load->model('player_model');

		if( $this->utils->table_really_exists($this->tableName) ){
            if( $this->db->field_exists('ticketTime', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_ticketTime', 'ticketTime');
            }

            if( $this->db->field_exists('UserName', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_UserName', 'UserName');
            }

            if( $this->db->field_exists('gameCode', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_gameCode', 'gameCode');
            }

            if( $this->db->field_exists('updated_at', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            }

            if( $this->db->field_exists('external_uniqueid', $this->tableName) ){
                $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
            }
        }
	}

	public function down() {

	}
}