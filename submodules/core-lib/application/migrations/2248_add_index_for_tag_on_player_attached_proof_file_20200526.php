<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_for_tag_on_player_attached_proof_file_20200526 extends CI_Migration {
    private $tableName = 'player_attached_proof_file';

    public function up() {
        $this->load->model(['player_model']);

        $this->player_model->addIndex($this->tableName, 'idx_tag', 'tag');
    }

    public function down() {}
}
