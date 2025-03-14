<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_player_friend_referral_custom_details_20240222 extends CI_Migration
{
    private $tableName = 'player_friend_referral_custom_details';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'playerId' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'referred_depositors_count' => array(
                'type' => 'INT',
                'null' => true,
                'default' => 0,
            ),
            'referred_actual_depositors_count' => array(
                'type' => 'INT',
                'null' => true,
                'default' => 0,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            )
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_playerId', 'playerId');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
        }
    }

    public function down()
    {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}