<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_repeal_time_on_kingpoker_gamelogs_20210129 extends CI_Migration {

    private $tableName = 'kingpoker_gamelogs';

    public function up() {

        $column = array(
            'repeal_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            )
        );

        
        if(!$this->db->field_exists('repeal_time', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $column);
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_repeal_time","repeal_time");
        }
    }

    public function down() {
        
        if($this->db->field_exists('repeal_time', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'repeal_time');
        }
    }
}