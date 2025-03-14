<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_game_type_to_rgs_game_logs_20200720 extends CI_Migration
{
    private $tableName = 'rgs_game_logs';

    public function up() {

        $fields = array(
            'game_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('game_type', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('game_type', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'game_type');
            }
        }
    }
}