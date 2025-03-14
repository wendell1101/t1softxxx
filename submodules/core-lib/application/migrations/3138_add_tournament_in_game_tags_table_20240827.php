<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_tournament_in_game_tags_table_20240827 extends CI_Migration {
    private $tableName = 'game_tags';

    public function up() {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->load->model('game_tags');

            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"Tournament","2":"Tournament","3":"Tournament","4":"Tournament","5":"Tournament","6":"Tournament","7":"Tournament","8":"Tournament"}',
                    "tag_code" => "tournament",
                ]
            ];

            $this->game_tags->insertUpdateGameTag($gameTag);
        }
    }

    public function down() {
        $gameTag = array("tournament");
        
        foreach ($gameTag as $key) {
            $this->db->where('tag_code', $key);
            $this->db->delete($this->tableName);
        }
    }
}