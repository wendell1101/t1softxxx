<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_registration_fields_20231030 extends CI_Migration {

    private $tableName = 'registration_fields';

    public function up() {
        $fieldType = [
            'fieldType' => array(
                'type' => 'INT',
                'null' => false,
                'default' => 1,
            ),
        ];

        $options = [
            'options' => array(
                'type' => 'JSON',
                'null' => true,
            ),
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if (!$this->db->field_exists('fieldType', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $fieldType);
                $this->load->model('player_model');
            }

            if (!$this->db->field_exists('options', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $options);
                $this->load->model('player_model');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('fieldType', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'fieldType');
            }
        }
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('options', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'options');
            }
        }
    }
}