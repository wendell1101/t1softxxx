<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_AGSHABA_to_game_type_and_game_description_201611152128 extends CI_Migration {

	const FLAG_TRUE = 1;
	const FLAG_FALSE = 0;

	public function up() {
		$this->db->trans_start();
		$data = array(
			array(
				'game_type' => '_json:{"1":"AG SHABA Sports Type Table","2":"AG 沙巴体育 "}',
				'game_type_lang' => 'ag_shaba_sports',
				'status' => self::FLAG_TRUE,
				'flag_show_in_site' => self::FLAG_TRUE,
				'game_description_list' => array(
					array(
						'game_name' => '_json:{"1":"Soccer","2":"英式足球"}',
						'english_name' => 'Soccer',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '1',
						'external_game_id' => '1',
					),
					array(
						'game_name' => '_json:{"1":"Basketball","2":"篮球"}',
						'english_name' => 'Basketball',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '2',
						'external_game_id' => '2',
					),
					array(
						'game_name' => '_json:{"1":"Football","2":"橄榄球"}',
						'english_name' => 'Football',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '3',
						'external_game_id' => '3',
					),
					array(
						'game_name' => '_json:{"1":"Ice Hockey","2":"冰上曲棍球"}',
						'english_name' => 'Ice Hockey',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '4',
						'external_game_id' => '4',
					),
					array(
						'game_name' => '_json:{"1":"Tennis","2":"网球"}',
						'english_name' => 'Tennis',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '5',
						'external_game_id' => '5',
					),
					array(
						'game_name' => '_json:{"1":"Volleyball","2":"排球"}',
						'english_name' => 'Volleyball',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '6',
						'external_game_id' => '6',
					),
					array(
						'game_name' => '_json:{"1":"Billiards","2":"台球"}',
						'english_name' => 'Billiards',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '7',
						'external_game_id' => '7',
					),
					array(
						'game_name' => '_json:{"1":"Baseball","2":"棒球"}',
						'english_name' => 'Baseball',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '8',
						'external_game_id' => '8',
					),
					array(
						'game_name' => '_json:{"1":"Badminton","2":"羽毛球"}',
						'english_name' => 'Badminton',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '9',
						'external_game_id' => '9',
					),
					array(
						'game_name' => '_json:{"1":"Golf","2":"高尔夫"}',
						'english_name' => 'Golf',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '10',
						'external_game_id' => '10',
					), array(
						'game_name' => '_json:{"1":"Motorsports","2":"赛车"}',
						'english_name' => 'Motorsports',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '11',
						'external_game_id' => '11',
					),
					array(
						'game_name' => '_json:{"1":"Swimming","2":"游泳"}',
						'english_name' => 'Swimming',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '12',
						'external_game_id' => '12',
					),
					array(
						'game_name' => '_json:{"1":"Politics","2":"政治"}',
						'english_name' => 'Politics',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '13',
						'external_game_id' => '13',
					),
					array(
						'game_name' => '_json:{"1":"Water Polo","2":"水球"}',
						'english_name' => 'Water Polo',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '14',
						'external_game_id' => '14',
					),
					array(
						'game_name' => '_json:{"1":"Diving","2":"潜水"}',
						'english_name' => 'Diving',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '15',
						'external_game_id' => '15',
					),
					array(
						'game_name' => '_json:{"1":"Boxing","2":"射箭"}',
						'english_name' => 'Boxing',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '16',
						'external_game_id' => '16',
					),
					array(
						'game_name' => '_json:{"1":"Archery","2":"射箭"}',
						'english_name' => 'Archery',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '17',
						'external_game_id' => '17',
					),
					array(
						'game_name' => '_json:{"1":"Table Tennis","2":"乒乓球"}',
						'english_name' => 'Table Tennis',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '18',
						'external_game_id' => '18',
					),
					array(
						'game_name' => '_json:{"1":"Weightlifting","2":".举重"}',
						'english_name' => 'Weightlifting',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '19',
						'external_game_id' => '19',
					),
					array(
						'game_name' => '_json:{"1":"Canoeing","2":"划独木舟"}',
						'english_name' => 'Canoeing',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '20',
						'external_game_id' => '20',
					),
					array(
						'game_name' => '_json:{"1":"Gymnastics","2":"体操"}',
						'english_name' => 'Gymnastics',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '21',
						'external_game_id' => '21',
					),
					array(
						'game_name' => '_json:{"1":"Athletics","2":"田径"}',
						'english_name' => 'Athletics',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '22',
						'external_game_id' => '22',
					),
					array(
						'game_name' => '_json:{"1":"Equestrian","2":"马术"}',
						'english_name' => 'Equestrian',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '23',
						'external_game_id' => '23',
					),
					array(
						'game_name' => '_json:{"1":"Handball","2":"手球"}',
						'english_name' => 'Handball',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '24',
						'external_game_id' => '24',
					),
					array(
						'game_name' => '_json:{"1":"Darts","2":"飞镖"}',
						'english_name' => 'Darts',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '25',
						'external_game_id' => '25',
					),
					array(
						'game_name' => '_json:{"1":"Rugby","2":"橄榄球"}',
						'english_name' => 'Rugby',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '26',
						'external_game_id' => '26',
					),
					array(
						'game_name' => '_json:{"1":"Cricket","2":"板球"}',
						'english_name' => 'Cricket',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '27',
						'external_game_id' => '27',
					), 
					array(
						'game_name' => '_json:{"1":"Field Hockey","2":"曲棍球"}',
						'english_name' => 'Field Hockey',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '28',
						'external_game_id' => '28',
					), array(
						'game_name' => '_json:{"1":"Winter Sport","2":"冬季运动"}',
						'english_name' => 'Winter Sport',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '29',
						'external_game_id' => '29',
					),
					array(
						'game_name' => '_json:{"1":"Squash","2":"壁球"}',
						'english_name' => 'Squash',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '30',
						'external_game_id' => '30',
					),
					array(
						'game_name' => '_json:{"1":"Entertainment","2":"游艺"}',
						'english_name' => 'Entertainment',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '31',
						'external_game_id' => '31',
					),
					array(
						'game_name' => '_json:{"1":"Net Ball","2":"净球"}',
						'english_name' => 'Net Ball',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '32',
						'external_game_id' => '32',
					),
					array(
						'game_name' => '_json:{"1":"Cycling","2":"脚踏车"}',
						'english_name' => 'Cycling',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '33',
						'external_game_id' => '33',
					),
					array(
						'game_name' => '_json:{"1":"Fencing","2":".剑术"}',
						'english_name' => 'Fencing',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '34',
						'external_game_id' => '34',
					),
					array(
						'game_name' => '_json:{"1":"Judo","2":"柔道"}',
						'english_name' => 'Judo',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '35',
						'external_game_id' => '35',
					),
					array(
						'game_name' => '_json:{"1":"M. Pentathlon","2":"五项锦标赛"}',
						'english_name' => 'M. Pentathlon',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '36',
						'external_game_id' => '36',
					),
					array(
						'game_name' => '_json:{"1":"Rowing","2":"划艇"}',
						'english_name' => 'Rowing',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '37',
						'external_game_id' => '37',
					),
					array(
						'game_name' => '_json:{"1":"Sailing","2":"航行"}',
						'english_name' => 'Sailing',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '38',
						'external_game_id' => '38',
					),
					array(
						'game_name' => '_json:{"1":"Shooting","2":"射击"}',
						'english_name' => 'Shooting',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '39',
						'external_game_id' => '39',
					),
					array(
						'game_name' => '_json:{"1":"Taekwondo","2":"跆拳道"}',
						'english_name' => 'Taekwondo',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '40',
						'external_game_id' => '40',
					),
					array(
						'game_name' => '_json:{"1":"Triathlon","2":"铁人三项"}',
						'english_name' => 'Triathlon',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '41',
						'external_game_id' => '41',
					),
					array(
						'game_name' => '_json:{"1":"Wrestling","2":"摔跤"}',
						'english_name' => 'Wrestling',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '42',
						'external_game_id' => '42',
					),
					array(
						'game_name' => '_json:{"1":"E Sports","2":"电子竞技"}',
						'english_name' => 'E Sports',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '43',
						'external_game_id' => '43',
					),
					// array(
					// 	'game_name' => '_json:{"1":"Muay Thai","2":"Muay Thai"}',
					// 	'english_name' => 'Muay Thai',
					// 	'flash_enabled' => self::FLAG_TRUE,
					// 	'html_five_enabled' => self::FLAG_FALSE,
					// 	'mobile_enabled' => self::FLAG_FALSE,
					// 	'game_code' => '44',
					// 	'external_game_id' => '44',
					// ),
					// array(
					// 	'game_name' => '_json:{"1":"Cricket","2":"Cricket"}',
					// 	'english_name' => 'Cricket',
					// 	'flash_enabled' => self::FLAG_TRUE,
					// 	'html_five_enabled' => self::FLAG_FALSE,
					// 	'mobile_enabled' => self::FLAG_FALSE,
					// 	'game_code' => '50',
					// 	'external_game_id' => '50',
					// ),
					array(
						'game_name' => '_json:{"1":"Other Sports","2":"其他体育"}',
						'english_name' => 'Other Sports',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '99',
						'external_game_id' => '99',
					),
					array(
						'game_name' => '_json:{"1":"Horse Racing","2":"赛马"}',
						'english_name' => 'Horse Racing',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '151',
						'external_game_id' => '151',
					),
					array(
						'game_name' => '_json:{"1":"Greyhounds","2":"灰猎犬"}',
						'english_name' => 'Greyhounds',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '152',
						'external_game_id' => '152',
					),
					array(
						'game_name' => '_json:{"1":"Harness","2":"马具"}',
						'english_name' => 'Harness',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '153',
						'external_game_id' => '153',
					),
					array(
						'game_name' => '_json:{"1":"HorseRacing FixedOdds","2":"赛马固定赔率"}',
						'english_name' => 'HorseRacing FixedOdds',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '154',
						'external_game_id' => '154',
					),
					
					array(
						'game_name' => '_json:{"1":"Number Game","2":"数字游戏"}',
						'english_name' => 'Number Game',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '161',
						'external_game_id' => '161',
					),
					array(
						'game_name' => '_json:{"1":"Live Casino","2":"真人娱乐"}',
						'english_name' => 'Live Casino',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '162',
						'external_game_id' => '162',
					),
					array(
						'game_name' => '_json:{"1":"Virtual Soccer","2":"虚拟足球"}',
						'english_name' => 'Virtual Soccer',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '180',
						'external_game_id' => '180',
					),
					array(
						'game_name' => '_json:{"1":"Virtual Horse Racing","2":"虚拟赛马"}',
						'english_name' => 'Virtual Horse Racing',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '181',
						'external_game_id' => '181',
					),
					array(
						'game_name' => '_json:{"1":"Virtual Greyhound","2":"虚拟灵狮"}',
						'english_name' => 'Virtual Greyhound',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '182',
						'external_game_id' => '182',
					),
					array(
						'game_name' => '_json:{"1":"Virtual Speedway","2":"虚拟高速公路"}',
						'english_name' => 'Virtual Speedway',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '183',
						'external_game_id' => '183',
					),

					array(
						'game_name' => '_json:{"1":"Virtual F1","2":"虚拟F1"}',
						'english_name' => 'Virtual F1',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '184',
						'external_game_id' => '184',
					),
					array(
						'game_name' => '_json:{"1":"Virtual Cycling","2":"虚拟循环"}',
						'english_name' => 'Virtual Cycling',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '185',
						'external_game_id' => '185',
					),
					array(
						'game_name' => '_json:{"1":"Virtual Tennis","2":"虚拟网球"}',
						'english_name' => 'Virtual Tennis',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '186',
						'external_game_id' => '186',
					),
					array(
						'game_name' => '_json:{"1":"Keno","2":"基诺"}',
						'english_name' => 'Keno',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '202',
						'external_game_id' => '202',
					),
					array(
						'game_name' => '_json:{"1":"Casino","2":"娱乐场"}',
						'english_name' => 'Casino',
						'flash_enabled' => self::FLAG_TRUE,
						'html_five_enabled' => self::FLAG_FALSE,
						'mobile_enabled' => self::FLAG_FALSE,
						'game_code' => '251',
						'external_game_id' => '251',
					),

				),
			)
			
		);

		$game_description_list = array();
		foreach ($data as $game_type) {

			$this->db->insert('game_type', array(
				'game_platform_id' => AGSHABA_API,
				'game_type' => $game_type['game_type'],
				'game_type_lang' => $game_type['game_type_lang'],
				'status' => $game_type['status'],
				'flag_show_in_site' => $game_type['flag_show_in_site'],
			));

			$game_type_id = $this->db->insert_id();
			foreach ($game_type['game_description_list'] as $game_description) {
				$game_description_list[] = array_merge(array(
					'game_platform_id' => AGSHABA_API,
					'game_type_id' => $game_type_id,
				), $game_description);
			}

		}

		$this->db->insert_batch('game_description', $game_description_list);
		$this->db->trans_complete();
	}

	public function down() {

		$this->db->trans_start();
		$this->db->delete('game_type', array('game_platform_id' => AGSHABA_API, 'game_type !='=> 'unknown'));
		$this->db->delete('game_description', array('game_platform_id' => AGSHABA_API,'game_name !='=> 'agshaba.unknown'));
		$this->db->trans_complete();
	}
}