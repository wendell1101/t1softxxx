<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_update_index_on_hkb_game_logs_20211008 extends CI_Migration
{

    private $tableName = "hkb_game_logs";

    public function up()
    {
        # Add Index
        $this->load->model('player_model');

        # remove unique index in version_key
        if($this->player_model->existsIndex($this->tableName, 'idx_version_key')) 
        {
            $this->player_model->dropIndex($this->tableName, 'idx_version_key');
        }

        $this->player_model->addIndex($this->tableName, 'idx_version_key', 'version_key');
        $this->player_model->addIndex($this->tableName, 'idx_username', 'username');
        $this->player_model->addIndex($this->tableName, 'idx_game_id', 'game_id');
        $this->player_model->addIndex($this->tableName, 'idx_trans_time', 'trans_time');
        $this->player_model->addIndex($this->tableName, 'idx_winloss_time', 'winloss_time');
    }

    public function down(){
    }
}