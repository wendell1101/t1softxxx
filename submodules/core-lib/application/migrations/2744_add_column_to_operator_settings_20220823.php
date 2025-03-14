<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_operator_settings_20220823 extends CI_Migration {

    private $tableName = 'operator_settings';

    public function up() {
        $fields = array(
            'updated_at DATETIME DEFAULT NULL' => array(
                'null' => TRUE,
            ),
        );

        $fields2 = array(
            'updated_by INT DEFAULT NULL' => array(
                'null' => TRUE,
            ),
        );

        $this->load->model('player_model');
        if(!$this->db->field_exists('updated_at', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
            $this->player_model->addIndex($this->tableName,'idx_updated_at','updated_at');
        }

        if(!$this->db->field_exists('updated_by', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields2);
            $this->player_model->addIndex($this->tableName,'idx_updated_by','updated_by');
        }
    }

    public function down() {
        if($this->db->field_exists('updated_at', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'updated_at');
        }

        if($this->db->field_exists('updated_by', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'updated_by');
        }
    }
}