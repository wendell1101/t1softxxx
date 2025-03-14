<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_player_friend_referral_level_20231003 extends CI_Migration
{
    private $tableName = 'player_friend_referral_level';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'referral_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'interval_level' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'last_invited_player' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'last_invited_total_bet' => array(
                'type' => 'DOUBLE',
                'null' => false,
            ),
            'last_referral_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'player_promo_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'release_date' => array(
                'type' => 'DATE',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'notes' => array(
                'type' => 'TEXT',
                'null' => true,
            )
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_referral_id', 'referral_id');
            $this->player_model->addIndex($this->tableName, 'idx_last_referral_id', 'last_referral_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_promo_id', 'player_promo_id');
            $this->player_model->addIndex($this->tableName, 'idx_release_date', 'release_date');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_last_invited_player', 'last_invited_player');
        }
    }

    public function down()
    {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}