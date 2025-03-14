<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_on_fast_track_bonus_crediting_20201203 extends CI_Migration {

    public function up() {
        $this->db->query('ALTER TABLE `fast_track_bonus_crediting` CHANGE `expire_date` `expirationDate` DATETIME NULL');
    }

    public function down() {}
}