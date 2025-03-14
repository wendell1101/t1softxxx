<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_player_oauth2_refresh_tokens_202309279 extends CI_Migration {

	private $tableNames = ['player_oauth2_refresh_tokens', 'player_oauth2_access_tokens'];


	public function up() {
        $this->load->model('player_model');

        foreach($this->tableNames as $tableName){
            if( $this->utils->table_really_exists($tableName) ){
                if( $this->db->field_exists('expires_at', $tableName) ){
                    $this->player_model->addIndex($tableName, 'idx_expires_at', 'expires_at');
                }
            }
        }
	}

	public function down() {

	}
}