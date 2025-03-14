<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_ole_sso_player_20231128 extends CI_Migration
{
    private $tableName = 'ole_sso_player';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'player_id' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
            ),
            'username' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => false,
            ),
            'access_token' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => false,
            ),
            'expiration_date' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
            'status' => array(
                'type' => 'INT',
                'constraint' => '12',
                'default' => 1, 
                'null' => false,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            )
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_player_id','player_id');
            $this->player_model->addIndex($this->tableName,'idx_username','username');
            $this->player_model->addIndex($this->tableName,'idx_access_token','access_token');
            $this->player_model->addIndex($this->tableName,'idx_expiration_date','expiration_date');
            $this->player_model->addIndex($this->tableName,'idx_status','status');
            $this->player_model->addIndex($this->tableName,'idx_created_at','created_at');

        }
    }

    public function down()
    {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}