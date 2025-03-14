<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_add_column_to_promorules_20241230 extends CI_Migration {
    private $tableName = 'promorules';
    public function up() {

        $fields = array(
            'rouletteInfo' => array(
                "type" => "JSON",
                "null" => true,
            ),
        );

        $this->load->model('player_model');
        if(!$this->db->field_exists('rouletteInfo', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }
    public function down() {
        if($this->db->field_exists('rouletteInfo', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'rouletteInfo');
        }
    }
}