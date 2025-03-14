<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_hb_game_description_20161102 extends CI_Migration {
	
	const FLAG_TRUE = 1;
	const FLAG_FALSE = 0;

	public function up() {

		$this->db->start_trans();

		$game_descriptions = array(
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGAllForOne', 
				'game_name' => '_json:{"1":"All For One","2":"三剑客"}', 
				'english_name' => 'All For One',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGArcticWonders', 
				'game_name' => '_json:{"1":"Arctic Wonders","2":"北极奇迹"}', 
				'english_name' => 'Arctic Wonders',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGAzlansGold', 
				'game_name' => '_json:{"1":"Aztlan’s Gold","2":"亚兹特兰金"}', 
				'english_name' => 'Aztlan’s Gold',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGBarnstormerBucks', 
				'game_name' => '_json:{"1":"Barnstormer Bucks","2":"农场现金"}', 
				'english_name' => 'Barnstormer Bucks',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGBikiniIsland', 
				'game_name' => '_json:{"1":"Bikini Island","2":"比基尼岛"}', 
				'english_name' => 'Bikini Island',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGBlackbeardsBounty', 
				'game_name' => '_json:{"1":"Blackbeards Bounty","2":"黑胡子赏金"}', 
				'english_name' => 'Blackbeards Bounty',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGCarnivalCash', 
				'game_name' => '_json:{"1":"Carnival Cash","2":"现金嘉年华"}', 
				'english_name' => 'Carnival Cash',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGCashReef', 
				'game_name' => '_json:{"1":"Cash Reef","2":"金钱礁"}', 
				'english_name' => 'Cash Reef',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGCashosaurus', 
				'game_name' => '_json:{"1":"Cashosaurus","2":"土豪恐龙"}', 
				'english_name' => 'Cashosaurus',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGDiscoFunk', 
				'game_name' => '_json:{"1":"Disco Funk","2":"舞厅飙舞"}', 
				'english_name' => 'Disco Funk',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGDrFeelgood', 
				'game_name' => '_json:{"1":"Dr Feelgood","2":"好感医生"}', 
				'english_name' => 'Dr Feelgood',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGTheDragonCastle', 
				'game_name' => '_json:{"1":"Dragon Castle","2":"龙之城堡"}', 
				'english_name' => 'Dragon Castle',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGDragonsRealm', 
				'game_name' => '_json:{"1":"Dragons Realm","2":"神龙之境"}', 
				'english_name' => 'Dragons Realm',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGEgyptianDreams', 
				'game_name' => '_json:{"1":"Egyptian Dreams","2":"埃及古梦"}', 
				'english_name' => 'Egyptian Dreams',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGFlyingHigh', 
				'game_name' => '_json:{"1":"Flying High","2":"高空飞翔"}', 
				'english_name' => 'Flying High',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGFrontierFortunes', 
				'game_name' => '_json:{"1":"Frontier Fortunes","2":"边境之福"}', 
				'english_name' => 'Frontier Fortunes',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGGoldenUnicorn', 
				'game_name' => '_json:{"1":"Golden Unicorn","2":"金麒麟"}', 
				'english_name' => 'Golden Unicorn',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGGrapeEscape', 
				'game_name' => '_json:{"1":"Grape Escape","2":"葡萄越狱"}', 
				'english_name' => 'Grape Escape',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGHauntedHouse', 
				'game_name' => '_json:{"1":"Haunted House","2":"鬼屋"}', 
				'english_name' => 'Haunted House',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGIndianCashCatcher', 
				'game_name' => '_json:{"1":"Indian Cash Catcher","2":"印第安追梦"}', 
				'english_name' => 'Indian Cash Catcher',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGJungleRumble', 
				'game_name' => '_json:{"1":"Jungle Rumble","2":"丛林怒吼"}', 
				'english_name' => 'Jungle Rumble',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGKingTutsTomb', 
				'game_name' => '_json:{"1":"King Tut’s Tomb","2":"图坦卡蒙之墓"}', 
				'english_name' => 'King Tut’s Tomb',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGLittleGreenMoney', 
				'game_name' => '_json:{"1":"Little Green Money","2":"太空小绿人"}', 
				'english_name' => 'Little Green Money',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGMonsterMashCash', 
				'game_name' => '_json:{"1":"Monster Mash Cash","2":"怪物聚集"}', 
				'english_name' => 'Monster Mash Cash',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGMrBling', 
				'game_name' => '_json:{"1":"Mr Bling","2":"珠光宝气"}', 
				'english_name' => 'Mr Bling',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGMummyMoney', 
				'game_name' => '_json:{"1":"Mummy Money","2":"金钱木乃伊"}', 
				'english_name' => 'Mummy Money',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGMysticFortune', 
				'game_name' => '_json:{"1":"Mystic Fortune","2":"神秘宝藏"}', 
				'english_name' => 'Mystic Fortune',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGPamperMe', 
				'game_name' => '_json:{"1":"Pamper Me","2":"俏佳人"}', 
				'english_name' => 'Pamper Me',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGPiratesPlunder', 
				'game_name' => '_json:{"1":"Pirate’s Plunder","2":"海盗掠宝"}', 
				'english_name' => 'Pirate’s Plunder',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGPoolShark', 
				'game_name' => '_json:{"1":"Pool Shark","2":"台球鲨鱼"}', 
				'english_name' => 'Pool Shark',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGPuckerUpPrince', 
				'game_name' => '_json:{"1":"Pucker Up Prince","2":"青蛙王子"}', 
				'english_name' => 'Pucker Up Prince',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGQueenOfQueens243', 
				'game_name' => '_json:{"1":"Queen of Queens","2":"女王之上"}', 
				'english_name' => 'Queen of Queens',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGQueenOfQueens1024', 
				'game_name' => '_json:{"1":"Queen of Queens II","2":"女王至上II"}', 
				'english_name' => 'Queen of Queens II',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGRideEmCowboy', 
				'game_name' => '_json:{"1":"Ride Em Cowboy","2":"幸运牛仔"}', 
				'english_name' => 'Ride Em Cowboy',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGRodeoDrive', 
				'game_name' => '_json:{"1":"Rodeo Drive","2":"罗迪欧大道"}', 
				'english_name' => 'Rodeo Drive',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGShaolinFortunes243', 
				'game_name' => '_json:{"1":"Shaolin Fortunes","2":"少林宝藏"}', 
				'english_name' => 'Shaolin Fortunes',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGShaolinFortunes100', 
				'game_name' => '_json:{"1":"Shaolin Fortunes 100","2":"少林宝藏 II"}', 
				'english_name' => 'Shaolin Fortunes 100',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGShogunsLand', 
				'game_name' => '_json:{"1":"Shogun’s Land","2":"幕府之地"}', 
				'english_name' => 'Shogun’s Land',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGSOS', 
				'game_name' => '_json:{"1":"SOS","2":"求救信号"}', 
				'english_name' => 'SOS',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGSpaceFortune', 
				'game_name' => '_json:{"1":"Space Fortune","2":"太空宝藏"}', 
				'english_name' => 'Space Fortune',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGSuperStrike', 
				'game_name' => '_json:{"1":"Super Strike","2":"好球"}', 
				'english_name' => 'Super Strike',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGTheBigDeal', 
				'game_name' => '_json:{"1":"The Big Deal","2":"重要人物"}', 
				'english_name' => 'The Big Deal',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGTowerOfPizza', 
				'game_name' => '_json:{"1":"Tower Of Pizza","2":"披萨塔"}', 
				'english_name' => 'Tower Of Pizza',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGVikingsPlunder', 
				'game_name' => '_json:{"1":"Viking’s Plunder","2":"维京掠宝"}', 
				'english_name' => 'Viking’s Plunder',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGWeirdScience', 
				'game_name' => '_json:{"1":"Weird Science","2":"科学怪人"}', 
				'english_name' => 'Weird Science',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGZeus', 
				'game_name' => '_json:{"1":"Zeus","2":"宙斯"}', 
				'english_name' => 'Zeus',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGGambleBeatDealer', 
				'game_name' => '_json:{"1":"Gamble - Beat the Dealer","2":"翻倍 －赢过庄家"}', 
				'english_name' => 'Gamble - Beat the Dealer',
				),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGSirBlingalot', 
				'game_name' => '_json:{"1":"Sir Blingalot","2":"闪亮骑士"}', 
				'english_name' => 'Sir Blingalot',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGDoubleODollars', 
				'game_name' => '_json:{"1":"Double O Dollars","2":"双赢密探"}', 
				'english_name' => 'Double O Dollars',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGSkysTheLimit', 
				'game_name' => '_json:{"1":"Sky’s the Limit","2":"天空之际"}', 
				'english_name' => 'Sky’s the Limit',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGTreasureDiver', 
				'game_name' => '_json:{"1":"Treasure Diver","2":"深海寻宝"}', 
				'english_name' => 'Treasure Diver',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGKanesinferno', 
				'game_name' => '_json:{"1":"Kane’s Inferno","2":"凯恩地狱"}', 
				'english_name' => 'Kane’s Inferno',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGGalacticCash', 
				'game_name' => '_json:{"1":"Galactic Cash","2":"银河大战"}', 
				'english_name' => 'Galactic Cash',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGBuggyBonus', 
				'game_name' => '_json:{"1":"Buggy Bonus","2":"昆虫宝宝"}', 
				'english_name' => 'Buggy Bonus',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGTreasureTomb', 
				'game_name' => '_json:{"1":"Treasure Tomb","2":"古墓宝藏"}', 
				'english_name' => 'Treasure Tomb',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGZeus2', 
				'game_name' => '_json:{"1":"Zeus 2","2":"宙斯2"}', 
				'english_name' => 'Zeus 2',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGRuffledUp', 
				'game_name' => '_json:{"1":"Ruffled Up","2":"触电的小鸟"}', 
				'english_name' => 'Ruffled Up',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGFaCaiShen', 
				'game_name' => '_json:{"1":"Fa Cai Shen","2":"发财神"}', 
				'english_name' => 'Fa Cai Shen',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGBombsAway', 
				'game_name' => '_json:{"1":"Bombs Away","2":"炸弹追击"}', 
				'english_name' => 'Bombs Away',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGGoldRush', 
				'game_name' => '_json:{"1":"Gold Rush","2":"淘金疯狂"}', 
				'english_name' => 'Gold Rush',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGWickedWitch', 
				'game_name' => '_json:{"1":"Wicked Witch","2":"巫婆大财"}', 
				'english_name' => 'Wicked Witch',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGRomanEmpire', 
				'game_name' => '_json:{"1":"Roman Empire","2":"罗马帝国"}', 
				'english_name' => 'Roman Empire',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGDragonsThrone', 
				'game_name' => '_json:{"1":"Dragons Throne","2":"龙之宝座"}', 
				'english_name' => 'Dragons Throne',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGCoyoteCrash', 
				'game_name' => '_json:{"1":"Coyote Crash","2":"狼贼夺宝"}', 
				'english_name' => 'Coyote Crash',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGArcaneElements', 
				'game_name' => '_json:{"1":"Arcane Elements","2":"神秘元素"}', 
				'english_name' => 'Arcane Elements',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SG12Zodiacs', 
				'game_name' => '_json:{"1":"12 Zodiacs","2":"十二生肖"}', 
				'english_name' => '12 Zodiacs',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGSuperTwister', 
				'game_name' => '_json:{"1":"Super Twister","2":"超级龙卷风"}', 
				'english_name' => 'Super Twister',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGSparta', 
				'game_name' => '_json:{"1":"Sparta","2":"斯巴达"}', 
				'english_name' => 'Sparta',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'SGGangsters', 
				'game_name' => '_json:{"1":"Gangsters","2":"黑手党"}', 
				'english_name' => 'Gangsters',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'CaribbeanHoldem', 
				'game_name' => '_json:{"1":"Caribbean Hold’Em","2":"赌场德州扑克"}', 
				'english_name' => 'Caribbean Hold’Em',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'CaribbeanStud', 
				'game_name' => '_json:{"1":"Caribbean Stud","2":"加勒比克扑克"}', 
				'english_name' => 'Caribbean Stud',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'Blackjack', 
				'game_name' => '_json:{"1":"Blackjack 5 Hand","2":"黑杰克5手"}', 
				'english_name' => 'Blackjack 5 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'BlackjackDoubleExposure', 
				'game_name' => '_json:{"1":"Blackjack Double Exposure 5 Hand","2":"二十一点双重曝光5手"}', 
				'english_name' => 'Blackjack Double Exposure 5 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'Blackjack3H', 
				'game_name' => '_json:{"1":"Blackjack 3 Hand","2":"三手酒杯"}', 
				'english_name' => 'Blackjack 3 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'Blackjack3HDoubleExposure', 
				'game_name' => '_json:{"1":"Blackjack Double Exposure 3 Hand","2":"二十一点双重曝光3手"}', 
				'english_name' => 'Blackjack Double Exposure 3 Hand'
			,),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'EURoulette', 
				'game_name' => '_json:{"1":"European Roulette","2":"欧洲轮盘"}', 
				'english_name' => 'European Roulette',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'AmericanBaccarat', 
				'game_name' => '_json:{"1":"American Baccarat","2":"美式百家乐"}', 
				'english_name' => 'American Baccarat',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'Baccarat3HZC', 
				'game_name' => '_json:{"1":"American Baccarat Zero Commission","2":"美式百家乐零佣金"}', 
				'english_name' => 'American Baccarat Zero Commission',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'Sicbo', 
				'game_name' => '_json:{"1":"Sicbo","2":"（骰宝 手机版）"}', 
				'english_name' => 'Sicbo',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'AcesandEights1Hand', 
				'game_name' => '_json:{"1":"Aces & Eights 1 Hand","2":"尖子和八 1手"}', 
				'english_name' => 'Aces & Eights 1 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'AcesandEights5Hand', 
				'game_name' => '_json:{"1":"Aces & Eights 5 Hand","2":"尖子和八5手"}', 
				'english_name' => 'Aces & Eights 5 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'AcesandEights10Hand', 
				'game_name' => '_json:{"1":"Aces & Eights 10 Hand","2":"尖子和八10手"}', 
				'english_name' => 'Aces & Eights 10 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'AcesandEights50Hand', 
				'game_name' => '_json:{"1":"Aces & Eights 50 Hand","2":"尖子和八50手"}', 
				'english_name' => 'Aces & Eights 50 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'AcesandEights100Hand', 
				'game_name' => '_json:{"1":"Aces & Eights 100 Hand","2":"尖子和八 100手"}', 
				'english_name' => 'Aces & Eights 100 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'AllAmericanPoker1Hand', 
				'game_name' => '_json:{"1":"All American Poker 1 Hand","2":"美国扑克1手"}', 
				'english_name' => 'All American Poker 1 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'AllAmericanPoker5Hand', 
				'game_name' => '_json:{"1":"All American Poker 5 Hand","2":"美国扑克5手"}', 
				'english_name' => 'All American Poker 5 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'AllAmericanPoker10Hand', 
				'game_name' => '_json:{"1":"All American Poker 10 Hand","2":"美国扑克10手"}', 
				'english_name' => 'All American Poker 10 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'AllAmericanPoker50Hand', 
				'game_name' => '_json:{"1":"All American Poker 50 Hand","2":"美国扑克50手"}', 
				'english_name' => 'All American Poker 50 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'AllAmericanPoker100Hand', 
				'game_name' => '_json:{"1":"All American Poker 100 Hand","2":"美国扑克100手"}', 
				'english_name' => 'All American Poker 100 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'BonusDuecesWild1Hand', 
				'game_name' => '_json:{"1":"Bonus Deuces Wild 1 Hand","2":"红利局末平分1手"}', 
				'english_name' => 'Bonus Deuces Wild 1 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'BonusDuecesWild5Hand', 
				'game_name' => '_json:{"1":"Bonus Deuces Wild 5 Hand","2":"红利局末平分5手"}', 
				'english_name' => 'Bonus Deuces Wild 5 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'BonusDuecesWild10Hand', 
				'game_name' => '_json:{"1":"Bonus Deuces Wild 10 Hand","2":"红利局末平分10手"}', 
				'english_name' => 'Bonus Deuces Wild 10 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'BonusDuecesWild50Hand', 
				'game_name' => '_json:{"1":"Bonus Deuces Wild 50 Hand","2":"红利局末平分50手"}', 
				'english_name' => 'Bonus Deuces Wild 50 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'BonusDuecesWild100Hand', 
				'game_name' => '_json:{"1":"Bonus Deuces Wild 100 Hand","2":"红利局末平分100手"}', 
				'english_name' => 'Bonus Deuces Wild 100 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'BonusPoker1Hand', 
				'game_name' => '_json:{"1":"Bonus Poker 1 Hand","2":"红利扑克1手"}', 
				'english_name' => 'Bonus Poker 1 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'BonusPoker5Hand', 
				'game_name' => '_json:{"1":"Bonus Poker 5 Hand","2":"红利扑克5手"}', 
				'english_name' => 'Bonus Poker 5 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'BonusPoker10Hand', 
				'game_name' => '_json:{"1":"Bonus Poker 10 Hand","2":"红利扑克10手"}', 
				'english_name' => 'Bonus Poker 10 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'BonusPoker50Hand', 
				'game_name' => '_json:{"1":"Bonus Poker 50 Hand","2":"红利扑克50手"}', 
				'english_name' => 'Bonus Poker 50 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'BonusPoker100Hand', 
				'game_name' => '_json:{"1":"Bonus Poker 100 Hand","2":"红利扑克100手"}', 
				'english_name' => 'Bonus Poker 100 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DuecesWild1Hand', 
				'game_name' => '_json:{"1":"Deuces Wild 1 Hand","2":"局末平分1手"}', 
				'english_name' => 'Deuces Wild 1 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DuecesWild5Hand', 
				'game_name' => '_json:{"1":"Deuces Wild 5 Hand","2":"局末平分5手"}', 
				'english_name' => 'Deuces Wild 5 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DuecesWild10Hand', 
				'game_name' => '_json:{"1":"Deuces Wild 10 Hand","2":"局末平分10手"}', 
				'english_name' => 'Deuces Wild 10 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DuecesWild50Hand', 
				'game_name' => '_json:{"1":"Deuces Wild 50 Hand","2":"局末平分50手"}', 
				'english_name' => 'Deuces Wild 50 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DuecesWild100Hand', 
				'game_name' => '_json:{"1":"Deuces Wild 100 Hand","2":"局末平分100手"}', 
				'english_name' => 'Deuces Wild 100 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DoubleBonusPoker1Hand', 
				'game_name' => '_json:{"1":"Double Bonus Poker 1 Hand","2":"双大床红利扑克1手"}', 
				'english_name' => 'Double Bonus Poker 1 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DoubleBonusPoker5Hand', 
				'game_name' => '_json:{"1":"Double Bonus Poker 5 Hand","2":"双大床红利扑克5手"}', 
				'english_name' => 'Double Bonus Poker 5 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DoubleBonusPoker10Hand', 
				'game_name' => '_json:{"1":"Double Bonus Poker 10 Hand","2":"双大床红利扑克10手"}', 
				'english_name' => 'Double Bonus Poker 10 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DoubleBonusPoker50Hand', 
				'game_name' => '_json:{"1":"Double Bonus Poker 50 Hand","2":"双大床红利扑克50手"}', 
				'english_name' => 'Double Bonus Poker 50 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DoubleBonusPoker100Hand', 
				'game_name' => '_json:{"1":"Double Bonus Poker 100 Hand","2":"双大床红利扑克100手"}', 
				'english_name' => 'Double Bonus Poker 100 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DoubleDoubleBonusPoker1Hand', 
				'game_name' => '_json:{"1":"Double Double Bonus Poker 1 Hand","2":"双双大床红利扑克1手"}', 
				'english_name' => 'Double Double Bonus Poker 1 Hand'
			,),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DoubleDoubleBonusPoker5Hand', 
				'game_name' => '_json:{"1":"Double Double Bonus Poker 5 Hand","2":"双双大床红利扑克5手"}', 
				'english_name' => 'Double Double Bonus Poker 5 Hand'
			,),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DoubleDoubleBonusPoker10Hand', 
				'game_name' => '_json:{"1":"Double Double Bonus Poker 10 Hand","2":"双双大床红利扑克10手"}', 
				'english_name' => 'Double Double Bonus Poker 
			10 Hand',),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DoubleDoubleBonusPoker50Hand', 
				'game_name' => '_json:{"1":"Double Double Bonus Poker 50 Hand","2":"双双大床红利扑克50手"}', 
				'english_name' => 'Double Double Bonus Poker 
			50 Hand',),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'DoubleDoubleBonusPoker100Hand', 
				'game_name' => '_json:{"1":"Double Double Bonus Poker 100 Hand","2":"双双大床红利扑克100手"}', 
				'english_name' => 'Double Double Bonus Poker 1
			00 Hand',),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'JacksorBetter1Hand', 
				'game_name' => '_json:{"1":"Jacks or Better 1 Hand","2":"千斤顶或更好1手"}', 
				'english_name' => 'Jacks or Better 1 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'JacksorBetter5Hand', 
				'game_name' => '_json:{"1":"Jacks or Better 5 Hand","2":"千斤顶或更好5手"}', 
				'english_name' => 'Jacks or Better 5 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'JacksorBetter10Hand', 
				'game_name' => '_json:{"1":"Jacks or Better 10 Hand","2":"千斤顶或更好10手"}', 
				'english_name' => 'Jacks or Better 10 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'JacksorBetter50Hand', 
				'game_name' => '_json:{"1":"Jacks or Better 50 Hand","2":"千斤顶或更好50手"}', 
				'english_name' => 'Jacks or Better 50 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'JacksorBetter100Hand', 
				'game_name' => '_json:{"1":"Jacks or Better 100 Hand","2":"千斤顶或更好100手"}', 
				'english_name' => 'Jacks or Better 100 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'JokerPoker1Hand', 
				'game_name' => '_json:{"1":"Joker Poker 1 Hand","2":"小丑扑克1手"}', 
				'english_name' => 'Joker Poker 1 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'JokerPoker5Hand', 
				'game_name' => '_json:{"1":"Joker Poker 5 Hand","2":"小丑扑克5手"}', 
				'english_name' => 'Joker Poker 5 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'JokerPoker10Hand', 
				'game_name' => '_json:{"1":"Joker Poker 10 Hand","2":"小丑扑克10手"}', 
				'english_name' => 'Joker Poker 10 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'JokerPoker50Hand', 
				'game_name' => '_json:{"1":"Joker Poker 50 Hand","2":"小丑扑克50手"}', 
				'english_name' => 'Joker Poker 50 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'JokerPoker100Hand', 
				'game_name' => '_json:{"1":"Joker Poker 100 Hand","2":"小丑扑克100手"}', 
				'english_name' => 'Joker Poker 100 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'TensorBetter1Hand', 
				'game_name' => '_json:{"1":"Tens Or Better 1 Hand","2":"数万或更好1手"}', 
				'english_name' => 'Tens Or Better 1 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'TensorBetter5Hand', 
				'game_name' => '_json:{"1":"Tens Or Better 5 Hand","2":"数万或更好5手"}', 
				'english_name' => 'Tens Or Better 5 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'TensorBetter10Hand', 
				'game_name' => '_json:{"1":"Tens Or Better 10 Hand","2":"数万或更好10手"}', 
				'english_name' => 'Tens Or Better 10 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'TensorBetter50Hand', 
				'game_name' => '_json:{"1":"Tens Or Better 50 Hand","2":"数万或更好50手"}', 
				'english_name' => 'Tens Or Better 50 Hand',
			),
			array(
				'game_platform_id' => 38, 
				'game_code' => 'TensorBetter100Hand', 
				'game_name' => '_json:{"1":"Tens Or Better 100 Hand","2":"数万或更好100手"}', 
				'english_name' => 'Tens Or Better 100 Hand',
			),
		);

		$this->db->where('game_platform_id', HB_API);
		$this->db->update_batch('game_description', $game_descriptions, 'game_code',500);

		$this->db->trans_complete();
	}

	public function down() {
	}
}
