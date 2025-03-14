<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_bac_bo_in_game_tags_table_20221027 extends CI_Migration {

    private $tableName='game_tags';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('game_tags');
            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"Bac bo","2":"Bac bo","3":"Bac bo","4":"Bac bo","5":"Bac bo","6":"Bac bo","7":"Bac bo","8":"Bac bo"}',
                    "tag_code" => "bac_bo",
                ]
            ];
            $this->game_tags->insertUpdateGameTag($gameTag);
        }
    }

    public function down() {
        $gameTag = array("bac_bo");
        
        foreach ($gameTag as $key) {
            $this->db->where('tag_code', $key);
            $this->db->delete($this->tableName);
        }
    }
}