<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_roulette_20241202 extends CI_Migration {

    private $tableName = 'roulette';

    public function up() {

        $fields = array(
            'uniqueCode' => array(
                "type" => "VARCHAR",
                "constraint" => 32,
                "null" => false,
            ),
        );

        $this->load->model('player_model');
        if(!$this->db->field_exists('uniqueCode', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
            $this->player_model->addUniqueIndex($this->tableName,'idx_uniqueCode','uniqueCode');
        }
    }

    public function down() {
        if($this->db->field_exists('uniqueCode', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'uniqueCode');
        }
    }
}