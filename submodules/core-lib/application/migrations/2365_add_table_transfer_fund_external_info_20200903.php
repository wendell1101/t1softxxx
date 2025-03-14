<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_transfer_fund_external_info_20200903 extends CI_Migration {

    private $tableName = 'transfer_fund_external_info';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'external_trans_id_from_gamegatewayapi' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => false,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
        );
        if(! $this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);

            $this->dbforge->create_table($this->tableName);
            # add index
            $this->load->model("player_model");
            $this->player_model->addUniqueIndex($this->tableName,"idx_external_trans_id_from_gamegatewayapi","external_trans_id_from_gamegatewayapi");
        }
    }

    public function down() {
        // $this->dbforge->drop_table($this->tableName);
    }
}
