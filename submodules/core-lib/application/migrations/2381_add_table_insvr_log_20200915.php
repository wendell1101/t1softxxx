<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_insvr_log_20200915 extends CI_Migration {

    private $tableName = 'insvr_log';
    private $tableName4game_description = 'insvr_game_description_log';

    public function up() {

        if(! $this->utils->table_really_exists($this->tableName)){
            $fields = array(
                'id' => array(
                    'type' => 'INT',
                    'null' => false,
                    'auto_increment' => TRUE,
                ),

                // 'game_description_id' => array(
                //     'type' => 'INT',
                //     'constraint' => '10',
                //     'null' => true,
                // ),
                // 'game_code' => array(
                //     'type' => 'VARCHAR',
                //     'constraint' => '200',
                //     'null' => true,
                // ),

                'player_id' => array(
                    'type' => 'INT',
                    'constraint' => '10',
                    'null' => true,
                ),
                'playerpromo_id' => array(
                    'type' => 'INT',
                    'constraint' => '10',
                    'null' => true,
                ),

                'uri' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '200',
                    'null' => true,
                ),
                "request" => array(
                    "type" => "JSON",
                    "null" => true
                ),
                "response" => array(
                    "type" => "JSON",
                    "null" => true
                ),

                "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
                    "null" => false
                ),
                "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                    "null" => false
                ),
            );

            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);

            $this->dbforge->create_table($this->tableName);
            # add index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, 'idx_playerpromo_id', 'playerpromo_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            // $this->player_model->addIndex($this->tableName, 'idx_game_description_id', 'game_description_id');
        }


        if(! $this->utils->table_really_exists($this->tableName4game_description)){
            $fields = array(
                'id' => array(
                    'type' => 'INT',
                    'null' => false,
                    'auto_increment' => TRUE,
                ),

                'insvr_log_id' => array(
                    'type' => 'INT',
                    'constraint' => '10',
                    'null' => true,
                ),

                'game_description_id' => array(
                    'type' => 'INT',
                    'constraint' => '10',
                    'null' => true,
                ),
                'game_code' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '200',
                    'null' => true,
                ),
            );

            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);

            $this->dbforge->create_table($this->tableName4game_description);
            # add index
            $this->load->model("player_model");
            // $this->player_model->addIndex($this->tableName4game_description, 'idx_playerpromo_id', 'playerpromo_id');
            $this->player_model->addIndex($this->tableName4game_description, 'idx_insvr_log_id', 'insvr_log_id');
            $this->player_model->addIndex($this->tableName4game_description, 'idx_game_description_id', 'game_description_id');
        }


    }

    public function down() {
        if( $this->db->table_exists($this->tableName) ){
            $this->dbforge->drop_table($this->tableName);
        }

        if( $this->db->table_exists($this->tableName4game_description) ){
            $this->dbforge->drop_table($this->tableName4game_description);
        }
    }
}
