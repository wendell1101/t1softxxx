<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// class Migration_add_columns_to_cashback_tables_20210623 extends CI_Migration {
class Migration_add_column_to_favorite_game_20210820 extends CI_Migration {

	private $tableName = 'favorite_game';
    // private $tableName_total_cashback_player_game_daily = 'total_cashback_player_game_daily';

    public function up() {
        $this->load->model('player_model');
        if($this->utils->table_really_exists($this->tableName)){

			if( ! $this->db->field_exists('external_game_id', $this->tableName)){
				// add column
                $fields = [
                    'external_game_id' => ['type' => 'VARCHAR', 'constraint' => '300', 'null' => TRUE ]
                ];
				$this->dbforge->add_column($this->tableName, $fields);
				// add index
				$this->load->model([ 'player_model' ]);
				$this->player_model->addIndex($this->tableName, 'idx_platform_ext_game_id', 'game_platform_id, external_game_id');
            }
        }

    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
			if( $this->db->field_exists('external_game_id', $this->tableName)){
				// drop index
				$this->load->model([ 'player_model' ]);
				$this->player_model->dropIndex($this->tableName, 'idx_platform_ext_game_id');
				// drop column
                $this->dbforge->drop_column($this->tableName, 'external_game_id');
            }
        }

    }
}
