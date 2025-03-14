<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_opus_game_description_20161026 extends CI_Migration {
	
	const FLAG_TRUE = 1;
	const FLAG_FALSE = 0;

	public function up() {
		
		$this->db->start_trans();

		$game_descriptions = array(
			array(
				'game_platform_id' => 71, 
				'game_code' => 'acesAndEights', 
				'game_name' => '_json:{"1":"Aces and Eights Poker", "2":"A及8牌"}', 
				'english_name' => 'Aces and Eights Poker',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'acesAndFaces', 
				'game_name' => '_json:{"1":"Aces and Faces Poker", "2":"A及花牌"}', 
				'english_name' => 'Aces and Faces Poker',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'bonusDeucesWild', 
				'game_name' => '_json:{"1":"Bonus Deuces Wild Poker", "2":"奖金大放送"}', 
				'english_name' => 'Bonus Deuces Wild Poker',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'deucesWild', 
				'game_name' => '_json:{"1":"Deuces Wild Poker", "2":"百搭二王"}', 
				'english_name' => 'Deuces Wild Poker',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'doubleDoubleBonus', 
				'game_name' => '_json:{"1":"Double Double Bonus Poker", "2":"翻倍红利扑克"}', 
				'english_name' => 'Double Double Bonus Poker',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'europeanRoulette', 
				'game_name' => '_json:{"1":"European Roulette Gold", "2":"黄金欧洲轮盘"}', 
				'english_name' => 'European Roulette Gold',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'jacksOrBetter', 
				'game_name' => '_json:{"1":"Jacks or Better Poker", "2":"对J高手"}', 
				'english_name' => 'Jacks or Better Poker',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'mermaidsMillions', 
				'game_name' => '_json:{"1":"Mermaids Millions", "2":"百万美人鱼"}', 
				'english_name' => 'Mermaids Millions',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'thunderstruck', 
				'game_name' => '_json:{"1":"Thunderstruck", "2":"雷神"}', 
				'english_name' => 'Thunderstruck',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'tombRaider', 
				'game_name' => '_json:{"1":"Tomb Raider", "2":"古墓奇兵"}', 
				'english_name' => 'Tomb Raider',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'europeanBlackjackGold', 
				'game_name' => '_json:{"1":"European Blackjack Gold", "2":"黄金欧洲21点"}', 
				'english_name' => 'European Blackjack Gold',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'ladiesNite', 
				'game_name' => '_json:{"1":"Ladies Nite", "2":"淑女派对"}', 
				'english_name' => 'Ladies Nite',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'springBreak', 
				'game_name' => '_json:{"1":"Spring Break", "2":"春假时光"}', 
				'english_name' => 'Spring Break',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'avalon', 
				'game_name' => '_json:{"1":"Avalon", "2":"阿瓦隆"}', 
				'english_name' => 'Avalon',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'agentJaneBlonde', 
				'game_name' => '_json:{"1":"Agent Jane Blonde", "2":"城市猎人"}', 
				'english_name' => 'Agent Jane Blonde',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'burningDesire', 
				'game_name' => '_json:{"1":"Burning Desire", "2":"燃烧的慾望"}', 
				'english_name' => 'Burning Desire',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'deckTheHalls', 
				'game_name' => '_json:{"1":"Deck the Halls", "2":"圣诞大餐"}', 
				'english_name' => 'Deck the Halls',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'adventurePalace', 
				'game_name' => '_json:{"1":"Adventure Palace", "2":"冒险丛林"}', 
				'english_name' => 'Adventure Palace',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'tallyHo', 
				'game_name' => '_json:{"1":"Tally Ho", "2":"狐狸爵士"}', 
				'english_name' => 'Tally Ho',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'cashSplash', 
				'game_name' => '_json:{"1":"Cash Splash 5 Reel", "2":"现金飞溅"}', 
				'english_name' => 'Cash Splash 5 Reel',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'majorMillions', 
				'game_name' => '_json:{"1":"Major Millions 5 Reel", "2":"百万富翁"}', 
				'english_name' => 'Major Millions 5 Reel',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'loaded', 
				'game_name' => '_json:{"1":"Loaded ", "2":"幸运嘻哈"}', 
				'english_name' => 'Loaded ',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'breakDaBankAgain', 
				'game_name' => '_json:{"1":"Break da Bank Again", "2":"银行抢匪2"}', 
				'english_name' => 'Break da Bank Again',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'treasureNile', 
				'game_name' => '_json:{"1":"Treasure Nile", "2":"尼罗河宝藏"}', 
				'english_name' => 'Treasure Nile',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'cashapillar', 
				'game_name' => '_json:{"1":"Cashapillar", "2":"昆虫派对"}', 
				'english_name' => 'Cashapillar',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'alaskanFishing', 
				'game_name' => '_json:{"1":"Alaskan Fishing", "2":"阿拉斯加垂钓"}', 
				'english_name' => 'Alaskan Fishing',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'stashOfTheTitans', 
				'game_name' => '_json:{"1":"Stash of the Titans", "2":"泰坦帝国"}', 
				'english_name' => 'Stash of the Titans',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'carnaval', 
				'game_name' => '_json:{"1":"Carnaval", "2":"狂欢节"}', 
				'english_name' => 'Carnaval',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'voila', 
				'game_name' => '_json:{"1":"Voila!", "2":"恋恋法国"}', 
				'english_name' => 'Voila!',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'lionsPride', 
				'game_name' => '_json:{"1":"Lion’s Pride", "2":"狮子的自尊"}', 
				'english_name' => 'Lion’s Pride',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'bigTop', 
				'game_name' => '_json:{"1":"Big Top", "2":"马戏团"}', 
				'english_name' => 'Big Top',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'breakDaBank', 
				'game_name' => '_json:{"1":"Break da Bank", "2":"银行抢匪"}', 
				'english_name' => 'Break da Bank',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'purePlatinum', 
				'game_name' => '_json:{"1":"Pure Platinum", "2":"白金俱乐部"}', 
				'english_name' => 'Pure Platinum',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => '5ReelDrive', 
				'game_name' => '_json:{"1":"5 Reel Drive", "2":"侠盗猎车手"}', 
				'english_name' => '5 Reel Drive',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'couchPotato', 
				'game_name' => '_json:{"1":"Couch Potato", "2":"慵懒土豆"}', 
				'english_name' => 'Couch Potato',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'halloweenies', 
				'game_name' => '_json:{"1":"Halloweenies", "2":"万圣节派对"}', 
				'english_name' => 'Halloweenies',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'whatAHoot', 
				'game_name' => '_json:{"1":"What a Hoot", "2":"猫头鹰乐园"}', 
				'english_name' => 'What a Hoot',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'thunderstruckII', 
				'game_name' => '_json:{"1":"Thunderstruck II", "2":"雷神2"}', 
				'english_name' => 'Thunderstruck II',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'reelThunder', 
				'game_name' => '_json:{"1":"Reel Thunder", "2":"雷霆风暴"}', 
				'english_name' => 'Reel Thunder',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'isis', 
				'game_name' => '_json:{"1":"Isis", "2":"伊西斯"}', 
				'english_name' => 'Isis',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'centreCourt', 
				'game_name' => '_json:{"1":"Centre Court", "2":"网球冠军"}', 
				'english_name' => 'Centre Court',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'theTwistedCircus', 
				'game_name' => '_json:{"1":"The Twisted Circus", "2":"反转马戏团"}', 
				'english_name' => 'The Twisted Circus',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'starlightKiss', 
				'game_name' => '_json:{"1":"Starlight Kiss", "2":"星光之吻"}', 
				'english_name' => 'Starlight Kiss',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'mayanPrincess', 
				'game_name' => '_json:{"1":"Mayan Princess", "2":"玛雅公主"}', 
				'english_name' => 'Mayan Princess',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'tigersEye', 
				'game_name' => '_json:{"1":"Tiger’s Eye", "2":"虎眼"}', 
				'english_name' => 'Tiger’s Eye',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'eaglesWings', 
				'game_name' => '_json:{"1":"Eagel’s Wings", "2":"疾风老鹰"}', 
				'english_name' => 'Eagel’s Wings',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'highSociety', 
				'game_name' => '_json:{"1":"High Society", "2":"上流社会"}', 
				'english_name' => 'High Society',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'hitman', 
				'game_name' => '_json:{"1":"Hitman", "2":"终极杀手"}', 
				'english_name' => 'Hitman',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'footballStar', 
				'game_name' => '_json:{"1":"Football Star", "2":"足球之巅"}', 
				'english_name' => 'Football Star',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'treasurePalace', 
				'game_name' => '_json:{"1":"Treasure Palace", "2":"宝藏宫殿"}', 
				'english_name' => 'Treasure Palace',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'beachBabes', 
				'game_name' => '_json:{"1":"Beach Babes", "2":"沙滩女孩"}', 
				'english_name' => 'Beach Babes',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'madHatters', 
				'game_name' => '_json:{"1":"Mad Hatters", "2":"疯狂帽匠"}', 
				'english_name' => 'Mad Hatters',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'theGrandJourney', 
				'game_name' => '_json:{"1":"The Grand Journey", "2":"探险之旅"}', 
				'english_name' => 'The Grand Journey',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'kathmandu', 
				'game_name' => '_json:{"1":"Kathmandu", "2":"加德满都"}', 
				'english_name' => 'Kathmandu',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'summertime', 
				'game_name' => '_json:{"1":"Summertime", "2":"暑假时光"}', 
				'english_name' => 'Summertime',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'silverFang', 
				'game_name' => '_json:{"1":"Silver Fang", "2":"银狼"}', 
				'english_name' => 'Silver Fang',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'mysticDreams', 
				'game_name' => '_json:{"1":"Mystic Dreams", "2":"神秘的梦"}', 
				'english_name' => 'Mystic Dreams',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'bushTelegraph', 
				'game_name' => '_json:{"1":"Bush Telegraph", "2":"丛林快讯"}', 
				'english_name' => 'Bush Telegraph',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'ageOfDiscovery', 
				'game_name' => '_json:{"1":"Age Of Discovery", "2":"大航海时代"}', 
				'english_name' => 'Age Of Discovery',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'classicBlackjackGold', 
				'game_name' => '_json:{"1":"Classic Blackjack Gold", "2":"黄金经典21点"}', 
				'english_name' => 'Classic Blackjack Gold',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'barsNStripes', 
				'game_name' => '_json:{"1":"Bars and Stripes", "2":"美式酒吧"}', 
				'english_name' => 'Bars and Stripes',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'atlanticCityBlackjackGold', 
				'game_name' => '_json:{"1":"Atlantic City Blackjack Gold", "2":"金牌大西洋城21点"}', 
				'english_name' => 'Atlantic City Blackjack Gold',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'rivieraRiches', 
				'game_name' => '_json:{"1":"Riviera Riches", "2":"瑞维拉财宝"}', 
				'english_name' => 'Riviera Riches',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'santaPaws', 
				'game_name' => '_json:{"1":"Santa Paws", "2":"冰雪圣诞村"}', 
				'english_name' => 'Santa Paws',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'bigKahuna', 
				'game_name' => '_json:{"1":"Big Kahuna", "2":"森林之王"}', 
				'english_name' => 'Big Kahuna',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'sureWin', 
				'game_name' => '_json:{"1":"Sure Win", "2":"必胜"}', 
				'english_name' => 'Sure Win',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'vegasDowntownBlackjackGold', 
				'game_name' => '_json:{"1":"Vegas Downtown Blackjack Gold", "2":"金牌拉斯维加斯市中心"}', 
				'english_name' => 'Vegas Downtown Blackjack Gold',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'vegasSingleDeckBlackjackGold', 
				'game_name' => '_json:{"1":"Vegas Single Deck Blackjack Gold", "2":"黄金拉斯维加斯(单副)"}', 
				'english_name' => 'Vegas Single Deck Blackjack Gold',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'cricketStar', 
				'game_name' => '_json:{"1":"Cricket Star", "2":"板球明星"}', 
				'english_name' => 'Cricket Star',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'secretAdmirer', 
				'game_name' => '_json:{"1":"Secret Admirer", "2":"暗恋"}', 
				'english_name' => 'Secret Admirer',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'vegasStripBlackjackGold', 
				'game_name' => '_json:{"1":"Vegas Strip  Blackjack Gold", "2":"黄金拉斯维加斯大道"}', 
				'english_name' => 'Vegas Strip  Blackjack Gold',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'luckyLeprechaun', 
				'game_name' => '_json:{"1":"Lucky Leprechaun", "2":"幸运妖精"}', 
				'english_name' => 'Lucky Leprechaun',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'rhymingReelsGeorgiePorgie', 
				'game_name' => '_json:{"1":"Rhyming Reels Georgie Porgie", "2":"乔治与柏志"}', 
				'english_name' => 'Rhyming Reels Georgie Porgie',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'harveys', 
				'game_name' => '_json:{"1":"Harveys", "2":"哈维斯的晚餐"}', 
				'english_name' => 'Harveys',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'boogieMonsters', 
				'game_name' => '_json:{"1":"Boogie Monsters", "2":"摇滚怪兽"}', 
				'english_name' => 'Boogie Monsters',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'liquidGold', 
				'game_name' => '_json:{"1":"Liquid Gold", "2":"液态黄金"}', 
				'english_name' => 'Liquid Gold',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'dragonsMyth', 
				'game_name' => '_json:{"1":"Dragons Myth", "2":"龙的神话"}', 
				'english_name' => 'Dragons Myth',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'coolWolf', 
				'game_name' => '_json:{"1":"Cool Wolf", "2":"酷派狼人"}', 
				'english_name' => 'Cool Wolf',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'breakAway', 
				'game_name' => '_json:{"1":"Break Away", "2":"冰上曲棍球"}', 
				'english_name' => 'Break Away',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'ariana', 
				'game_name' => '_json:{"1":"Ariana", "2":"阿丽亚娜"}', 
				'english_name' => 'Ariana',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'untamedGiantPanda', 
				'game_name' => '_json:{"1":"Untamed - Giant Panda", "2":"野生熊猫"}', 
				'english_name' => 'Untamed - Giant Panda',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'bikiniParty', 
				'game_name' => '_json:{"1":"Bikini Party", "2":"比基尼派对"}', 
				'english_name' => 'Bikini Party',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'dragonDance', 
				'game_name' => '_json:{"1":"Dragon Dance", "2":"舞龙"}', 
				'english_name' => 'Dragon Dance',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'sunTide', 
				'game_name' => '_json:{"1":"Sun Tide", "2":"太阳征程​"}', 
				'english_name' => 'Sun Tide',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'supeItUp', 
				'game_name' => '_json:{"1":"Supe It Up", "2":"增强马力"}', 
				'english_name' => 'Supe It Up',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'partyIsland', 
				'game_name' => '_json:{"1":"Party Island", "2":"派对岛屿"}', 
				'english_name' => 'Party Island',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'americanRouletteGold', 
				'game_name' => '_json:{"1":"American Roulette Gold", "2":"黄金美式轮盘"}', 
				'english_name' => 'American Roulette Gold',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'asianBeauty', 
				'game_name' => '_json:{"1":"Asian Beauty", "2":"亚洲风情"}', 
				'english_name' => 'Asian Beauty',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'bridesmaids', 
				'game_name' => '_json:{"1":"Bridesmaids", "2":"伴娘我最大"}', 
				'english_name' => 'Bridesmaids',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'sterlingSilver', 
				'game_name' => '_json:{"1":"Sterling Silver", "2":"纯银"}', 
				'english_name' => 'Sterling Silver',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'rugbyStar', 
				'game_name' => '_json:{"1":"Rugby Star", "2":"橄榄球明星"}', 
				'english_name' => 'Rugby Star',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'goldenEra', 
				'game_name' => '_json:{"1":"Golden Era", "2":"黄金时代"}', 
				'english_name' => 'Golden Era',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'soManyMonsters', 
				'game_name' => '_json:{"1":"So Many Monsters", "2":"好多怪兽"}', 
				'english_name' => 'So Many Monsters',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'soMuchCandy', 
				'game_name' => '_json:{"1":"So Much Candy", "2":"好多糖果"}', 
				'english_name' => 'So Much Candy',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'soMuchSushi', 
				'game_name' => '_json:{"1":"So Much Sushi", "2":"好多寿司"}', 
				'english_name' => 'So Much Sushi',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'basketballStar', 
				'game_name' => '_json:{"1":"Basketball Star", "2":"篮球巨星"}', 
				'english_name' => 'Basketball Star',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'happyHolidays', 
				'game_name' => '_json:{"1":"Happy Holidays", "2":"快乐假日"}', 
				'english_name' => 'Happy Holidays',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'luckyKoi', 
				'game_name' => '_json:{"1":"Lucky Koi", "2":"好运金鲤"}', 
				'english_name' => 'Lucky Koi',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'wildOrient', 
				'game_name' => '_json:{"1":"Wild Orient", "2":"东方珍兽"}', 
				'english_name' => 'Wild Orient',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'barBarBlackSheep5Reel', 
				'game_name' => '_json:{"1":"Bar Bar Black Sheep", "2":"黑绵羊咩咩叫"}', 
				'english_name' => 'Bar Bar Black Sheep',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'winSumDimSum', 
				'game_name' => '_json:{"1":"Win Sum Dim Sum", "2":"开心点心"}', 
				'english_name' => 'Win Sum Dim Sum',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'cashville', 
				'game_name' => '_json:{"1":"Cashville", "2":"现金威乐"}', 
				'english_name' => 'Cashville',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'fishParty', 
				'game_name' => '_json:{"1":"Fish Party", "2":"海底派对"}', 
				'english_name' => 'Fish Party',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'kingsOfCash', 
				'game_name' => '_json:{"1":"Kings Of Cash", "2":"现金之王"}', 
				'english_name' => 'Kings Of Cash',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'summerHoliday', 
				'game_name' => '_json:{"1":"Summer Holiday", "2":"暑假"}', 
				'english_name' => 'Summer Holiday',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'prettyKitty', 
				'game_name' => '_json:{"1":"Pretty Kitty", "2":"漂亮猫咪"}', 
				'english_name' => 'Pretty Kitty',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'shoot', 
				'game_name' => '_json:{"1":"Shoot!", "2":"射门!"}', 
				'english_name' => 'Shoot!',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'reelSpinner', 
				'game_name' => '_json:{"1":"Reel Spinner", "2":"旋转大战"}', 
				'english_name' => 'Reel Spinner',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'karaokeParty', 
				'game_name' => '_json:{"1":"Karaoke Party", "2":" K歌乐韵"}', 
				'english_name' => 'Karaoke Party',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'frozenDiamonds', 
				'game_name' => '_json:{"1":"Frozen Diamonds", "2":" 急冻钻石​"}', 
				'english_name' => 'Frozen Diamonds',
			),
			array(
				'game_platform_id' => 71, 
				'game_code' => 'jungleJimElDorado', 
				'game_name' => '_json:{"1":"Jungle Jim- El Dorado", "2":"丛林吉姆黄金国​"}', 
				'english_name' => 'Jungle Jim- El Dorado',
			),
		);

		$this->db->where('game_platform_id', GSMG_API);
		$this->db->update_batch('game_description', $game_descriptions, 'game_code', 500);

		$this->db->trans_complete();
	}

	public function down() {
	}
}
