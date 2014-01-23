<?php
/**
 * Acces Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Curl
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Curl_Access {
	/**
	 * Curl options.
	 *
	 * @var array
	 */
	private $_options = array ();
	/**
	 * If we are connected to the website.
	 *
	 * @var boolean
	 */
	private $_connected = false;
	/**
	 * Number of threads
	 *
	 * @var integer
	 */
	private $_threads = 7;
	/**
	 * Construct Result
	 *
	 * @var string
	 */
	private $_constructResult = null;
	
	/**
	 * Constructor and Login.
	 *
	 * @param $url -
	 *        	Url Login
	 * @param $valuesLogin -
	 *        	Array with the login parameters
	 * @return none
	 */
	public function __construct($url, array $valuesLogin, $credentials) {
		if (! isset ( $credentials ["cookiesDir"] )) {
			$credentials ["cookiesDir"] = "Oara";
		}
		if (! isset ( $credentials ["cookiesSubDir"] )) {
			$credentials ["cookiesSubDir"] = "Import";
		}
		if (! isset ( $credentials ["cookieName"] )) {
			$credentials ["cookieName"] = "default";
		}
		
		// Setting cookies
		$isDianomi = $credentials ['networkName'] == "Dianomi" ? true : false;
		
		$isTD = ($credentials ['networkName'] == "TradeDoubler" || $credentials ['networkName'] == "Stream20" || $credentials ['networkName'] == "Wehkamp" || $credentials ['networkName'] == "Steak");
		// $isAW = $credentials['networkName'] == "AffiliateWindow";
		$dir = realpath ( dirname ( __FILE__ ) ) . '/../data/curl/' . $credentials ['cookiesDir'] . '/' . $credentials ['cookiesSubDir'] . '/';
		
		if (! Oara_Utilities::mkdir_recursive ( $dir, 0777 )) {
			throw new Exception ( 'Problem creating folder in Access' );
		}
		// Deleting the last cookie
		
		if ($handle = opendir ( $dir )) {
			/* This is the correct way to loop over the directory. */
			while ( false !== ($file = readdir ( $handle )) ) {
				if ($credentials ['cookieName'] == strstr ( $file, '_', true )) {
					unlink ( $dir . $file );
					break;
				}
			}
			closedir ( $handle );
		}
		
		$cookieName = $credentials ["cookieName"];
		
		$cookies = $dir . $cookieName . '_cookies.txt';
		
		$this->_options = array (
				CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:23.0) Gecko/20100101 Firefox/23.0",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FAILONERROR => true,
				CURLOPT_COOKIEJAR => $cookies,
				CURLOPT_COOKIEFILE => $cookies,
				CURLOPT_HTTPAUTH => CURLAUTH_ANY,
				CURLOPT_AUTOREFERER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_HEADER => false,
				CURLOPT_FOLLOWLOCATION => false,
				//CURLOPT_VERBOSE => true 
		);
		
		// Init curl
		$options = $this->_options;
		$options [CURLOPT_URL] = $url;
		$options [CURLOPT_POST] = true;
		// Login form fields
		$arg = self::getPostFields ( $valuesLogin );
		$options [CURLOPT_POSTFIELDS] = $arg;
		$curlResult = self::curlExec ( $options );
		$result = $curlResult ["result"];
		if ($isDianomi) {
			$result = true;
		}
		$this->_constructResult = $result;
		if ($result == false) {
			throw new Exception ( "Failed to connect" );
		} else {
			$this->_connected = true;
		}
	}
	/**
	 * Get the construct result
	 */
	public function getConstructResult() {
		return $this->_constructResult;
	}
	
	/**
	 * Post request.
	 *
	 * @param $url -
	 *        	Post url request
	 * @param $valuesForm -
	 *        	Curl Parameter array
	 * @return $results
	 */
	public function post(array $urls, $return = 'content', $deep = 0) {
		$results = array ();
		$curlResults = array ();
		if (! $this->_connected) {
			throw new Exception ( "Not connected" );
		}
		
		
		foreach ($urls as $urlKey => $request){
			$options = $this->_options;
			$options [CURLOPT_URL] = $request->getUrl ();
			$options [CURLOPT_POST] = true;
			// Post form fields
			$arg = self::getPostFields ( $request->getParameters () );
			$options [CURLOPT_POSTFIELDS] = $arg;
			$curlResult = self::curlExec($options);
			if ($return == 'content') {
				$results[$urlKey] = $curlResult["result"];
			} else if ($return == 'url') {
				$curlResults [( string ) $chId] = $result ["info"]["url"];;
			}
		}
		return $results;
	}
	/**
	 * Get request.
	 *
	 * @param $url -
	 *        	Get url request
	 * @param $valuesForm -
	 *        	Curl Parameter array
	 * @return $results
	 */
	public function get(array $urls, $return = 'content', $deep = 0) {
		$results = array ();
		$curlResults = array ();
		if (! $this->_connected) {
			throw new Exception ( "Not connected" );
		}
		
		
		foreach ($urls as $urlKey => $request){
			$options = $this->_options;
			$options [CURLOPT_URL] = $request->getUrl () . self::getPostFields ( $request->getParameters () );
			$options [CURLOPT_POST] = false;
			$curlResult = self::curlExec($options);
			if ($return == 'content') {
				$results[$urlKey] = $curlResult["result"];
			} else if ($return == 'url') {
				$curlResults [( string ) $chId] = $result ["info"]["url"];;
			}
		}
		return $results;
	}
	/**
	 * Curl_Parameter to post
	 *
	 * @param array $data        	
	 * @return unknown_type
	 */
	public function getPostFields(array $data) {
		$return = array ();
		
		foreach ( $data as $parameter ) {
			$return [] = $parameter->getKey () . '=' . urlencode ( $parameter->getValue () );
		}
		
		return implode ( '&', $return );
	}
	/**
	 * Search the position for a key in a map
	 *
	 * @param array $data        	
	 * @param
	 *        	$key
	 * @return position
	 */
	private function keyPosition(array $data, $key) {
		$long = count ( $data );
		$result = null;
		$i = 0;
		$enc = false;
		while ( $i < $long && ! $enc ) {
			if (self::keyName ( $data, $i ) == $key) {
				$result = $i;
				$enc = true;
			}
			$i ++;
		}
		return $result;
	}
	/*
	 * Return the key for a position in the array. @param array $a @param $pos return key
	 */
	private function keyName(array $a, $pos) {
		$temp = array_slice ( $a, $pos, 1, true );
		return key ( $temp );
	}
	function curlExec($options, $maxredirect = null) {
		$mr = $maxredirect === null ? 10 : intval ( $maxredirect );
		if (ini_get ( 'open_basedir' ) == '' && ini_get ( 'safe_mode' ) == 'Off') {
			
			$rch = curl_init ();
			if (! empty ( $options )) {
				curl_setopt_array ( $rch, $options );
			}
			
			curl_setopt ( $rch, CURLOPT_FOLLOWLOCATION, $mr > 0 );
			curl_setopt ( $rch, CURLOPT_MAXREDIRS, $mr );
			curl_setopt ( $rch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt ( $rch, CURLOPT_SSL_VERIFYPEER, false );
		} else {
			if ($mr > 0) {
				
				$rch = curl_init ();
				if (! empty ( $options )) {
					curl_setopt_array ( $rch, $options );
				}
				curl_setopt ( $rch, CURLOPT_FOLLOWLOCATION, false );
				$original_url = curl_getinfo ( $rch, CURLINFO_EFFECTIVE_URL );
				$newurl = $original_url;
				
				curl_setopt ( $rch, CURLOPT_HEADER, true );
				curl_setopt ( $rch, CURLOPT_NOBODY, false );
				curl_setopt ( $rch, CURLOPT_FORBID_REUSE, false );
				do {
					curl_setopt ( $rch, CURLOPT_URL, $newurl );
					$resp = curl_exec ( $rch );
					list ( $header, $response ) = explode ( "\r\n\r\n", $resp, 2 );
					
					
					$result ["result"] = $response;
					$result ["err"] = curl_errno ( $rch );
					$result ["errmsg"] = curl_error ( $rch );
					$result ["info"] = curl_getinfo ( $rch );
					
					if (curl_errno ( $rch )) {
						$code = 0;
					} else {
						$code = curl_getinfo ( $rch, CURLINFO_HTTP_CODE );
						if ($code == 301 || $code == 302) {
							preg_match ( '/Location:(.*?)\n/', $header, $matches );
							$newurl = trim ( array_pop ( $matches ) );
							
							// if no scheme is present then the new url is a
							// relative path and thus needs some extra care
							if (! preg_match ( "/^https?:/i", $newurl )) {
								$newurl = $original_url . $newurl;
							}
						} else {
							$code = 0;
						}
					}
				} while ( $code && -- $mr );
				
				curl_close ( $rch );
				
				if (! $mr) {
					if ($maxredirect === null)
						trigger_error ( 'Too many redirects.', E_USER_WARNING );
					else
						$maxredirect = 0;
					
					return false;
				}
			}
		}
		return $result;
	}
}
