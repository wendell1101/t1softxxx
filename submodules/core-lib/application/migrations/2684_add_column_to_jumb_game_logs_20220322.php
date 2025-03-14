<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_jumb_game_logs_20220322 extends CI_Migration {
    private $tableName = 'jumb_game_logs';

    public function up() {
        $field1 = array(
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            )
        );
        
        $field2 = array(
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            )
        );

        if($this->utils->table_really_exists($this->tableName)) {
            $this->load->model('player_model');

            if(!$this->db->field_exists('created_at', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $field1);
                $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            }
            
            if(!$this->db->field_exists('updated_at', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $field2);
                $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            }

            if($this->db->field_exists('gameDate', $this->tableName)) {
                $this->player_model->addIndex($this->tableName, 'idx_gameDate', 'gameDate');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)) {
            if($this->db->field_exists('created_at', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'created_at');
            }

            if($this->db->field_exists('updated_at', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'updated_at');
            }
        }
    }
}
