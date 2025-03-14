<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_player_tracking_report_20230515 extends CI_Migration
{

    private $tableName = 'player_tracking_report';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'event_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true
            ),
            'player_id' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true
            ),
            'platform_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'external_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true
            ),
            'extra_info' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'response_result' => array(
                'type' => 'JSON',
                'null' => false,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_event_name', 'event_name');
            $this->player_model->addIndex($this->tableName, 'idx_platform_id', 'platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_external_id', 'external_id');
        }
    }

    public function down()
    {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}