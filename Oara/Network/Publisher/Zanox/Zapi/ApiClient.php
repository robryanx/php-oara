<?php

require_once 'includes/ApiConst.php';
require_once 'includes/ApiError.php';

/* Test Comment for myfix branch */

/**
 * zanox Api Client (ConnectId Version)
 *
 * Publisher api client library for accessing zanox affiliate
 * network services.
 *
 * Supported Version: PHP >= 5.1.0
 *
 * The zanox API client contains methods and routines to access all publisher
 * Web Services functionalities via a common abstract interface. This includes
 * the hash-signed SOAP and REST request authentication of client messages
 * as well as the encapsulation of all by zanox provided API methods.
 *
 * ---
 *
 * Usage Example: Restful XML API
 * <code>
 *
 *      require_once 'client/ApiClient.php';
 *
 *      $api = ApiClient::factory(PROTOCOL_XML, VERSION_DEFAULT);
 *
 *      $connectId = '__your_connect_id__';
 *      $secretKey = '__your_secrect_key__';
 *      $publicKey = '__your_public_key__';
 *
 *      $api->setConnectId($connectId);
 *      $api->setSecretKey($secretKey);
 *      $api->setPublicKey($publicKey);
 *
 *      $xml = $api->getPrograms();
 *
 * </code>
 *
 * ---
 *
 * Usage Example: Restful JSON API
 * <code>
 *
 *      require_once 'client/ApiClient.php';
 *
 *      $api = ApiClient::factory(PROTOCOL_JSON, VERSION_DEFAULT);
 *
 *      $connectId = '__your_connect_id__';
 *      $secretKey = '__your_secrect_key__';
 *      $publicKey = '__your_public_key__';
 *
 *      $api->setConnectId($connectId);
 *      $api->setSecretKey($secretKey);
 *      $api->setPublicKey($publicKey);
 *
 *      $xml = $api->searchProducts('iphone');
 *
 * </code>
 *
 * ---
 *
 * Usage Example: SOAP API
 * <code>
 *
 *      require_once 'client/ApiClient.php';
 *
 *      $api = ApiClient::factory(PROTOCOL_SOAP, VERSION_DEFAULT);
 *
 *      $connectId = '__your_connect_id__';
 *      $secretKey = '__your_secrect_key__';
 *      $publicKey = '__your_public_key__';
 *
 *      $api->setConnectId($connectId);
 *      $api->setSecretKey($secretKey);
 *      $api->setPublicKey($publicKey);
 *
 *      $xml = $api->getAdspaces();
 *
 * </code>
 *
 * ---
 *
 * Usage Example: Using API via Proxy Server
 * <code>
 *
 *      require_once 'client/ApiClient.php';
 *
 *      $api = ApiClient::factory(PROTOCOL_SOAP, VERSION_DEFAULT);
 *
 *      $api->setProxy("example.org", 8080, "login", "password");
 *
 * </code>
 *
 * ---
 *
 * Usage Example: Using API via HTTPS
 * <code>
 *
 *      require_once 'client/ApiClient.php';
 *
 *      $api = ApiClient::factory(PROTOCOL_SOAP, VERSION_DEFAULT);
 *
 *      $api->setHttpProtocol(HTTPS);
 *
 * </code>
 *
 * ---
 *
 * Usage Example: Using the Build-In Xml/Json Parser
 * <code>
 *
 *      require_once 'client/ApiClient.php';
 *
 *      $api = ApiClient::factory(PROTOCOL_JSON, VERSION_DEFAULT);
 *
 *      $xml = $api->getProduct('31f3bf210db1883e6bc3f7ab5dd096c7');
 *
 *      $array = $api->unserialize($xml);
 *
 * </code>
 *
 * ---
 *
 * @author      Thomas Nicolai (thomas.nicolai@sociomantic.com)
 * @author      Lars Kirchhoff (lars.kirchhoff@sociomantic.com)
 *
 * @see         http://wiki.zanox.com/en/Web_Services
 * @see         http://apps.zanox.com
 *
 * @category    Web Services
 * @package     ApiClient
 * @version     2009-09-01
 * @copyright   Copyright 2007-2009 zanox.de AG
 *
 * @uses        PEAR Crypt_HMAC (required for PHP < 5.1.2)
 */


/**
 * Base Directory Path
 */
define("DIR", dirname(__FILE__));


/**
 * ApiClient
 */
final class ApiClient
{

    /**
     * Protocol instances.
     *
     * @static
     * @var     static instances of ApiClient
     * @access  private
     */
    private static $instance = array();



    /**
     * Make the constructor private to prevent the class being instantiated
     * directly.
     *
     * @return void
     * @access privat
     */
    private function __construct() { }



    /**
     * Factory function returns a static instance of the ApiClient.
     *
     * You can choose between three different api protocols. JSON, XML and
     * SOAP are supported by the zanox api. If no version is given the latest
     * version is always used.
     *
     * ---
     *
     * Usage example: creating api instance
     * <code>
     *      // use soap api interface and the latest version
     *      $api = ApiClient::factory(PROTOCOL_SOAP, VERSION_DEFAULT);
     *
     *      // use xml api interface and the latest vesion
     *      $api = ApiClient::factory(PROTOCOL_XML, VERSION_DEFAULT);
     *
     *      // use json api interface and latest version
     *      $api = ApiClient::factory(PROTOCOL_JSON);
     * </code>
     *
     * ---
     *
     * @param   string      $protocol       api protocol type (XML,JSON or SOAP)
     * @param   string      $version        api version is optional
     *
     * @return  mixed                       object on successful instantiation
     *                                      or false
     *
     * @static
     * @access  public
     */
    public static function factory ( $protocol = PROTOCOL_DEFAULT, $version = VERSION_DEFAULT )
    {
        $protocol = strtolower($protocol);

        if ( empty(self::$instance[$version][$protocol]) )
        {
            $class = self::getInterface($version, $protocol);

            if ( $class )
            {
                self::$instance[$version][$protocol] = new $class($protocol, $version);
            }
            else
            {
                throw new ApiClientException(CLI_ERROR_PROTOCOL_VERSION);
            }
        }

        return self::$instance[$version][$protocol];
    }



    /**
     * Automatically includes the required ApiClient protocol class.
     *
     * @param   string      $version        api version
     * @param   string      $protocol       api protocol
     *
     * @return  mixed                       class name or false
     *
     * @access  private
     */
    private static function getInterface ( $version, $protocol )
    {
        $path = DIR . '/version/' . $version . '/';

        if ( is_dir($path) )
        {
            if ( $protocol == PROTOCOL_SOAP )
            {
                $class = SOAP_INTERFACE;
                $classfile = $path . $class . '.php';
            }
            else if ( $protocol == PROTOCOL_XML || $protocol == PROTOCOL_JSON )
            {
                $class = RESTFUL_INTERFACE;
                $classfile = $path . $class . '.php';
            }
            else
            {
                throw new ApiClientException(CLI_ERROR_PROTOCOL);
            }

            if ( is_file($classfile) )
            {
                require_once $classfile;

                if ( class_exists($class) )
                {
                  return $class;
                }
                else
                {
                    throw new ApiClientException(CLI_ERROR_PROTOCOL_CLASS);
                }
            }
            else
            {
                throw new ApiClientException(CLI_ERROR_PROTOCOL_CLASSFILE);
            }
        }
        else
        {
            throw new ApiClientException(CLI_ERROR_VERSION);
        }
    }
}

?>