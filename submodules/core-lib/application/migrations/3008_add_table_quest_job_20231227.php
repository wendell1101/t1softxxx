<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_quest_job_20231227 extends CI_Migration
{
    private $tableName = 'quest_job';

    public function up()
    {
        $fields = array(
            'questJobId' => array(
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'questManagerId' => array(
                'type' => 'INT', 
                'null' => false,
            ),
            'questRuleId' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'title' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'createdAt DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            )
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('questJobId', true);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_questJobId','questJobId');
            $this->player_model->addIndex($this->tableName,'idx_questManagerId','questManagerId');
            $this->player_model->addIndex($this->tableName,'idx_questRuleId','questRuleId');
            $this->player_model->addIndex($this->tableName,'idx_createdAt','createdAt');
            $this->player_model->addIndex($this->tableName,'idx_updatedAt','updatedAt');
        }
    }

    public function down()
    {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}