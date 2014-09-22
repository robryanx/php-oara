<?php
/**
* Acces Class
*
* @author Carlos Morillo Merino
* @category Oara_Curl
* @copyright Fubra Limited
* @version Release: 01.00
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
	 *
	 *
	 * Construct Result
	 *
	 * @var string
	 */
	private $_constructResult = null;

	/**
	 * Cookie Path
	 * @var unknown
	 */
	private $_cookiePath = null;

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
		$dir = COOKIES_BASE_DIR . DIRECTORY_SEPARATOR . $credentials ['cookiesDir'] . DIRECTORY_SEPARATOR . $credentials ['cookiesSubDir'] . '/';

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
		$this->_cookiePath = $cookies;

		$this->_options = array (
				CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:23.0) Gecko/20100101 Firefox/32.0",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FAILONERROR => true,
				CURLOPT_COOKIEJAR => $cookies,
				CURLOPT_COOKIEFILE => $cookies,
				CURLOPT_HTTPAUTH => CURLAUTH_ANY,
				CURLOPT_AUTOREFERER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_HEADER => false ,
				CURLOPT_VERBOSE => false,
				);

		// Init curl
		$ch = curl_init ();
		$options = $this->_options;
		$options [CURLOPT_URL] = $url;
		$options [CURLOPT_POST] = true;

		$options [CURLOPT_FOLLOWLOCATION] = true;

		// Login form fields
		$arg = self::getPostFields ( $valuesLogin );

		$options [CURLOPT_POSTFIELDS] = $arg;

		// problem with SMG about the redirects and headers
		if ($isTD) {
			$options [CURLOPT_HEADER] = true;
			$options [CURLOPT_FOLLOWLOCATION] = false;
		}

		curl_setopt_array ( $ch, $options );

		$result = curl_exec ( $ch );
		$err = curl_errno ( $ch );
		$errmsg = curl_error ( $ch );
		$info = curl_getinfo ( $ch );
		// Close curl session
		curl_close ( $ch );

		if ($isDianomi) {
			$result = true;
		}

		while ( ($isTD) && ($info ['http_code'] == 301 || $info ['http_code'] == 302) ) {
			// redirect manually, cookies must be set, which curl does not itself

			// extract new location
			preg_match_all ( '|Location: (.*)\n|U', $result, $results );
			$location = implode ( ';', $results [1] );
			$ch = curl_init ();

			$options = $this->_options;
			$options [CURLOPT_URL] = str_replace ( "/publisher/..", "", $location );
			$options [CURLOPT_HEADER] = true;
			$options [CURLOPT_FOLLOWLOCATION] = false;

			curl_setopt_array ( $ch, $options );

			$result = curl_exec ( $ch );
			$err = curl_errno ( $ch );
			$errmsg = curl_error ( $ch );
			$info = curl_getinfo ( $ch );

			curl_close ( $ch );
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
	 * Get the cookies
	 */
	public function getCookies() {
		return file_get_contents($this->_cookiePath);
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

		$mcurl = curl_multi_init ();
		$threadsRunning = 0;
		$urls_id = 0;
		for(;;) {
			// Fill up the slots
			while ( $threadsRunning < $this->_threads && $urls_id < count ( $urls ) ) {
				$request = $urls [$urls_id];
				$ch = curl_init ();
				$chId = ( int ) $ch;
				$curlResults [( string ) $chId] = '';
				$options = $this->_options;
				$options [CURLOPT_URL] = $request->getUrl ();
				$options [CURLOPT_POST] = true;
				$options [CURLOPT_FOLLOWLOCATION] = true;
				// Post form fields
				$arg = self::getPostFields ( $request->getParameters () );
				$options [CURLOPT_POSTFIELDS] = $arg;
				curl_setopt_array ( $ch, $options );

				curl_multi_add_handle ( $mcurl, $ch );
				$urls_id ++;
				$threadsRunning ++;
			}
			// Check if done
			if ($threadsRunning == 0 && $urls_id >= count ( $urls )) {
				break;
			}
			// Let mcurl do its thing
			curl_multi_select ( $mcurl );
			while ( ($mcRes = curl_multi_exec ( $mcurl, $mcActive )) == CURLM_CALL_MULTI_PERFORM ) {
				sleep ( 1 );
			}
			if ($mcRes != CURLM_OK) {
				throw new Exception ( 'Fail in CURL access in POST, multiexec' );
			}
			while ( $done = curl_multi_info_read ( $mcurl ) ) {
				$ch = $done ['handle'];
				$chId = ( int ) $ch;
				$done_url = curl_getinfo ( $ch, CURLINFO_EFFECTIVE_URL );
				$done_content = curl_multi_getcontent ( $ch );
				if ($done_content == false) {
					if ($deep == 5) {
						throw new Exception ( 'Fail in CURL access in POST, getcontent' );
					}
					$keyPosition = self::keyPosition ( $curlResults, $chId );
					$newUrlArray = array ();
					$newUrlArray [] = $urls [$keyPosition];
					$newDeep = $deep + 1;
					$recursion = self::post ( $newUrlArray, $return, $newDeep );
					$done_content = $recursion [0];
				}
				if (curl_errno ( $ch ) == 0) {
					if ($return == 'content') {
						$curlResults [( string ) $chId] = $done_content;
					} else if ($return == 'url') {
						$curlResults [( string ) $chId] = $done_url;
					}
				} else {
					throw new Exception ( 'Fail in CURL access in POST, getcontent' );
				}
				curl_multi_remove_handle ( $mcurl, $ch );
				curl_close ( $ch );
				$threadsRunning --;
			}
		}
		curl_multi_close ( $mcurl );
		foreach ( $curlResults as $key => $value ) {
			$results [] = $value;
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

		$mcurl = curl_multi_init ();
		$threadsRunning = 0;
		$urls_id = 0;
		for(;;) {
			// Fill up the slots
			while ( $threadsRunning < $this->_threads && $urls_id < count ( $urls ) ) {
				$request = $urls [$urls_id];
				$ch = curl_init ();
				$chId = ( int ) $ch;
				$curlResults [( string ) $chId] = '';

				$options = $this->_options;
				$options [CURLOPT_URL] = $request->getUrl () . self::getPostFields ( $request->getParameters () );
				$options [CURLOPT_RETURNTRANSFER] = true;
				$options [CURLOPT_FOLLOWLOCATION] = true;
				curl_setopt_array ( $ch, $options );

				curl_multi_add_handle ( $mcurl, $ch );
				$urls_id ++;
				$threadsRunning ++;
			}
			// Check if done
			if ($threadsRunning == 0 && $urls_id >= count ( $urls )) {
				break;
			}
			// Let mcurl do it's thing
			curl_multi_select ( $mcurl );
			while ( ($mcRes = curl_multi_exec ( $mcurl, $mcActive )) == CURLM_CALL_MULTI_PERFORM ) {
				sleep ( 1 );
			}
			if ($mcRes != CURLM_OK) {
				throw new Exception ( 'Fail in CURL access in GET, multiexec' );
			}
			while ( $done = curl_multi_info_read ( $mcurl ) ) {
				$ch = $done ['handle'];
				$chId = ( int ) $ch;
				$done_url = curl_getinfo ( $ch, CURLINFO_EFFECTIVE_URL );
				$done_content = curl_multi_getcontent ( $ch );
				if ($done_content == false) {
					if ($deep == 5) {
						throw new Exception ( 'Fail in CURL access in GET, getcontent' );
					}
					$keyPosition = self::keyPosition ( $curlResults, $chId );
					$newUrlArray = array ();
					$newUrlArray [] = $urls [$keyPosition];
					$newDeep = $deep + 1;
					$recursion = self::get ( $newUrlArray, $return, $newDeep );
					$done_content = $recursion [0];
				}
				if (curl_errno ( $ch ) == 0) {
					if ($return == 'content') {
						$curlResults [( string ) $chId] = $done_content;
					} else if ($return == 'url') {
						$curlResults [( string ) $chId] = $done_url;
					}
				} else {
					throw new Exception ( 'Fail in CURL access in GET, getcontent' );
				}
				curl_multi_remove_handle ( $mcurl, $ch );
				curl_close ( $ch );
				$threadsRunning --;
			}
		}
		curl_multi_close ( $mcurl );
		foreach ( $curlResults as $key => $value ) {
			$results [] = $value;
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

	public function getOptions(){
		return $this->_options;
	}
	public function setOptions($options){
		$this->_options = $options;
	}
}