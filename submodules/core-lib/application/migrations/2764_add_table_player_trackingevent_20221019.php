<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_player_trackingevent_20221019 extends CI_Migration {

    private $tableName = 'player_trackingevent';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'null' => false,
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'player_id' => array(
                'type' => 'INT',
                'unsigned' => TRUE
            ),
            'source_type' => array(
                'type' => 'INT',
                'unsigned' => TRUE
            ),
            'params' => array(
                'type' => 'json',
                'null' => true,
            ),
            'is_notify' => array(
                'type' => 'TINYINT',
                'constraint' => 4,
                'unsigned' => TRUE,
                'default' => 0,
            ),
            'notify_time' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => true,
            ),
            'updated_at' => array(
                'type' => 'DATETIME',
                'null' => true
            )
        );
        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model');              
            $this->player_model->addIndex($this->tableName,'idx_source_type','source_type');
            $this->player_model->addIndex($this->tableName,'idx_player_id','player_id');
            $this->player_model->addIndex($this->tableName,'idx_notify_time','notify_time');
            $this->player_model->addIndex($this->tableName,'idx_created_at','created_at');
            $this->player_model->addIndex($this->tableName,'idx_updated_at','updated_at');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}