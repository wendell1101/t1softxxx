<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_player_dw_achieve_threshold_history_20200914 extends CI_Migration {

    private $tableName = 'player_dw_achieve_threshold_history';

    public function up() {
        $fields=array(
            'player_dw_achieve_threshold_id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'username' => array(
                'type' => 'VARCHAR',
                'constraint'=>'100',
                'null' => true,
            ),
            'player_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'create_at' => array(
                'type' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                'null' => false,
            ),
            'threshold_amount' => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0
            ),
            'achieve_amount' => array(
                'type' => 'DOUBLE',
                'null' => false,
                'default' => 0
            ),
            'achieve_threshold_type' => array(
                'type' => 'TINYINT',
                'null' => false,
            )
        );

        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('player_dw_achieve_threshold_id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_player_id' , 'player_id');
            $this->player_model->addIndex($this->tableName,'idx_create_at' , 'create_at');
            $this->player_model->addIndex($this->tableName,'idx_username' , 'username');
            $this->player_model->addIndex($this->tableName,'idx_achieve_threshold_type' , 'achieve_threshold_type');
        }
    }

    public function down() {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}