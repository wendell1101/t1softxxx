<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_player_login_report_20210528 extends CI_Migration {

    private $tableName = 'player_login_report';

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
            'ip' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => false,
            ),
            'cookie' => array(
                'type' => 'TEXT',
                'null' => true,
                'comment' => "value only",
            ),
            'referrer' => array(
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => true,
            ),
            'user_agent' => array(
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => true,
            ),
            'device' => array(
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => true,
                'comment' => "get from user_agent",
            ),
            'os' => array(
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => true,
            ),
            'is_mobile' => array(
                'type' => 'int',
                'null' => true,
                'comment' => "get from user_agent: 1- true, 0 - false",
            ),
            'create_at' => array(
                'type' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                'null' => false,
            ),
            'login_result' => array(
                'type' => 'TINYINT',
                'null' => false,
            ),
            'player_status' => array(
                'type' => 'TINYINT',
                'null' => false,
            ),
            'login_from' => array(
                'type' => 'TINYINT',
                'null' => false,
            ),
            'browser_type' => array(
                'type' => 'TINYINT',
                'null' => false,
            ),
            'content' => array(
                'type' => 'TEXT',
                'null' => true,
            )
        );

        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex('player_login_report','idx_player_id' , 'player_id');
            $this->player_model->addIndex('player_login_report','idx_ip' , 'ip');
            $this->player_model->addIndex('player_login_report','idx_create_at' , 'create_at');
            $this->player_model->addIndex('player_login_report','idx_player_status' , 'player_status');
            $this->player_model->addIndex('player_login_report','idx_login_from' , 'login_from');
        }
    }

    public function down() {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
