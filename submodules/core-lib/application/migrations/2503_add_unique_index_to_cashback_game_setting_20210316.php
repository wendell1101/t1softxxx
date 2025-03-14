<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_unique_index_to_cashback_game_setting_20210316 extends CI_Migration {

    public function up() {
        $this->load->model(['group_level']);

        $cnt=$this->group_level->clear_duplicate_cashback();
        $this->utils->debug_log('clear_duplicate_cashback', $cnt);

        $this->group_level->addUniqueIndex('group_level_cashback_game_platform', 'idx_game_platform_id_level_id', 'vipsetting_cashbackrule_id,game_platform_id');
        $this->group_level->addUniqueIndex('group_level_cashback_game_type', 'idx_game_type_id_level_id', 'vipsetting_cashbackrule_id,game_type_id');
        $this->group_level->addUniqueIndex('group_level_cashback_game_description', 'idx_game_description_id_level_id', 'vipsetting_cashbackrule_id,game_description_id');
    }

    public function down() {
    }
}

///END OF FILE//////////