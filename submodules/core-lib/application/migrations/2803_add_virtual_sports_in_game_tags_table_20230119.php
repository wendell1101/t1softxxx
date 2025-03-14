<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_virtual_sports_in_game_tags_table_20230119 extends CI_Migration {

    private $tableName='game_tags';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('game_tags');
            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"Virtual Sports","2":"Virtual Sports","3":"Virtual Sports","4":"Virtual Sports","5":"Virtual Sports","6":"Virtual Sports","7":"Virtual Sports","8":"Virtual Sports"}',
                    "tag_code" => "virtual_sports",
                ]
            ];
            $this->game_tags->insertUpdateGameTag($gameTag);
        }
    }

    public function down() {
        $gameTag = array("virtual_sports");
        
        foreach ($gameTag as $key) {
            $this->db->where('tag_code', $key);
            $this->db->delete($this->tableName);
        }
    }
}