<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_unique_index_to_promorulesgamebetrule_20210315 extends CI_Migration {

    public function up() {
        $this->load->model(['player_model']);
        $this->player_model->addUniqueIndex('promorulesgamebetrule','idx_promoruleId_game_description_id' , 'promoruleId, game_description_id');
    }

    public function down() {
    }
}

///END OF FILE//////////