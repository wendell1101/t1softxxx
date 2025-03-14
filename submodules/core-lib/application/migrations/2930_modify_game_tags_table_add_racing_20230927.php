<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_game_tags_table_add_racing_20230927 extends CI_Migration {

    private $tableName='game_tags';

    public function up() {
        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('game_tags');
            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"Racing","2":"Racing","3":"Racing","4":"Racing","5":"Racing","6":"Racing","7":"Racing","8":"Racing"}',
                    "tag_code" => "racing",
                ]
            ];
            $this->game_tags->insertUpdateGameTag($gameTag);

            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"Original","2":"Original","3":"Original","4":"Original","5":"Original","6":"Original","7":"Original","8":"Original"}',
                    "tag_code" => "original",
                    "is_custom" => 1
                ]
            ];
            $this->game_tags->insertUpdateGameTag($gameTag);

            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"Epic","2":"Epic","3":"Epic","4":"Epic","5":"Epic","6":"Epic","7":"Epic","8":"Epic"}',
                    "tag_code" => "epic",
                    "is_custom" => 1
                ]
            ];
            $this->game_tags->insertUpdateGameTag($gameTag);

            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"Popular","2":"Popular","3":"Popular","4":"Popular","5":"Popular","6":"Popular","7":"Popular","8":"Popular"}',
                    "tag_code" => "popular",
                    "is_custom" => 1
                ]
            ];
            $this->game_tags->insertUpdateGameTag($gameTag);

            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"Hot","2":"Hot","3":"Hot","4":"Hot","5":"Hot","6":"Hot","7":"Hot","8":"Hot"}',
                    "tag_code" => "hot",
                    "is_custom" => 1
                ]
            ];
            $this->game_tags->insertUpdateGameTag($gameTag);

            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"New","2":"New","3":"New","4":"New","5":"New","6":"New","7":"New","8":"New"}',
                    "tag_code" => "new",
                    "is_custom" => 1
                ]
            ];
            $this->game_tags->insertUpdateGameTag($gameTag);

            $gameTag = [
                [
                    "tag_name" => '_json:{"1":"Top","2":"Top","3":"Top","4":"Top","5":"Top","6":"Top","7":"Top","8":"Top"}',
                    "tag_code" => "top",
                    "is_custom" => 1
                ]
            ];
            $this->game_tags->insertUpdateGameTag($gameTag);



        }
    }

    public function down() {
        $gameTag = array('racing','original', 'epic', 'popular', 'hot', 'new', 'top');
        
        foreach ($gameTag as $key) {
            $this->db->where('tag_code', $key);
            $this->db->delete($this->tableName);
        }
    }
}