<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_ole_auth_player_20231207 extends CI_Migration
{
    private $tableName = 'ole_auth_player';

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
            'ole_user_id' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
            ),
            'ole_username' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => false,
            ),
            'access_token' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
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
            $this->player_model->addIndex($this->tableName,'idx_ole_user_id','ole_user_id');
            $this->player_model->addIndex($this->tableName,'idx_ole_username','ole_username');
            $this->player_model->addIndex($this->tableName,'idx_access_token','access_token');
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