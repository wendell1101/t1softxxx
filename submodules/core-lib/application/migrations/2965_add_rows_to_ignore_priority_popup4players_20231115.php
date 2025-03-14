<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_rows_to_ignore_priority_popup4players_20231115 extends CI_Migration {

    public function up() {
        $this->db->trans_start();
        $this->db->query("INSERT INTO player_in_priority (player_id, is_priority, is_join_show_done)
        SELECT playerId, 0, 1
        FROM player
        WHERE playerId NOT IN (
            SELECT player_id
            FROM player_in_priority
        )");
        $this->db->trans_complete();
   }

    public function down() {
    }

}