<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_pragmaticplay_idr7_game_logs_20210507 extends CI_Migration {

    private $tableName = 'pragmaticplay_idr7_game_logs';

    public function up() {
        $fields = array(
            'after_balance' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('after_balance', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('after_balance', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'after_balance');
            }
        }
    }
}