<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_for_dg_game_logs_20201105 extends CI_Migration {

    private $tableName = 'dg_game_logs';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $md5_column = array(
                'md5_sum' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '32',
                    'null' => true
                )
            );

            $created_at_column = array(
                'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                    "null" => true
                )
            );

            $updated_at_column = array(
                'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                    "null" => true
                )
            );


            if(!$this->db->field_exists('md5_sum', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $md5_column);
            }
            
            if(!$this->db->field_exists('created_at', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $created_at_column);
            }

            if(!$this->db->field_exists('updated_at', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $updated_at_column);
            }
        }
    }

    public function down() {
    }
}