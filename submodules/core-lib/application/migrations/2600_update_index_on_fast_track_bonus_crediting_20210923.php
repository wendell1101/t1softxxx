<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_index_on_fast_track_bonus_crediting_20210923 extends CI_Migration {

    private $tableName = 'fast_track_bonus_crediting';

    public function up() {
        if($this->db->table_exists($this->tableName)){
            # Add Index
            $this->load->model('player_model');

            # remove unique index in expire_date
            if ($this->player_model->existsIndex($this->tableName, 'idx_expire_date')) {
                $this->player_model->dropIndex($this->tableName, 'idx_expire_date');
            }

            # add unique index expirationDate
            if (!$this->player_model->existsIndex($this->tableName, 'idx_expirationDate')) {
                $this->player_model->addIndex($this->tableName, 'idx_expirationDate', 'expirationDate');
            }
        }
    }

    public function down() {
    }
}