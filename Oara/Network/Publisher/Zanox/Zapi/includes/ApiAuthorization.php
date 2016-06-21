<?php

require_once DIR . '/includes/model/IAuthorization.php';

/**
 * Api Authorization
 *
 * Supported Version: PHP >= 5.0
 *
 * @author      Thomas Nicolai (thomas.nicolai@sociomantic.com)
 * @author      Lars Kirchhoff (lars.kirchhoff@sociomantic.com)
 *
 * @see         http://wiki.zanox.com/en/Web_Services
 * @see         http://apps.zanox.com
 *
 * @package     ApiClient
 * @version     2009-09-01
 * @copyright   Copyright � 2007-2009 zanox.de AG
 *
 * @uses        PEAR Crypt_HMAC (required PHP < 5.1.2)
 */
class ApiAuthorization implements IAuthorization
{

    /**
     * zanox connect ID
     *
     * @var     string      $connectId          zanox connect id
     *
     * @access  private
     */
    private $connectId = '';


    /**
     * zanox shared secret key
     *
     * @var     string      $secrectKey         secret key to sign messages
     * @access  private
     */
    private $secretKey = '';


    /**
     * zanox public key
     *
     * @var     string      $applicationId      application id for oauth
     * @access  private
     */
    private $publicKey = '';


    /**
     * Timestamp of the message
     *
     * @var     string      $timestamp          timestamp to sign the message
     * @access  private
     */
    private $timestamp = false;


    /**
     * api version
     *
     * @var     string      $version            api version
     * @access  private
     */
    private $version = false;


    /**
     * message security
     *
     * @var     boolean
     * @access  private
     */
    private $msg_security = false;



    /**
     * Contructor
     *
     * @access     public
     *
     * @return     void
     */
    function __construct() {}



    /**
     * Set connectId
     *
     * @param      string      $timestamp      time stamp
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function setTimestamp( $timestamp )
    {
        $this->timestamp = $timestamp;
    }



   /**
     * Returns the current REST timestamp.
     *
     * If there hasn't already been set a datetime we create one automatically.
     * As a format the HTTP Header protocol RFC format is taken.
     *
     * @see        see HTTP RFC for the datetime format
     *
     * @access     public
     * @final
     *
     * @return     string                      message timestamp
     */
    final public function getTimestamp()
    {
        return $this->timestamp;
    }



    /**
     * Set the connectId
     *
     * @param      string      $connectId      zanox connectId
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function setConnectId( $connectId )
    {
        $this->connectId = $connectId;
    }



    /**
     * Returns the connectId
     *
     * @access     public
     * @final
     *
     * @return     string                      zanox connect id
     */
    final public function getConnectId()
    {
        return $this->connectId;
    }



    /**
     * Sets the public key.
     *
     * @param      string      $publicKey      public key
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function setPublicKey( $publicKey )
    {
        $this->publicKey = $publicKey;
    }



    /**
     * Returns the public key
     *
     * @access     public
     * @final
     *
     * @return     string                      zanox public key
     */
    final public function getPublicKey()
    {
        return $this->publicKey;
    }



    /**
     * Set SecretKey
     *
     * @param      string      $secretKey      zanox secret key
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function setSecretKey( $secretKey )
    {
        $this->secretKey = $secretKey;
    }



    /**
     * Sets the API version to use.
     *
     * @param      string      $version        API version
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function setVersion( $version )
    {
        $this->version = $version;
    }



    /**
     * Enables the API authentication.
     *
     * Authentication is only required and therefore enabled for some privacy
     * related methods like accessing your profile or reports.
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function setSecureApiCall( $status = false )
    {
        $this->msg_security = $status;
    }



    /**
     * Returns message security status.
     *
     * Method returns true if message needs to signed with crypted hmac
     * string and nonce. Otherwise false is returned.
     *
     * @access     public
     * @final
     *
     * @return     bool                        true if secure message
     */
    final public function isSecureApiCall()
    {
        return $this->msg_security;
    }



    /**
     * Returns the crypted hash signature for the message.
     *
     * Builds the signed string consisting of the rest action verb, the uri used
     * and the timestamp of the message. Be aware of the 15 minutes timeframe
     * when setting the time manually.
     *
     * @param      string      $service        service name or restful action
     * @param      string      $method         method or uri
     * @param      string      $nonce          nonce of request
     *
     * @access     public
     * @final
     *
     * @return     string                      encoded string
     */
    final public function getSignature( $service, $method, $nonce )
    {
        $sign = $service . strtolower($method) . $this->timestamp;

        if ( !empty($nonce) )
        {
            $sign .= $nonce;
        }

        $hmac = $this->hmac($sign);

        if ( $hmac )
        {
            return $hmac;
        }

        return false;
    }



    /**
     * Returns hash based nonce.
     *
     * @see    http://en.wikipedia.org/wiki/Cryptographic_nonce
     *
     * @access     public
     * @final
     *
     * @return     string                           md5 hash-based nonce
     */
    final public function getNonce ()
    {
        $mt   = microtime();
        $rand = mt_rand();

        return md5($mt . $rand);
    }



    /**
     * Encodes the given message parameters with Base64.
     *
     * @param      string          $str            string to encode
     *
     * @access     private
     *
     * @return                                     encoded string
     */
    private function encodeBase64( $str )
    {
        $encode = '';

        for ($i=0; $i < strlen($str); $i+=2){
            $encode .= chr(hexdec(substr($str, $i, 2)));
        }

        return base64_encode($encode);
    }



    /**
     * Creates secured HMAC signature of the message parameters.
     *
     * Uses the hash_hmac function if available (PHP needs to be >= 5.1.2).
     * Otherwise it uses the PEAR/CRYP_HMAC library to sign and crypt the
     * message. Make sure you have at least one of the options working on your
     * system.
     *
     * @param      string      $message            message to sign
     *
     * @access     private
     *
     * @return     string                          signed sha1 message hash
     */
    private function hmac( $mesgparams )
    {
        if ( function_exists('hash_hmac') )
        {
            $hmac = hash_hmac('sha1', utf8_encode($mesgparams), $this->secretKey);
            $hmac = $this->encodeBase64($hmac);
        }
        else
        {
            require_once 'Crypt/HMAC.php';

            $hashobj = new Crypt_HMAC($this->secretKey, "sha1");
            $hmac = $this->encodeBase64($hashobj->hash(utf8_encode($mesgparams)));
        }

        return $hmac;
    }


}

?>