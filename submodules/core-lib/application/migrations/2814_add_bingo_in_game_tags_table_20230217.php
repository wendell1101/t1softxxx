<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_bingo_in_game_tags_table_20230217 extends CI_Migration {

    private $tableName='game_tags';

    public function up() {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->load->model('game_tags');

            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"Bingo", "2":"Bingo", "3":"Bingo", "4":"Bingo", "5":"Bingo", "6":"Bingo", "7":"Bingo", "8":"Bingo"}',
                    "tag_code" => "bingo",
                ]
            ];

            $this->game_tags->insertUpdateGameTag($gameTag);
        }
    }

    public function down() {
        $gameTag = array("bingo");

        foreach ($gameTag as $key) {
            $this->db->where('tag_code', $key);
            $this->db->delete($this->tableName);
        }
    }
}