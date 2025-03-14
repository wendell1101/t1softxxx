<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_roulette_api_record_20220713 extends CI_Migration {

    private $tableName = 'roulette_api_record';

    public function up() {
        $field = array(
            'valid_date' => array(
                'type' => 'DATE',
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');

            if(!$this->db->field_exists('valid_date', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
                $this->player_model->addIndex($this->tableName, 'idx_valid_date', 'valid_date');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('valid_date', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'valid_date');
            }
        }
    }
}
