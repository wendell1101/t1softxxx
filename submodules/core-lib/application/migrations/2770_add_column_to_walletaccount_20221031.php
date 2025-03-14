<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_walletaccount_20221031 extends CI_Migration {

    private $tableName = 'walletaccount';

    public function up() {
        $fields = array(
            'spent_time' => array(
                "type" => "INT",
                "null" => true,
            ),
        );

        $this->load->model('player_model');
        if(!$this->db->field_exists('spent_time', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
            $this->player_model->addIndex($this->tableName,'idx_spent_time','spent_time');
        }
    }

    public function down() {
        if($this->db->field_exists('spent_time', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'spent_time');
        }
    }
}