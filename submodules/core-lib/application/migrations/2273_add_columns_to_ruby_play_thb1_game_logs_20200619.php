<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_ruby_play_thb1_game_logs_20200619 extends CI_Migration {

    private $tableName = 'ruby_play_thb1_game_logs';

    public function up() {

        $fields = array(
            'result_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            )
        );

        if(!$this->db->field_exists($fields, $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('result_amount', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'result_amount');
        }
    }
}