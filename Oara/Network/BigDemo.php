<?php
/**
 * Data Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Nectar
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_BigDemo extends Oara_Network{

	private $_affiliateNetwork = null;

	private $_merchantList = array("A Quarter Of",
		"AA UK Breakdown",
		"AbeBooks.co.uk",
		"Absolutemusic",
		"Accessorize",
		"Action Aid",
		"ActivInstinct",
		"Activity Superstore",
		"Adams",
		"Adidas UK",
		"Adobe",
		"Advanced Mp3 Players",
		"AirCon Direct",
		"AJ Electronics",
		"Alienware",
		"All About Coats",
		"AllPosters",
		"AllSaints",
		"Amazon",
		"Amazon MP3",
		"Ambrose Wilson",
		"American Express Travel Insurance",
		"Ancestry.co.uk",
		"Angels Fancy Dress",
		"Apple Store (UK)",
		"Appliance City",
		"Appliance Deals",
		"Appliances Direct",
		"Appliances Online",
		"Arena Flowers",
		"Argos",
		"Argos Entertainment",
		"Artigiano",
		"Artrepublic",
		"Astley Clarke",
		"Atelier des Chefs",
		"Attractiontix",
		"Audible",
		"Austin Reed",
		"AVG",
		"Avon UK",
		"Baker Ross",
		"Bambino Direct",
		"Banana Republic",
		"BANK Fashion",
		"Barratts",
		"Base London",
		"BBC Magazines",
		"BBC Shop",
		"BE Broadband",
		"Be Direct",
		"Be2",
		"BeanBag Bazaar",
		"Bear Grylls",
		"beautysleuth",
		"Bedworld",
		"Best Buy",
		"Best of the Best",
		"BHS",
		"BHS Menswear",
		"Big Bathroom Shop",
		"Big Red Warehouse",
		"Big Shoe Boutique",
		"Blacks",
		"Blackwell",
		"Blockbuster",
		"Blooming Direct",
		"Boden",
		"Bonmarché",
		"Bonprix",
		"BonusPrint",
		"Boohoo",
		"Born Gifted",
		"Bose",
		"Boxfresh",
		"BT Broadband",
		"BT Shop",
		"Bunches.co.uk",
		"Burton",
		"Buy Spares",
		"Buyagift",
		"Cadbury Gifts Direct",
		"Cancer Research UK",
		"CC Fashion",
		"Chain Reaction Cycles",
		"Charles Tyrwhitt ",
		"Cheap Smells",
		"Chemist Direct",
		"Chocolate Trading Company",
		"Christopher Ward",
		"Clarks",
		"Clearwater Hampers",
		"Clifford James",
		"Clogau Gold",
		"Cloggs",
		"Comet",
		"Confetti",
		"Consoles and Gadgets",
		"Corel",
		"Cosyfeet",
		"Crabtree & Evelyn",
		"Craghoppers",
		"Crave Maternity",
		"Credit Expert",
		"Crocs",
		"Crocus",
		"Crucial",
		"Cucina Direct",
		"Currys",
		"Currys Partmaster",
		"Dabs",
		"Darlings of Chelsea",
		"Daxon",
		"Dealtastic",
		"Debenhams.com Only",
		"Dell",
		"dial-a-phone",
		"Direct TVs",
		"Discount Shoe Store",
		"Discount Supplements",
		"Discount Theatre",
		"Disney Store",
		"Dixons",
		"Dorothy Perkins",
		"Dreams",
		"dress-for-less",
		"drinkstuff.com",
		"Dyson",
		"e2save Mobiles",
		"Early Learning Centre",
		"Easyart",
		"EasyRoommate",
		"ebay.co.uk",
		"eBuyer.com",
		"eHarmony",
		"Electric Shopping",
		"Electrical 123",
		"Electrical Discount UK",
		"Electrical Experience",
		"Electricshop.com",
		"Ernest Jones",
		"Ethical Superstore",
		"EuroPC",
		"Evans",
		"Evans Cycles",
		"Evocal",
		"Expedia.co.uk",
		"Express Chemist",
		"Extreme Element",
		"F Hinds",
		"Fashion World",
		"Fasthosts",
		"FeelUnique.com",
		"Figleaves Lingerie",
		"Filofax UK",
		"findmeagift.com",
		"Firebox",
		"Firetrap",
		"First Aid Warehouse",
		"Flowers Direct",
		"Flying Flowers",
		"Forever Unique",
		"Foyles",
		"Fragrance Direct",
		"Freemans",
		"French Connection",
		"Full Circle",
		"Function 18",
		"Furniture 123",
		"Gadgets.co.uk",
		"Game",
		"GamePlay",
		"Gameseek",
		"Gamestation",
		"GAP",
		"Garden Bird",
		"Gardening Direct",
		"Gear 4 Music",
		"GearZone",
		"Genes Reunited",
		"Get the Label",
		"Getting Personal",
		"GHD",
		"Gio-Goi",
		"Goldsmiths",
		"Golf Online",
		"Gorgeous Shop",
		"Graham and Green",
		"Gray and Osbourn",
		"Great Little Trading Company",
		"Great Magazines",
		"Great Plains",
		"Green People",
		"GymCompany",
		"H Samuel",
		"Halfords",
		"Hallmark",
		"Hamleys",
		"Hampergifts",
		"Hawkshead",
		"Haysom Interiors",
		"Heal's",
		"Hertz",
		"Hewlett Packard",
		"High and Mighty",
		"HMV",
		"Holdall",
		"Holland & Barrett",
		"Home and Garden Gifts",
		"Homebase",
		"Hotel Chocolat",
		"Hotter Shoes",
		"House of Fraser",
		"Hush",
		"I Want One of Those",
		"Interflora",
		"Ipanema Flip Flops",
		"iTunes",
		"iView Cameras",
		"Jacamo",
		"Jackpot Joy",
		"Jacques Vert",
		"Javari",
		"JD Sports",
		"JD Williams",
		"Jersey Plants Direct",
		"Jessops",
		"JJB Sports",
		"JML Direct",
		"Joe Browns",
		"Jokers Masquerade",
		"Jones Bootmaker",
		"Joseph Turner Shirts",
		"Joules Clothing",
		"Julipa",
		"Just Eat",
		"Just Last Season",
		"Kaleidoscope",
		"Kickers",
		"Kiddicare",
		"Kiddies Kingdom",
		"Kitbag.com",
		"Kitchen Science",
		"Kurt Geiger",
		"La Redoute",
		"La Senza",
		"Lakeland",
		"Lands' End",
		"Laptops Direct",
		"Laskys",
		"Laura Ashley",
		"Legoland",
		"Lenovo UK",
		"LetsSubscribe.com",
		"liGo Electronics",
		"Lloyds Pharmacy",
		"Logitech",
		"London Fine Foods Group",
		"London Zoo",
		"Lonely Planet",
		"Long Tall Sally",
		"Lookmantastic",
		"Love Your Shoes",
		"M and M Direct",
		"M&Co",
		"Majestic Wine",
		"Mamas and Papas",
		"Mankind",
		"Maplin",
		"Marisota",
		"Matalan",
		"Match.com",
		"Mattress Man",
		"Maxifuel",
		"Maximuscle",
		"Maxitone",
		"McAfee",
		"megabus.com",
		"Microsoft Store",
		"Millets",
		"Mills & Boon",
		"Mini Barratts",
		"Ministry of Paintball",
		"Misco",
		"Miss Selfridge",
		"Missguided.co.uk",
		"Mobiles.co.uk",
		"Moda in Pelle",
		"Monsoon",
		"Moo.com",
		"Moss Bros",
		"Motel Rocks",
		"Mothercare",
		"Musicroom",
		"My Memory",
		"myPIX.com",
		"MyTights",
		"Napster",
		"National Express",
		"National Lottery",
		"National Magazine Company",
		"National Trust",
		"Needapresent.com",
		"New Look",
		"Next",
		"Next Domestic Appliances",
		"Nike",
		"Novatech",
		"O'Neill",
		"O2 Broadband",
		"O2 Mobile",
		"Oak Furniture Land",
		"Office Shoes",
		"OneStopPhoneShop",
		"Online Golf",
		"Orange Broadband",
		"Orange Mobile",
		"Orange USB Broadband",
		"Past Times",
		"Pavers Shoes",
		"PC World",
		"Peacocks",
		"Penguin UK",
		"People Tree",
		"Pet Supermarket",
		"Petit Feet",
		"Pets at Home",
		"Phase Eight",
		"Phones4U",
		"PhotoBox",
		"PicStop",
		"Pixmania.co.uk",
		"Planet",
		"Play.com",
		"Plus.net",
		"Precis Petite",
		"Premier Man",
		"Present Aid",
		"Prezzybox",
		"Priceless Shoes",
		"Puchi Petwear",
		"Purely Gadgets",
		"QVC",
		"Rail Europe",
		"Red Candy",
		"Red Letter Days",
		"Reebok",
		"Republic",
		"Robert Dyas",
		"Rosemary Conley Online",
		"Rosetta Stone",
		"Rutland Cycling",
		"Sainsbury's Diets",
		"Sainsbury's Online",
		"Sainsbury's Online Groceries",
		"Samuel Windsor",
		"Saverstore.com",
		"Scholastic Bookclubs",
		"Scholl",
		"Schuh",
		"Scotts of Stow",
		"Scotts Online",
		"SeaLife",
		"Secret Sales",
		"Sendit.com",
		"Shirtcity.co.uk",
		"Simple Shoes",
		"Simply Be",
		"Simply Yours",
		"Size",
		"Skatehut",
		"Sky Broadband",
		"Sky Player",
		"Skype",
		"Sleeping Solutions",
		"Snapfish",
		"Sock Shop",
		"SpaFinder",
		"Sparkling Direct",
		"Spirit of Nature",
		"Sportsshoes.com",
		"St Tropez",
		"Storm",
		"Strawberry Fool",
		"StrawberryNET",
		"StressNoMore",
		"Subside Sports",
		"SuperFit",
		"Sweatband.com",
		"Swerve",
		"Symantec",
		"T Mobile",
		"T Mobile Free Sim",
		"Tech Depot",
		"Ted Baker",
		"Tenpin",
		"Teva",
		"The Beauty Room",
		"The Bespoke Gift Company",
		"The Body Shop",
		"The British Museum Shop",
		"The Carphone Warehouse",
		"The Chocolate Tasting Club",
		"The Dungeons",
		"The Hut",
		"The London Pass",
		"The Original Gift Company",
		"The Savile Row Company",
		"The Toy Shop",
		"The White Company",
		"Theatre Tickets Direct",
		"TheTrainline.com",
		"Thomas Pink",
		"Thompson & Morgan",
		"Thorntons",
		"Ticketmaster",
		"Tightsplease",
		"Timberland",
		"TM Lewin",
		"Tog 24",
		"Tombola",
		"TomTom",
		"Topman",
		"Totally Funky",
		"Toys R Us (UK)",
		"Tree2Mydoor",
		"Truffle Shuffle",
		"Trunki",
		"UK Water Features",
		"Ulster Weavers",
		"Urban Outfitters",
		"USC",
		"Vertbaudet",
		"Viking",
		"Virgin Atlantic",
		"Virgin Experience Days",
		"VistaPrint",
		"VivaLaDiva",
		"Vodafone",
		"Vonage",
		"Walktall",
		"Wallis",
		"Warehouse",
		"Watch Shop",
		"Waterstones",
		"Webtogs",
		"Wedding Plan Insurance",
		"WeightWatchers",
		"White Stuff",
		"Whittard of Chelsea",
		"Wiggle",
		"WildDay.com",
		"Windsmoor",
		"World Society for the Protection of Animals",
		"Yellow Moon",
		"Yogoego",
		"Yoodoo",
		"YorkTest",
		"Yours Clothing",
		"Zalando",
		"Zavvi",
		"zooplus.co.uk",
		"Zuneta",
		"118Golf",
		"1staudiovisual",
		"24-7 Electrical",
		"3Mobile",
		);

	private $_linkList = array("");

	private $_websiteList = array("");

	private $_pageList = array("");

	/**
	 * Constructor and Login
	 * @param $cartrawler
	 * @return Oara_Network_Demo_Export
	 */
	public function __construct($credentials)
	{

	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = true;
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getMerchantList()
	 */
	public function getMerchantList($merchantMap = array())
	{
		$merchants = Array();
		
		$merchantsNumber = count($this->_merchantList);

		for($i = 0; $i < $merchantsNumber ; $i++){
			//Getting the array Id
			$obj = Array();
			$obj['cid'] = $i;
			$obj['name'] = $this->_merchantList[$i];
			$merchants[] = $obj;
		}
		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null , Zend_Date $dStartDate = null , Zend_Date $dEndDate = null)
	{
		$totalTransactions = Array();
		$transactionNumber = 50000;
		$twoMonthsAgoDate = new Zend_Date();
		$twoMonthsAgoDate->subMonth(2);
		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		for ($i = 0; $i < $transactionNumber; $i++){
			$dateIndex = rand(0, count($dateArray)-1);
			$merchantIndex = rand(0, count($merchantList)-1);
			$transaction = array();
			$transaction['merchantId'] = $merchantList[$merchantIndex];
			$transaction['date'] = $dateArray[$dateIndex]->toString("yyyy-MM-dd HH:mm:ss");
			$transactionAmount = rand(1, 1000);
			$transaction['amount'] = $transactionAmount;
			$transaction['commission'] = $transactionAmount*(rand(1, 20)/100);
			$transactionStatusChances = rand(1, 100);
			if ($dateArray[$dateIndex]->compare($twoMonthsAgoDate) >= 0){
				if ($transactionStatusChances < 60){
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else if ($transactionStatusChances < 70){
					$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
				} else {
					$transaction['status'] = Oara_Utilities::STATUS_PENDING;
				}
			} else {
				if ($transactionStatusChances < 80){
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else {
					$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
				}
			}
			$totalTransactions[] = $transaction;
		}
		return $totalTransactions;

	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
		$totalOverviews = Array();
		$transactionArray = self::transactionMapPerDay($transactionList);
		foreach ($transactionArray as $merchantId => $dateList){

			foreach ($dateList as $date => $transactionList){
					
				$overview = Array();
				$overview['merchantId'] = $merchantId;
				$overviewDate = new Zend_Date($date, "yyyy-MM-dd");
				$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
				$clickNumber = rand(1, 3000);
				$overview['click_number'] = $clickNumber;
				$impressionNumber = rand(1, 6000);
				$overview['impression_number'] = $impressionNumber;
				$overview['transaction_number'] = 0;
				$overview['transaction_confirmed_value'] = 0;
				$overview['transaction_confirmed_commission']= 0;
				$overview['transaction_pending_value']= 0;
				$overview['transaction_pending_commission']= 0;
				$overview['transaction_declined_value']= 0;
				$overview['transaction_declined_commission']= 0;
				foreach ($transactionList as $transaction){
					$overview['transaction_number'] ++;
					if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED){
						$overview['transaction_confirmed_value'] += $transaction['amount'];
						$overview['transaction_confirmed_commission'] += $transaction['commission'];
					} else if ($transaction['status'] == Oara_Utilities::STATUS_PENDING){
						$overview['transaction_pending_value'] += $transaction['amount'];
						$overview['transaction_pending_commission'] += $transaction['commission'];
					} else if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED){
						$overview['transaction_declined_value'] += $transaction['amount'];
						$overview['transaction_declined_commission'] += $transaction['commission'];
					}
				}
				$totalOverviews[] = $overview;
			}

		}
		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		$overviewNumber = rand(1, 20);
		for ($i = 0; $i < $overviewNumber; $i++){
			$dateIndex = rand(0, count($dateArray)-1);
			$merchantIndex = rand(0, count($merchantList)-1);
			$overview = Array();
			$overview['merchantId'] = $merchantList[$merchantIndex];
			$overview['date'] = $dateArray[$dateIndex]->toString("yyyy-MM-dd HH:mm:ss");
			$clickNumber = rand(1, 3000);
			$impressionNumber = rand(1, 6000);
			$overview['impression_number'] = $impressionNumber;
			$overview['transaction_number'] = 0;
			$overview['transaction_confirmed_value'] = 0;
			$overview['transaction_confirmed_commission']= 0;
			$overview['transaction_pending_value']= 0;
			$overview['transaction_pending_commission']= 0;
			$overview['transaction_declined_value']= 0;
			$overview['transaction_declined_commission']= 0;
			$totalOverviews[] = $overview;
		}
		return $totalOverviews;
	}

	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	public function getPaymentHistory(){
		$paymentHistory = array();
		$startDate = new Zend_Date('01-01-2010', 'dd-MM-yyyy');
		$endDate = new Zend_Date();
		$dateArray = Oara_Utilities::monthsOfDifference($startDate, $endDate);
		for ($i = 0; $i < count($dateArray); $i++){
			$dateMonth = $dateArray[$i];
			$obj = array();
			$obj['date'] = $dateMonth->toString("yyyy-MM-dd HH:mm:ss");
			$value = rand(1,135000);
			$obj['value'] = $value;
			$obj['method'] = 'BACS';
			$obj['pid'] = $dateMonth->toString('yyyyMMdd');
			$paymentHistory[] = $obj;
		}
		return $paymentHistory;
	}

	/**
	 * Filter the transactionList per day
	 * @param array $transactionList
	 * @return array
	 */
	public function transactionMapPerDay(array $transactionList){
		$transactionMap = array();
		foreach ($transactionList as $transaction){
			$dateString = substr($transaction['date'], 0, 10);
			if (!isset($transactionMap[$transaction['merchantId']][$dateString])){
				$transactionMap[$transaction['merchantId']][$dateString] = array();
			}

			$transactionMap[$transaction['merchantId']][$dateString][] = $transaction;
		}
		return $transactionMap;
	}

}