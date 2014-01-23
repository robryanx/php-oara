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
	 * @var array
	 */
	private $_options = array();
	/**
	 * If we are connected to the website.
	 * @var boolean
	 */
	private $_connected = false;
	/**
	 * Number of threads
	 * @var integer
	 */
	private $_threads = 7;
	/**
	 * 
	 * Construct Result
	 * @var string
	 */
	private $_constructResult = null;

	/**
	 * Constructor and Login.
	 * @param $url - Url Login
	 * @param $valuesLogin - Array with the login parameters
	 * @return none
	 */
	public function __construct($url, array $valuesLogin, $credentials) {

		if (!isset($credentials["cookiesDir"])) {
			$credentials["cookiesDir"] = "Oara";
		}
		if (!isset($credentials["cookiesSubDir"])) {
			$credentials["cookiesSubDir"] = "Import";
		}
		if (!isset($credentials["cookieName"])) {
			$credentials["cookieName"] = "default";
		}

		//Setting cookies
		$isDianomi = $credentials['networkName'] == "Dianomi" ? true : false;
		
		$isTD = ($credentials['networkName'] == "TradeDoubler" || $credentials['networkName'] == "Stream20" || $credentials['networkName'] == "Wehkamp" || $credentials['networkName'] == "Steak");
		//$isAW = $credentials['networkName'] == "AffiliateWindow";
		$dir = realpath(dirname(__FILE__)).'/../data/curl/'.$credentials['cookiesDir'].'/'.$credentials['cookiesSubDir'].'/';

		if (!Oara_Utilities::mkdir_recursive($dir, 0777)) {
			throw new Exception('Problem creating folder in Access');
		}
		//Deleting the last cookie

		if ($handle = opendir($dir)) {
			/* This is the correct way to loop over the directory. */
			while (false !== ($file = readdir($handle))) {
				if ($credentials['cookieName'] == strstr($file, '_', true)) {
					unlink($dir.$file);
					break;
				}
			}
			closedir($handle);
		}

		$cookieName = $credentials["cookieName"];

		$cookies = $dir.$cookieName.'_cookies.txt';

		$this->_options = array(
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
			//CURLOPT_VERBOSE => true,
		);
		
		//Init curl
		$options = $this->_options;
		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_POST] = true;
		// Login form fields
		$arg = self::getPostFields($valuesLogin);
		$options[CURLOPT_POSTFIELDS] = $arg;
		$curlResult = self::curlExec($options);
		$result = $curlResult["result"];
		if ($isDianomi){
			$result = true;
		}
		
		
		$this->_constructResult = $result;
		if ($result == false) {
			throw new Exception("Failed to connect");
		} else {
			$this->_connected = true;
		}

	}
	/**
	 * 
	 * Get the construct result
	 */
	public function getConstructResult(){
		return $this->_constructResult;
	}
	
	/**
	 * Post request.
	 * @param $url - Post url request
	 * @param $valuesForm - Curl Parameter array
	 * @return $results
	 */
	public function post(array $urls, $return = 'content', $deep = 0) {
		$results = array();
		$curlResults = array();
		if (!$this->_connected) {
			throw new Exception("Not connected");
		}

		$mcurl = curl_multi_init();
		$threadsRunning = 0;
		$urls_id = 0;
		for (;;) {
			// Fill up the slots
			while ($threadsRunning < $this->_threads && $urls_id < count($urls)) {
				$request = $urls[$urls_id];
				$ch = curl_init();
				$chId = (int) $ch;
				$curlResults[(string) $chId] = '';
				$options = $this->_options;
				$options[CURLOPT_URL] = $request->getUrl();
				$options[CURLOPT_POST] = true;
				$options[CURLOPT_FOLLOWLOCATION] = true;
				// Post form fields
				$arg = self::getPostFields($request->getParameters());
				$options[CURLOPT_POSTFIELDS] = $arg;
				curl_setopt_array($ch, $options);

				curl_multi_add_handle($mcurl, $ch);
				$urls_id++;
				$threadsRunning++;
			}
			// Check if done
			if ($threadsRunning == 0 && $urls_id >= count($urls)) {
				break;
			}
			// Let mcurl do its thing
			curl_multi_select($mcurl);
			while (($mcRes = curl_multi_exec($mcurl, $mcActive)) == CURLM_CALL_MULTI_PERFORM) {
				sleep(1);
			}
			if ($mcRes != CURLM_OK) {
				throw new Exception('Fail in CURL access in POST, multiexec');
			}
			while ($done = curl_multi_info_read($mcurl)) {
				$ch = $done['handle'];
				$chId = (int) $ch;
				$done_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
				$done_content = curl_multi_getcontent($ch);
				if ($done_content == false) {
					if ($deep == 5) {
						throw new Exception('Fail in CURL access in POST, getcontent');
					}
					$keyPosition = self::keyPosition($curlResults, $chId);
					$newUrlArray = array();
					$newUrlArray[] = $urls[$keyPosition];
					$newDeep = $deep + 1;
					$recursion = self::post($newUrlArray, $return, $newDeep);
					$done_content = $recursion[0];
				}
				if (curl_errno($ch) == 0) {
					if ($return == 'content') {
						$curlResults[(string) $chId] = $done_content;
					} else
						if ($return == 'url') {
							$curlResults[(string) $chId] = $done_url;
						}
				} else {
					throw new Exception('Fail in CURL access in POST, getcontent');
				}
				curl_multi_remove_handle($mcurl, $ch);
				curl_close($ch);
				$threadsRunning--;
			}
		}
		curl_multi_close($mcurl);
		foreach ($curlResults as $key => $value) {
			$results[] = $value;
		}
		return $results;
	}
	/**
	 * Get request.
	 * @param $url - Get url request
	 * @param $valuesForm - Curl Parameter array
	 * @return $results
	 */
	public function get(array $urls, $return = 'content', $deep = 0) {
		$results = array();
		$curlResults = array();
		if (!$this->_connected) {
			throw new Exception("Not connected");
		}

		$mcurl = curl_multi_init();
		$threadsRunning = 0;
		$urls_id = 0;
		for (;;) {
			// Fill up the slots
			while ($threadsRunning < $this->_threads && $urls_id < count($urls)) {
				$request = $urls[$urls_id];
				$ch = curl_init();
				$chId = (int) $ch;
				$curlResults[(string) $chId] = '';

				$options = $this->_options;
				$options[CURLOPT_URL] = $request->getUrl().self::getPostFields($request->getParameters());
				$options[CURLOPT_RETURNTRANSFER] = true;
				$options[CURLOPT_FOLLOWLOCATION] = true;
				curl_setopt_array($ch, $options);

				curl_multi_add_handle($mcurl, $ch);
				$urls_id++;
				$threadsRunning++;
			}
			// Check if done
			if ($threadsRunning == 0 && $urls_id >= count($urls)) {
				break;
			}
			// Let mcurl do it's thing
			curl_multi_select($mcurl);
			while (($mcRes = curl_multi_exec($mcurl, $mcActive)) == CURLM_CALL_MULTI_PERFORM) {
				sleep(1);
			}
			if ($mcRes != CURLM_OK) {
				throw new Exception('Fail in CURL access in GET, multiexec');
			}
			while ($done = curl_multi_info_read($mcurl)) {
				$ch = $done['handle'];
				$chId = (int) $ch;
				$done_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
				$done_content = curl_multi_getcontent($ch);
				if ($done_content == false) {
					if ($deep == 5) {
						throw new Exception('Fail in CURL access in GET, getcontent');
					}
					$keyPosition = self::keyPosition($curlResults, $chId);
					$newUrlArray = array();
					$newUrlArray[] = $urls[$keyPosition];
					$newDeep = $deep + 1;
					$recursion = self::get($newUrlArray, $return, $newDeep);
					$done_content = $recursion[0];
				}
				if (curl_errno($ch) == 0) {
					if ($return == 'content') {
						$curlResults[(string) $chId] = $done_content;
					} else
						if ($return == 'url') {
							$curlResults[(string) $chId] = $done_url;
						}
				} else {
					throw new Exception('Fail in CURL access in GET, getcontent');
				}
				curl_multi_remove_handle($mcurl, $ch);
				curl_close($ch);
				$threadsRunning--;
			}
		}
		curl_multi_close($mcurl);
		foreach ($curlResults as $key => $value) {
			$results[] = $value;
		}
		return $results;
	}
	/**
	 * Curl_Parameter to post
	 * @param array $data
	 * @return unknown_type
	 */
	public function getPostFields(array $data) {

		$return = array();

		foreach ($data as $parameter) {
			$return[] = $parameter->getKey().'='.urlencode($parameter->getValue());
			
		}

		return implode('&', $return);
	}
	/**
	 * Search the position for a key in a map
	 * @param array $data
	 * @param  $key
	 * @return position
	 */
	private function keyPosition(array $data, $key) {
		$long = count($data);
		$result = null;
		$i = 0;
		$enc = false;
		while ($i < $long && !$enc) {
			if (self::keyName($data, $i) == $key) {
				$result = $i;
				$enc = true;
			}
			$i++;
		}
		return $result;
	}
	/*
	 * Return the key for a position in the array.
	 * @param array $a
	 * @param $pos
	 * return key
	 */
	private function keyName(array $a, $pos) {
		$temp = array_slice($a, $pos, 1, true);
		return key($temp);
	}
	
	function curlExec($options, $maxredirect = null) {
		$result = array();
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		
	    $mr = $maxredirect === null ? 10 : intval($maxredirect);
	    if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
	        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
	        curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
	    } else {
	        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	        if ($mr > 0) {
	            $newurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
	
	            $rch = curl_copy_handle($ch);
	            curl_setopt($rch, CURLOPT_HEADER, true);
	            curl_setopt($rch, CURLOPT_NOBODY, true);
	            curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
	            curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
	            do {
	                curl_setopt($rch, CURLOPT_URL, $newurl);
	                $header = curl_exec($rch);
	                if (curl_errno($rch)) {
	                    $code = 0;
	                } else {
	                    $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
	                    if ($code == 301 || $code == 302) {
	                        preg_match('/Location:(.*?)\n/', $header, $matches);
	                        $newurl = trim(array_pop($matches));
	                    } else {
	                        $code = 0;
	                    }
	                }
	            } while ($code && --$mr);
	            curl_close($rch);
	            if (!$mr) {
	                if ($maxredirect === null) {
	                    trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
	                } else {
	                    $maxredirect = 0;
	                }
	                return false;
	            }
	            curl_setopt($ch, CURLOPT_URL, $newurl);
	        }
	    }
	    
	    
	    $result["result"] = curl_exec($ch);
	    $result["err"] = curl_errno($ch);
	    $result["errmsg"] = curl_error($ch);
	    $result["info"] = curl_getinfo($ch);
	    curl_close($ch);
	    
	    return $result;
	} 
}
