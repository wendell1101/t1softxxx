<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_player_dw_achieve_threshold_20200914 extends CI_Migration {

    private $tableName = 'player_dw_achieve_threshold';

    public function up() {
        $fields=array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'player_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'create_at' => array(
                'type' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                'null' => false,
            ),
            'created_by' => array(
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
            ),
            'update_at' => array(
                'type' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                'null' => false,
            ),
            'update_by' => array(
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
            ),
            'before_deposit_achieve_threshold' => array(
                'type' => 'DOUBLE',
                'default' => 0,
                'null' => false,
            ),
            'before_withdrawal_achieve_threshold' => array(
                'type' => 'DOUBLE',
                'default' => 0,
                'null' => false,
            ),
            'after_deposit_achieve_threshold' => array(
                'type' => 'DOUBLE',
                'default' => 0,
                'null' => false,
            ),
            'after_withdrawal_achieve_threshold' => array(
                'type' => 'DOUBLE',
                'default' => 0,
                'null' => false,
            )
        );

        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_player_id' , 'player_id');
            $this->player_model->addIndex($this->tableName,'idx_create_at' , 'create_at');
        }
    }

    public function down() {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}