<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_shooting_games_in_game_tags_table_20221012 extends CI_Migration {

    private $tableName='game_tags';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('game_tags');
            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"Shooting Games","2":"Shooting Games","3":"Shooting Games","4":"Shooting Games","5":"Shooting Games","6":"Shooting Games","7":"Shooting Games","8":"Shooting Games"}',
                    "tag_code" => "shooting_games",
                ]
            ];
            $this->game_tags->insertUpdateGameTag($gameTag);
        }
    }

    public function down() {
        $gameTag = array("shooting_games");
        
        foreach ($gameTag as $key) {
            $this->db->where('tag_code', $key);
            $this->db->delete($this->tableName);
        }
    }
}