<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_quest_manager_20231227 extends CI_Migration
{
    private $tableName = 'quest_manager';

    public function up()
    {
        $fields = array(
            'questManagerId' => array(
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'levelType' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
                'default' => '1'
            ),
            'questManagerType' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
                'default' => '1'
            ),
            'questCategoryId' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
            ),
            'questRuleId' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
            ),
            'showOneClick' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0
            ),
            'showTimer' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0
            ),
            'startAt' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
            'endAt' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
            'period' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
            'title' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ),
            'description' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'iconPath' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ),
            'bannerPath' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ),
            'allowSameIPBonusReceipt' => array(
                'type' => 'TINYINT',
                'null' => false,
                'constrain' => 1,
                'default' => 0
            ),
            'claimOtherUrl' => array(
                'type' => 'varchar',
                'constraint' => '100',
                'null' => true,
            ),
            'displayPanel' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
                'default' => 1
            ),
            'createdBy' => array(
                'type' => 'INT',
                'null' => false,
                'default' => 0
            ),
            'updatedBy' => array(
                'type' => 'INT',
                'null' => false,
                'default' => 0
            ),
            'status' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0
            ),
            'deleted' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0
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
            $this->dbforge->add_key('questManagerId', true);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_levelType','levelType');
            $this->player_model->addIndex($this->tableName,'idx_questCategoryId','questCategoryId');
            $this->player_model->addIndex($this->tableName,'idx_startAt','startAt');
            $this->player_model->addIndex($this->tableName,'idx_endAt','endAt');
            $this->player_model->addIndex($this->tableName,'idx_period','period');
            $this->player_model->addIndex($this->tableName,'idx_createdBy','createdBy');
            $this->player_model->addIndex($this->tableName,'idx_updatedBy','updatedBy');
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