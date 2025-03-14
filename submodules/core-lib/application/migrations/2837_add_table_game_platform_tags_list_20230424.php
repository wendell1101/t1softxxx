<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_game_platform_tags_list_20230424 extends CI_Migration {


    private $tableName = 'game_platform_tag_list';

    public function up() {
        $fields = [
            // default
            'id' => [
                'type' => 'INT',
                'null' => false,
                'auto_increment' => true
            ],            
            'tag_id' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true
            ],             
            'game_platform_id' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true
            ],       
            'game_order' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true
            ],

            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false
            ]
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');            
            $this->player_model->addIndex($this->tableName, 'idx_tag_id', 'tag_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }


}