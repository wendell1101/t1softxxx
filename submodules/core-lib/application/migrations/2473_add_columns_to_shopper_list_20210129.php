<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_shopper_list_20210129 extends CI_Migration {

    private $tableName = 'shopper_list';

    public function up() {

        $column_before_points = array(
            'before_points' => array(
                'type' => 'DOUBLE',
                'null' => TRUE,
            )
        );
        $column_after_points = array(
            'after_points' => array(
                'type' => 'DOUBLE',
                'null' => true,
            )
        );
        $column_trans_id = array(
            'trans_id' => array(
                'type' => 'INT',
                'null' => true,
            )
        );

        if(!$this->db->field_exists('before_points', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $column_before_points);
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_before_points","before_points");
        }
        if (!$this->db->field_exists('after_points', $this->tableName)) {
            $this->dbforge->add_column($this->tableName, $column_after_points);
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, "idx_after_points", "after_points");
        }
        if (!$this->db->field_exists('trans_id', $this->tableName)) {
            $this->dbforge->add_column($this->tableName, $column_trans_id);
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, "idx_trans_id", "trans_id");
        }

    }

    public function down() {
        
        if($this->db->field_exists('before_points', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'before_points');
        }
        if ($this->db->field_exists('after_points', $this->tableName)) {
            $this->dbforge->drop_column($this->tableName, 'after_points');
        }
        if ($this->db->field_exists('trans_id', $this->tableName)) {
            $this->dbforge->drop_column($this->tableName, 'trans_id');
        }

    }
}