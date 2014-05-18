<?php
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_NetAfiliation
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_NetAffiliation extends Oara_Network {
	/**
	 * Server Number
	 * @var array
	 */
	private $_serverNumber = null;
	/**
	 * Export Credentials
	 * @var array
	 */
	private $_credentials = null;

	/**
	 * Client
	 * @var Oara_Curl_Access
	 */
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return null
	 */
	public function __construct($credentials) {

		$this->_credentials = $credentials;

		$user = $credentials['user'];
		$password = $credentials['password'];
		$loginUrl = "https://www2.netaffiliation.com/login";

		$valuesLogin = array(new Oara_Curl_Parameter('login[from]', 'Accueil/index'),
		new Oara_Curl_Parameter('login[email]', $user),
		new Oara_Curl_Parameter('login[mdp]', $password)
		);



		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$cookieLocalion = realpath(dirname(__FILE__)).'/../../data/curl/'.$credentials['cookiesDir'].'/'.$credentials['cookiesSubDir'].'/'.$credentials["cookieName"].'_cookies.txt';

		$cookieContent = file_get_contents($cookieLocalion);
		$serverNumber = null;
		if (preg_match("/www(.)\.netaffiliation\.com/", $cookieContent, $matches)) {
			$this->_serverNumber = $matches[1];
		}

		$urls = array();
		$valuesFormExport = array();
		$urls[] = new Oara_Curl_Request('http://www'.$this->_serverNumber.'.netaffiliation.com/affiliate/webservice', $valuesFormExport);
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('.margeHaut5');
		foreach ($results as $result){
			$this->_credentials["apiPassword"] = $result->nodeValue;
		}
		if (!isset($this->_credentials["apiPassword"])){
			$valuesFormExport = array();
			$urls[] = new Oara_Curl_Request('http://www'.$this->_serverNumber.'.netaffiliation.com/affiliate/webservice?d=1', $valuesFormExport);
			$exportReport = $this->_client->get($urls);
		}
		$urls = array();
		$valuesFormExport = array();
		$urls[] = new Oara_Curl_Request('http://www'.$this->_serverNumber.'.netaffiliation.com/affiliate/webservice', $valuesFormExport);
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('.margeHaut5');
		foreach ($results as $result){
			$this->_credentials["apiPassword"] = $result->nodeValue;
		}


	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;
		//Checking connection to the platform
		$valuesFormExport = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://www'.$this->_serverNumber.'.netaffiliation.com/index.php/', $valuesFormExport);
		$exportReport = $this->_client->get($urls);
		if (!preg_match("/logout/", $exportReport[0], $matches) || !isset($this->_credentials["apiPassword"])) {
			$connection = false;
		}
		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array();

		$valuesFormExport = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://www'.$this->_serverNumber.'.netaffiliation.com/index.php/affiliate/statistics', $valuesFormExport);

		$exportReport = $this->_client->post($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);

		$results = $dom->query('#statistiquesGenerales_liste_programme optgroup');
		foreach ($results as $result){
			$merchantLines = $result->childNodes;
			for ($i = 0; $i < $merchantLines->length; $i++) {
				$cid = $merchantLines->item($i)->attributes->getNamedItem("value")->nodeValue;
				$cid = str_replace("p", "", $cid);
				$obj = array();
				$name = $merchantLines->item($i)->nodeValue;
				$obj = array();
				$obj['cid'] = $cid;
				$obj['name'] = $name;
				$merchants[] = $obj;
			}
		}

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();

		$valuesFormExport = array();
		$valuesFormExport[] = new Oara_Curl_Parameter('authl', $this->_credentials["user"]);
		$valuesFormExport[] = new Oara_Curl_Parameter('authv', $this->_credentials["apiPassword"]);
		$valuesFormExport[] = new Oara_Curl_Parameter('champs', 'idprogramme,date,etat,argann,montant,taux,monnaie');

		$valuesFormExport[] = new Oara_Curl_Parameter('debut', $dStartDate->toString("yyyy-MM-dd"));
		$valuesFormExport[] = new Oara_Curl_Parameter('fin', $dEndDate->toString("yyyy-MM-dd"));
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://stat.netaffiliation.com/requete.php?', $valuesFormExport);
		$exportReport = $this->_client->get($urls);


		//sales
		$exportData = str_getcsv($exportReport[0], "\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ";");
			if (in_array($transactionExportArray[0], $merchantList)) {
				$transaction = Array();
				$transaction['merchantId'] = $transactionExportArray[0];
				$transactionDate = new Zend_Date($transactionExportArray[1], "dd/MM/yyyy HH:mm:ss");
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

				if ($transactionExportArray[3] != null) {
					$transaction['custom_id'] = $transactionExportArray[3];
				}

				if (strstr($transactionExportArray[2], 'v')) {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else
				if (strstr($transactionExportArray[2], 'r')) {
					$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
				} else if (strstr($transactionExportArray[2], 'a')) {
					$transaction['status'] = Oara_Utilities::STATUS_PENDING;
				} else {
					throw new Exception ("Status not found");
				}
				$transaction['amount'] = $transactionExportArray[4];
				$transaction['commission'] = round(($transactionExportArray[4] * $transactionExportArray[5])/100,2);
				$totalTransactions[] = $transaction;
			}
		}
		return $totalTransactions;
	}

}
