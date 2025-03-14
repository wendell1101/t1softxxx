<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_blocked_player_on_acl_rule_20211031 extends CI_Migration
{
    private $tableName = 'blocked_player_on_acl_rule';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ),
            'player_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'source' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'use_real_ip' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'ip' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'real_ip' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
        );

        if(!$this->utils->table_really_exists($this->tableName))
        {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_ip', 'ip');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
        }
    }

    public function down()
    {
    }
}
