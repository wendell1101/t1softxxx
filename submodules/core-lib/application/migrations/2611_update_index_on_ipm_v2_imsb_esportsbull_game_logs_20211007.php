<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_update_index_on_ipm_v2_imsb_esportsbull_game_logs_20211007 extends CI_Migration
{

    private $tableName = "ipm_v2_imsb_esportsbull_game_logs";

    public function up()
    {
        # Add Index
        $this->load->model('player_model');

        # remove unique index in BetId
        if($this->player_model->existsIndex($this->tableName, 'idx_BetId')) 
        {
            $this->player_model->dropIndex($this->tableName, 'idx_BetId');
        }

        # remove unique index in WagerCreationDateTime
        if($this->player_model->existsIndex($this->tableName, 'idx_WagerCreationDateTime')) 
        {
            $this->player_model->dropIndex($this->tableName, 'idx_WagerCreationDateTime');
        }
        
        # add index in BetId
        if(!$this->player_model->existsIndex($this->tableName, 'idx_BetId')) 
        {
            $this->player_model->addIndex($this->tableName, 'idx_BetId', 'BetId');
        }

        # add index in WagerCreationDateTime
        if(!$this->player_model->existsIndex($this->tableName, 'idx_WagerCreationDateTime')) 
        {
            $this->player_model->addIndex($this->tableName, 'idx_WagerCreationDateTime', 'WagerCreationDateTime');
        }
    }

    public function down(){
    }
}