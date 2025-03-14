<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_dingdong_in_game_tags_table_20220520 extends CI_Migration {

    private $tableName='game_tags';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('game_tags');
            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"Dingdong","2":"Dingdong","3":"Dingdong","4":"Dingdong","5":"Dingdong"}',
                    "tag_code" => "dingdong",
                ]
            ];
            $this->game_tags->insertUpdateGameTag($gameTag);
        }
    }

    public function down() {
        $gameTag = array("dingdong");
        
        foreach ($gameTag as $key) {
            $this->db->where('tag_code', $key);
            $this->db->delete($this->tableName);
        }
    }
}