<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_after_balance_to_pragmaticplay_20210617 extends CI_Migration {

	public function up() {
		$fields = array(
			'after_balance' => array(
				'type' => 'DOUBLE',
				'null' => true,
			),
		);

        // pragmaticplay_idr6_game_logs
        if($this->utils->table_really_exists("pragmaticplay_idr6_game_logs")){
            if(!$this->db->field_exists('after_balance', "pragmaticplay_idr6_game_logs")){
                $this->dbforge->add_column('pragmaticplay_idr6_game_logs', $fields);
            }
        }

        // pragmaticplay_idr7_game_logs
        if($this->utils->table_really_exists("pragmaticplay_idr7_game_logs")){
            if(!$this->db->field_exists('after_balance', "pragmaticplay_idr7_game_logs")){
                $this->dbforge->add_column('pragmaticplay_idr7_game_logs', $fields);
            }
        }

        // pragmaticplay_vnd3_game_logs
        if($this->utils->table_really_exists("pragmaticplay_vnd3_game_logs")){
            if(!$this->db->field_exists('after_balance', "pragmaticplay_vnd3_game_logs")){
                $this->dbforge->add_column('pragmaticplay_vnd3_game_logs', $fields);
            }
        }
		
	}

	public function down() {

        // pragmaticplay_idr6_game_logs
        if($this->utils->table_really_exists("pragmaticplay_idr6_game_logs")){
            if($this->db->field_exists('after_balance', "pragmaticplay_idr6_game_logs")){
                $this->dbforge->drop_column('pragmaticplay_idr6_game_logs', 'after_balance');
            }
        }

        // pragmaticplay_idr7_game_logs
        if($this->utils->table_really_exists("pragmaticplay_idr7_game_logs")){
            if($this->db->field_exists('after_balance', "pragmaticplay_idr7_game_logs")){
                $this->dbforge->drop_column('pragmaticplay_idr7_game_logs', 'after_balance');
            }
        }

        // pragmaticplay_vnd3_game_logs
        if($this->utils->table_really_exists("pragmaticplay_vnd3_game_logs")){
            if($this->db->field_exists('after_balance', "pragmaticplay_vnd3_game_logs")){
                $this->dbforge->drop_column('pragmaticplay_vnd3_game_logs', 'after_balance');
            }
        }
	}
}