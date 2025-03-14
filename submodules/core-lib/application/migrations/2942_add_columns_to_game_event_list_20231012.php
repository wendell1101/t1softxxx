<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_game_event_list_20231012 extends CI_Migration {

    private $tableName = 'game_event_list';

    public function up() {
        $column = array(
            'start_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'end_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('start_at', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column);
            }

            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_start_at','start_at');
            $this->player_model->addIndex($this->tableName,'idx_end_at','end_at');
            
        }
    }

    public function down() {
        
    }
}