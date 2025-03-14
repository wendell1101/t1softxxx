<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_player_additional_roulette_20220824 extends CI_Migration {

	private $tableName = 'player_additional_roulette';

    public function up()
    {
        $fields = array(
            "id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
            ),
            'player_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'source_promo_rule_id' => array(
                'type' => 'BIGINT',
                'null' => true,
            ),
            'source_player_promo_id' => array(
                'type' => 'BIGINT',
                'null' => true,
            ),
            'player_promo_id' => array(
                'type' => 'BIGINT',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'roulette_type' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'status' => array(
                'type' => 'TINYINT',
                'null' => false,
                'default' => 1
            ),
            'generate_by' => array(
                'type' => 'varchar',
                'constraint' => '200',
            ),
            'note' => array(
                'type' => 'varchar',
                'constraint' => '2000',
            ),
            'expired_at DATE DEFAULT NULL' => array(
                // 'type' => 'DATE',
                'null' => true,
            ),
            'update_on DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'apply_at DATETIME DEFAULT NULL' => array(
                // 'type' => 'DATETIME',
                'null' => true,
            ),
        );

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id",true);
            $this->dbforge->create_table($this->tableName);

            // # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_source_promo_rule_id', 'source_promo_rule_id');
            $this->player_model->addIndex($this->tableName, 'idx_source_player_promo_id', 'source_player_promo_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_promo_id', 'player_promo_id');
            $this->player_model->addIndex($this->tableName, 'idx_status', 'status');
            $this->player_model->addIndex($this->tableName, 'idx_roulette_type', 'roulette_type');
            $this->player_model->addIndex($this->tableName, 'idx_expired_at', 'expired_at');
            $this->player_model->addIndex($this->tableName, 'idx_apply_at', 'apply_at');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
