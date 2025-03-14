<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_copy_table_total_player_game_month_to_game_billing_report_20241108 extends CI_Migration {

    private $tableName='game_billing_report';

    public function up() {
        $this->load->model(['player_model']);
        $fields = [
            'id' => array(
                'type' => 'INT',
                'auto_increment' => TRUE,
            ),
            'player_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'betting_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'real_betting_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'bet_for_cashback' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'result_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'win_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'loss_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'game_fee' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'month' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'start_of_the_month' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'game_platform_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'game_type_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'game_description_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'timezone' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ),
            'unique_id' => array(
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ),
            'currency_key' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ),
            'md5_sum' => array(
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            )
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_month', 'month');
            $this->player_model->addIndex($this->tableName, 'idx_game_description_id', 'game_description_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_type_id', 'game_type_id');
            $this->player_model->addIndex($this->tableName, 'idx_timezone', 'timezone');
            # add index unique
            $this->player_model->addUniqueIndex($this->tableName, 'idx_unique_id', 'unique_id');
        }
    }

    public function down() {
    	if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}