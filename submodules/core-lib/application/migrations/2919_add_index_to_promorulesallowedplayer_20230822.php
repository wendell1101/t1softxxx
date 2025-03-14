<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_promorulesallowedplayer_20230822 extends CI_Migration {

	private $tableName = 'promorulesallowedplayer';

	public function up() {
        $this->load->model('player_model');

		if( $this->utils->table_really_exists($this->tableName) ){
            if( $this->db->field_exists('promoruleId', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_promoruleId', 'promoruleId');
            }

            if( $this->db->field_exists('playerId', $this->tableName) ){
				$this->player_model->addIndex($this->tableName, 'idx_playerId', 'playerId');
            }
        }
	}

	public function down() {

	}
}