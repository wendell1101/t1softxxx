<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_total_cashback_player_game_daily_20210831 extends CI_Migration {

    private $tableName = 'total_cashback_player_game_daily';

    public function up() {

        $field = array(
            'cashback_target' => array(
                'type' => 'INT',
                'null' => false,
                'default' => 1
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('cashback_target', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
                // add index
				$this->load->model([ 'player_model' ]);
				$this->player_model->addIndex($this->tableName, 'idx_cashback_target', 'cashback_target');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('cashback_target', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'cashback_target');
            }
        }
    }
}