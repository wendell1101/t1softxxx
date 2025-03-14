<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_oneworks_game_description_20161103 extends CI_Migration {
	
	const FLAG_TRUE = 1;
	const FLAG_FALSE = 0;

	public function up() {

		$this->db->start_trans();

		$game_descriptions = array(
			array(
				'game_platform_id' => 58, 
				'game_code' => 'unknown', 
				'game_name' => '_json:{"1":"Unknown Oneworks Game","2":"Unknown Oneworks Game"}', 
				'english_name' => 'Unknown Oneworks Game', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '1', 
				'game_name' => '_json:{"1":"Soccer","2":"Soccer"}', 
				'english_name' => 'Soccer', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '2', 
				'game_name' => '_json:{"1":"Basketball","2":"Basketball"}', 
				'english_name' => 'Basketball', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '3', 
				'game_name' => '_json:{"1":"Football","2":"Football"}', 
				'english_name' => 'Football', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '4', 
				'game_name' => '_json:{"1":"Ice Hockey","2":"Ice Hockey"}', 
				'english_name' => 'Ice Hockey', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '5', 
				'game_name' => '_json:{"1":"Tennis","2":"Tennis"}', 
				'english_name' => 'Tennis', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '6', 
				'game_name' => '_json:{"1":"Volleyball","2":"Volleyball"}', 
				'english_name' => 'Volleyball', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '7', 
				'game_name' => '_json:{"1":"Billiards","2":"Billiards"}', 
				'english_name' => 'Billiards', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '8', 
				'game_name' => '_json:{"1":"Baseball","2":"Baseball"}', 
				'english_name' => 'Baseball', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '9', 
				'game_name' => '_json:{"1":"Badminton","2":"Badminton"}', 
				'english_name' => 'Badminton', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '10', 
				'game_name' => '_json:{"1":"Golf","2":"Golf"}', 
				'english_name' => 'Golf', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '11', 
				'game_name' => '_json:{"1":"Motorsports","2":"Motorsports"}', 
				'english_name' => 'Motorsports', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '12', 
				'game_name' => '_json:{"1":"Swimming","2":"Swimming"}', 
				'english_name' => 'Swimming', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '13', 
				'game_name' => '_json:{"1":"Politics","2":"Politics"}', 
				'english_name' => 'Politics', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '14', 
				'game_name' => '_json:{"1":"Water Polo","2":"Water Polo"}', 
				'english_name' => 'Water Polo', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '15', 
				'game_name' => '_json:{"1":"Diving","2":"Diving"}', 
				'english_name' => 'Diving', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '16', 
				'game_name' => '_json:{"1":"Boxing","2":"Boxing"}', 
				'english_name' => 'Boxing', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '17', 
				'game_name' => '_json:{"1":"Archery","2":"Archery"}', 
				'english_name' => 'Archery', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '18', 
				'game_name' => '_json:{"1":"Table Tennis","2":"Table Tennis"}', 
				'english_name' => 'Table Tennis', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '19', 
				'game_name' => '_json:{"1":"Weightlifting","2":"Weightlifting"}', 
				'english_name' => 'Weightlifting', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '20', 
				'game_name' => '_json:{"1":"Canoeing","2":"Canoeing"}', 
				'english_name' => 'Canoeing', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '21', 
				'game_name' => '_json:{"1":"Gymnastics","2":"Gymnastics"}', 
				'english_name' => 'Gymnastics', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '22', 
				'game_name' => '_json:{"1":"Athletics","2":"Athletics"}', 
				'english_name' => 'Athletics', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '23', 
				'game_name' => '_json:{"1":"Equestrian","2":"Equestrian"}', 
				'english_name' => 'Equestrian', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '24', 
				'game_name' => '_json:{"1":"Handball","2":"Handball"}', 
				'english_name' => 'Handball', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '25', 
				'game_name' => '_json:{"1":"Darts","2":"Darts"}', 
				'english_name' => 'Darts', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '26', 
				'game_name' => '_json:{"1":"Rugby","2":"Rugby"}', 
				'english_name' => 'Rugby', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '28', 
				'game_name' => '_json:{"1":"Field Hockey","2":"Field Hockey"}', 
				'english_name' => 'Field Hockey', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '29', 
				'game_name' => '_json:{"1":"Winter Sport","2":"Winter Sport"}', 
				'english_name' => 'Winter Sport', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '30', 
				'game_name' => '_json:{"1":"Squash","2":"Squash"}', 
				'english_name' => 'Squash', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '31', 
				'game_name' => '_json:{"1":"Entertainment","2":"Entertainment"}', 
				'english_name' => 'Entertainment', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '32', 
				'game_name' => '_json:{"1":"Net Ball","2":"Net Ball"}', 
				'english_name' => 'Net Ball', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '33', 
				'game_name' => '_json:{"1":"Cycling","2":"Cycling"}', 
				'english_name' => 'Cycling', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '34', 
				'game_name' => '_json:{"1":"Fencing","2":"Fencing"}', 
				'english_name' => 'Fencing', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '35', 
				'game_name' => '_json:{"1":"Judo","2":"Judo"}', 
				'english_name' => 'Judo', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '36', 
				'game_name' => '_json:{"1":"M. Pentathlon","2":"M. Pentathlon"}', 
				'english_name' => 'M. Pentathlon', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '37', 
				'game_name' => '_json:{"1":"Rowing","2":"Rowing"}', 
				'english_name' => 'Rowing', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '38', 
				'game_name' => '_json:{"1":"Sailing","2":"Sailing"}', 
				'english_name' => 'Sailing', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '39', 
				'game_name' => '_json:{"1":"Shooting","2":"Shooting"}', 
				'english_name' => 'Shooting', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '40', 
				'game_name' => '_json:{"1":"Taekwondo","2":"Taekwondo"}', 
				'english_name' => 'Taekwondo', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '41', 
				'game_name' => '_json:{"1":"Triathlon","2":"Triathlon"}', 
				'english_name' => 'Triathlon', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '42', 
				'game_name' => '_json:{"1":"Wrestling","2":"Wrestling"}', 
				'english_name' => 'Wrestling', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '43', 
				'game_name' => '_json:{"1":"E Sports","2":"E Sports"}', 
				'english_name' => 'E Sports', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '44', 
				'game_name' => '_json:{"1":"Muay Thai","2":"Muay Thai"}', 
				'english_name' => 'Muay Thai', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '50', 
				'game_name' => '_json:{"1":"Cricket","2":"Cricket"}', 
				'english_name' => 'Cricket', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '99', 
				'game_name' => '_json:{"1":"Other Sports","2":"Other Sports"}', 
				'english_name' => 'Other Sports', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '161', 
				'game_name' => '_json:{"1":"Number Game","2":"Number Game"}', 
				'english_name' => 'Number Game', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '180', 
				'game_name' => '_json:{"1":"Virtual Soccer","2":"Virtual Soccer"}', 
				'english_name' => 'Virtual Soccer', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '181', 
				'game_name' => '_json:{"1":"Virtual Horse Racing","2":"Virtual Horse Racing"}', 
				'english_name' => 'Virtual Horse Racing', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '182', 
				'game_name' => '_json:{"1":"Virtual Greyhound","2":"Virtual Greyhound"}', 
				'english_name' => 'Virtual Greyhound', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '183', 
				'game_name' => '_json:{"1":"Virtual Speedway","2":"Virtual Speedway"}', 
				'english_name' => 'Virtual Speedway', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '184', 
				'game_name' => '_json:{"1":"Virtual F1","2":"Virtual F1"}', 
				'english_name' => 'Virtual F1', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '185', 
				'game_name' => '_json:{"1":"Virtual Cycling","2":"Virtual Cycling"}', 
				'english_name' => 'Virtual Cycling', 
			),
			array(
				'game_platform_id' => 58, 
				'game_code' => '210', 
				'game_name' => '_json:{"1":"Mobile","2":"Mobile"}', 
				'english_name' => 'Mobile', 
			),
		);

		$this->db->where('game_platform_id', ONEWORKS_API);
		$this->db->update_batch('game_description', $game_descriptions, 'game_code');

		$this->db->trans_complete();
	}

	public function down() {
	}
}
