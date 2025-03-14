<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_quest_category_20231227 extends CI_Migration
{
    private $tableName = 'quest_category';

    public function up()
    {
        $fields = array(
            'questCategoryId' => array(
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'parentId' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
            'title' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'description' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'sort' => array(
                'type' => 'INT',
                'constraint' => '12',
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
            $this->dbforge->add_key('questCategoryId', true);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_parentId','parentId');
            $this->player_model->addIndex($this->tableName,'idx_sort','sort');
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