<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_after_balance_on_kingpoker_gamelogs_20210127 extends CI_Migration {

    private $tableName = 'kingpoker_gamelogs';

    public function up() {

        $column = array(
            'after_balance' => array(
                'type' => 'DOUBLE',
                'null' => TRUE,
            )
        );

        
        if(!$this->db->field_exists('after_balance', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $column);
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_after_balance","after_balance");
        }
    }

    public function down() {
        
        if($this->db->field_exists('after_balance', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'after_balance');
        }
    }
}