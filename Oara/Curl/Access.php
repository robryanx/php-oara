<?php
namespace Oara\Curl;
    /**
     * The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
     * of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
     *
     * Copyright (C) 2016  Fubra Limited
     * This program is free software: you can redistribute it and/or modify
     * it under the terms of the GNU Affero General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or any later version.
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU Affero General Public License for more details.
     * You should have received a copy of the GNU Affero General Public License
     * along with this program.  If not, see <http://www.gnu.org/licenses/>.
     *
     * Contact
     * ------------
     * Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
     **/

/**
 * Acces Class
 *
 * @author Carlos Morillo Merino
 * @category Oara_Curl
 * @copyright Fubra Limited
 * @version Release: 01.00
 *
 */
class Access
{
    /**
     * Curl options.
     *
     * @var array
     */
    private $_options = array();
    /**
     * Number of threads
     *
     * @var integer
     */
    private $_threads = 7;

    /**
     * Cookie Path
     * @var unknown
     */
    private $_cookiePath = null;

    /**
     * \Oara\Curl\Access constructor.
     * @param $url
     * @param array $valuesLogin
     * @param $credentials
     */
    public function __construct($credentials)
    {
        $this->createCookieDir($credentials);
        //Default Options Values;
        $this->_options = array(
            CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:44.0) Gecko/20100101 Firefox/44.0",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_COOKIESESSION => false,
            CURLOPT_COOKIEJAR => $this->_cookiePath,
            CURLOPT_COOKIEFILE => $this->_cookiePath,
            CURLOPT_HTTPAUTH => CURLAUTH_ANY,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER => false,
            CURLOPT_VERBOSE => false,
            CURLOPT_FOLLOWLOCATION => true,
        );
    }
    /**
     * Creating the cookie directory
     * @param $credentials
     * @throws \Exception
     */
    public function createCookieDir($credentials)
    {
        if (!isset ($credentials ["cookiesDir"])) {
            $credentials ["cookiesDir"] = "Oara";
        }
        if (!isset ($credentials ["cookiesSubDir"])) {
            $credentials ["cookiesSubDir"] = "Import";
        }
        if (!isset ($credentials ["cookieName"])) {
            $credentials ["cookieName"] = "default";
        }

        $dir = COOKIES_BASE_DIR . DIRECTORY_SEPARATOR . $credentials ['cookiesDir'] . DIRECTORY_SEPARATOR . $credentials ['cookiesSubDir'] . DIRECTORY_SEPARATOR;

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new \Exception ('Problem creating folder in Access');
            }
        }
        // Deleting the last cookie
        if ($handle = \opendir($dir)) {
            /* This is the correct way to loop over the directory. */
            while (false !== ($file = \readdir($handle))) {
                if ($credentials ['cookieName'] == \strstr($file, '_', true)) {
                    \unlink($dir . $file);
                    break;
                }
            }
            \closedir($handle);
        }
        $cookieName = $credentials ["cookieName"];
        $cookies = $dir . $cookieName . '_cookies.txt';
        $this->_cookiePath = $cookies;
    }

    /**
     * Post
     * @param array $urls
     * @param int $deep
     * @return array
     * @throws \Exception
     */
    public function post(array $urls, $deep = 0, $allowEmptyResult = false)
    {
        $results = array();
        $curlResults = array();
        $mcurl = \curl_multi_init();
        $threadsRunning = 0;
        $urls_id = 0;
        for (; ;) {
            // Fill up the slots
            while ($threadsRunning < $this->_threads && $urls_id < count($urls)) {
                $request = $urls [$urls_id];
                $ch = \curl_init();
                $chId = ( int )$ch;
                $curlResults [( string )$chId] = '';
                $options = $this->_options;
                $options [CURLOPT_URL] = $request->getUrl();
                $options [CURLOPT_POST] = true;
                // Post form fields
                $arg = self::getPostFields($request->getParameters());
                $options [CURLOPT_POSTFIELDS] = $arg;
                \curl_setopt_array($ch, $options);
                \curl_multi_add_handle($mcurl, $ch);
                $urls_id++;
                $threadsRunning++;
            }
            // Check if done
            if ($threadsRunning == 0 && $urls_id >= \count($urls)) {
                break;
            }
            // Let mcurl do its thing
            \curl_multi_select($mcurl);
            while (($mcRes = \curl_multi_exec($mcurl, $mcActive)) == CURLM_CALL_MULTI_PERFORM) {
                \sleep(1);
            }
            if ($mcRes != CURLM_OK) {
                throw new \Exception ('Fail in CURL access in POST, multiexec');
            }
            while ($done = \curl_multi_info_read($mcurl)) {
                $ch = $done ['handle'];
                $chId = ( int )$ch;
                $done_content = \curl_multi_getcontent($ch);
                if (!$allowEmptyResult && $done_content == false) {
                    if ($deep == 5) {
                        throw new \Exception ('Fail in CURL access in POST, getcontent');
                    }
                    $keyPosition = self::keyPosition($curlResults, $chId);
                    $newUrlArray = array();
                    $newUrlArray [] = $urls [$keyPosition];
                    $newDeep = $deep + 1;
                    $recursion = self::post($newUrlArray, $newDeep);
                    $done_content = $recursion [0];
                }
                if (\curl_errno($ch) == 0) {
                    $curlResults [( string )$chId] = $done_content;
                } else {
                    throw new \Exception ('Fail in CURL access in POST, getcontent');
                }
                \curl_multi_remove_handle($mcurl, $ch);
                \curl_close($ch);
                $threadsRunning--;
            }
        }
        \curl_multi_close($mcurl);
        foreach ($curlResults as $key => $value) {
            $results [] = $value;
        }
        return $results;
    }

    /**
     * @param array $data
     * @return string
     */
    public function getPostFields(array $data)
    {
        $return = array();
        foreach ($data as $parameter) {
            $return [] = $parameter->getKey() . '=' . \urlencode($parameter->getValue());
        }
        return implode('&', $return);
    }

    /**
     * @param array $data
     * @param $key
     * @return int|null
     */
    private function keyPosition(array $data, $key)
    {
        $long = \count($data);
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

    /**
     * @param array $a
     * @param $pos
     * @return mixed
     */
    private function keyName(array $a, $pos)
    {
        $temp = \array_slice($a, $pos, 1, true);
        return \key($temp);
    }

    /**
     * Get
     * @param array $urls
     * @param int $deep
     * @return array
     * @throws Exception
     * @throws \Exception
     */
    public function get(array $urls, $deep = 0, $allowEmptyResult = false)
    {
        $results = array();
        $curlResults = array();
        $mcurl = \curl_multi_init();
        $threadsRunning = 0;
        $urls_id = 0;
        for (; ;) {
            // Fill up the slots
            while ($threadsRunning < $this->_threads && $urls_id < \count($urls)) {
                $request = $urls [$urls_id];
                $ch = \curl_init();
                $chId = ( int )$ch;
                $curlResults [( string )$chId] = '';
                $options = $this->_options;
                $options [CURLOPT_URL] = $request->getUrl() . self::getPostFields($request->getParameters());
                \curl_setopt_array($ch, $options);
                \curl_multi_add_handle($mcurl, $ch);
                $urls_id++;
                $threadsRunning++;
            }
            // Check if done
            if ($threadsRunning == 0 && $urls_id >= \count($urls)) {
                break;
            }
            // Let mcurl do it's thing
            \curl_multi_select($mcurl);
            while (($mcRes = \curl_multi_exec($mcurl, $mcActive)) == CURLM_CALL_MULTI_PERFORM) {
                \sleep(1);
            }
            if ($mcRes != CURLM_OK) {
                throw new \Exception ('Fail in CURL access in GET, multiexec');
            }
            while ($done = \curl_multi_info_read($mcurl)) {
                $ch = $done ['handle'];
                $chId = ( int )$ch;
                $done_content = \curl_multi_getcontent($ch);
                if (!$allowEmptyResult && $done_content == false) {
                    if ($deep == 5) {
                        throw new \Exception ('Fail in CURL access in GET, getcontent');
                    }
                    $keyPosition = self::keyPosition($curlResults, $chId);
                    $newUrlArray = array();
                    $newUrlArray [] = $urls [$keyPosition];
                    $newDeep = $deep + 1;
                    $recursion = self::get($newUrlArray, $newDeep);
                    $done_content = $recursion [0];
                }
                if (\curl_errno($ch) == 0) {
                    $curlResults [( string )$chId] = $done_content;
                } else {
                    throw new \Exception ('Fail in CURL access in GET, getcontent');
                }
                \curl_multi_remove_handle($mcurl, $ch);
                \curl_close($ch);
                $threadsRunning--;
            }
        }
        \curl_multi_close($mcurl);
        foreach ($curlResults as $key => $value) {
            $results [] = $value;
        }
        return $results;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * @param $options
     */
    public function setOptions($options)
    {
        $this->_options = $options;
    }

    /**
     * @return array
     */
    public function getCookiePath()
    {
        return $this->_cookiePath;
    }

    /**
     * @param $cookiePath
     */
    public function setCookiePath($cookiePath)
    {
        $this->_cookiePath = $cookiePath;
    }


    /**
     * @return string
     */
    public function getCookies()
    {
        return @\file_get_contents($this->_cookiePath);
    }

    /**
     * @return string
     */
    public function setCookies($data)
    {
        return @\file_put_contents($this->_cookiePath, $data);
    }
}