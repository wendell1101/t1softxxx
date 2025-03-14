<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_of_yeebet_game_logs_20220617 extends CI_Migration {

    private $tableName = 'yeebet_game_logs';

    public function up() {

        $gameno = array(
            "gameno" => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('gameno', $this->tableName)){
                $this->dbforge->modify_column($this->tableName, $gameno);
            }
        }
    }

    public function down() {

    }
}