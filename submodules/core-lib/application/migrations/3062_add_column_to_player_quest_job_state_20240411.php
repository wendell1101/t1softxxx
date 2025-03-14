<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_player_quest_job_state_20240411 extends CI_Migration {

    private $tableName = 'player_quest_job_state';

    public function up() {
        $fields = array(
            'releaseTime' => array(
                "type" => "DATETIME",
                "null" => true,
            ),
        );

        $this->load->model('player_model');
        if(!$this->db->field_exists('releaseTime', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
            $this->player_model->addIndex($this->tableName,'idx_releaseTime','releaseTime');
        }
    }

    public function down() {
        if($this->db->field_exists('releaseTime', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'releaseTime');
        }
    }
}