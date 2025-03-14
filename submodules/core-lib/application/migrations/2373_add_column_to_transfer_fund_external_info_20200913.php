<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Migration_add_column_to_transfer_fund_external_info_20200913 extends CI_Migration
{
    private $tableName = 'transfer_fund_external_info';

    public function up()
    {
        $fields = array(
            'game_platform_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );
        if(!$this->db->field_exists('game_platform_id', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
        $this->load->model(['player_model']);
        $this->player_model->dropIndex($this->tableName, 'idx_external_trans_id_from_gamegatewayapi');
        $this->player_model->addUniqueIndex($this->tableName, 'idx_unique_external_trans_id_and_game_platform_id', 'game_platform_id, external_trans_id_from_gamegatewayapi');
    }

    public function down()
    {
        $this->dbforge->drop_column($this->tableName, 'game_platform_id');
    }
}
