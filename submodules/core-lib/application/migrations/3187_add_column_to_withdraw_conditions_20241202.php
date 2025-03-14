<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Migration_add_column_to_withdraw_conditions_20241202 extends CI_Migration {
    private $tableName = 'withdraw_conditions';
    public function up() {

        $fields = array(
            'player_roulette_id' => array(
                "type" => "INT",
                "null" => true,
                "unsigned" => true,
            ),
        );

        $this->load->model('player_model');
        if(!$this->db->field_exists('player_roulette_id', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
            $this->player_model->addIndex($this->tableName,'idx_player_roulette_id','player_roulette_id');
        }
    }
    public function down() {
        if($this->db->field_exists('player_roulette_id', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'player_roulette_id');
        }
    }
}