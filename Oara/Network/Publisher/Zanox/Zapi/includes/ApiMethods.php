<?php

require_once DIR . '/includes/ApiAuthorization.php';
require_once DIR . '/includes/Parser.php';
require_once DIR . '/includes/ApiConst.php';
require_once DIR . '/includes/ApiError.php';

/**
 * Abstract ApiMethods Class
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
 * @copyright   Copyright (c) 2007-2011 zanox.de AG
 *
 * @uses        PEAR HTTP_Request
 * @uses        PEAR XML_Serializer
 */
abstract class ApiMethods
{

    /**
     * xml & json parser
     *
     * @var     object     $parser              parser instance
     *
     * @access  public
     */
    private $parser;


    /**
     * api version
     *
     * @var     string      $version            api version
     *
     * @access  private
     */
    private $version;


    /**
     * api protocol type
     *
     * @var     string      $protocol           api protocol type
     *                                          (XML, JSON or SOAP)
     *
     * @access  private
     */
    private $protocol;


    /**
     * ApiAuthorization instance
     *
     * @var     object     $auth               authorization class instance
     *
     * @access  private
     */
    private $auth;


    /**
     * Restful http verb
     *
     * @var     string      $connectId         http verb (GET/PUT/DELETE etc.)
     *
     * @access  private
     */
    private $httpVerb;


    /**
     * Restful http protocol type
     *
     * @var     string      $httpProtocol      http protocol type (HTTP/HTTPS)
     *
     * @access  private
     */
    private $httpProtocol;


    /**
     * Restful http compression
     *
     * @var     boolean    $httpCompression    http compression turned on/off
     *
     * @access  private
     */
    private $httpCompression;


    /**
     * Proxy host
     *
     * @var     string      $proxyHost         proxy host
     *
     * @access  private
     */
    private $proxyHost;


    /**
     * Proxy port
     *
     * @var     string      $proxyPort         proxy port
     *
     * @access  private
     */
    private $proxyPort;


    /**
     * Proxy login
     *
     * @var     string      $proxyLogin        proxy login
     *
     * @access  private
     */
    private $proxyLogin;


    /**
     * Proxy password
     *
     * @var     string      $proxyLogin        proxy password
     *
     * @access  private
     */
    private $proxyPassword;



    /**
     * Constructor: Sets the api version and protocol
     *
     * @param      string      $protocol       api protocol type
     * @param      string      $version        api version
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function __construct ( $protocol, $version  )
    {
        $this->setProtocol($protocol);
        $this->setVersion($version);

        $this->auth   = new ApiAuthorization();
        $this->parser = new Parser();
    }



    /**
     * Sets the api version and protocol
     *
     * @access     public
     * @final
     *
     * @return     string                      api version
     */
    final public function getVersion ()
    {
        return $this->version;
    }



    /**
     * Sets the api version
     *
     * @param      string      $version        api version
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function setVersion ( $version )
    {
        $this->version = $version;
    }



    /**
     * Returns api protocol type
     *
     * @access     public
     * @final
     *
     * @return     string                      api protocol type
     */
    final public function getProtocol ()
    {
        return $this->version;
    }



    /**
     * Set api protocol type
     *
     * @param      string      $protocol       api protocol type
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function setProtocol ( $protocol )
    {
        $this->protocol = $protocol;
    }



    /**
     * Set http protocol type
     *
     * @param      string      $protocol       http protocol type (HTTP or HTTPS)
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function setHttpProtocol ( $httpProtocol )
    {
        $this->httpProtocol = $httpProtocol;
    }


    /**
     * Set http protocol type
     *
     * @param      string      $protocol       http protocol type (HTTP or HTTPS)
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function enableCompression ()
    {
        $this->httpCompression = true;
    }


    /**
     * Set http protocol type
     *
     * @param      string      $host           proxy host name
     * @param      int         $port           proxy port
     * @param      string      $login          proxy login
     * @param      string      $password       proxy password
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function setProxy ( $host, $port, $login, $password )
    {
        $this->proxyHost     = $host;
        $this->proxyPort     = $port;
        $this->proxyLogin    = $login;
        $this->proxyPassword = $password;
    }



    /**
     * Set connectId
     *
     * @param      string      $connectId      zanox connectId
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function setConnectId ( $connectId )
    {
        $this->auth->setConnectId($connectId);
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
    final public function setSecretKey ( $secretKey )
    {
        $this->auth->setSecretKey($secretKey);
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
    final public function setPublicKey ( $publicKey )
    {
        $this->auth->setPublicKey($publicKey);
    }



    /**
     * Sets the HTTP RESTful action verb.
     *
     * The given action might be GET, POST, PUT or DELETE. Be aware
     * that no any action can be performed on any resource.
     *
     * @param      string      $action         http action verb
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function setRestfulAction ( $verb )
    {
        $this->httpVerb = $verb;
    }



    /**
     * Enables the API authentication.
     *
     * Authentication is only required and therefore enabled for some privacy related
     * functions like accessing your profile or reports.
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function setSecureApiCall ( $status = false )
    {
        $this->auth->setSecureApiCall($status);
    }



    /**
     * Serializes item.
     *
     * Transforms array into json or xml string while using the parser class.
     * This is neccessary in order to update or create new items.
     *
     * @param      string      $name           name of root node
     * @param      array       $item           array of item elements
     * @param      string      $lang           root node attribute
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function serialize ( $rootName, $itemArray, $attr = array() )
    {
        $attr['xmlns'] = 'http://api.zanox.com/namespace/' . $this->version . '/';

        if ( $this->protocol == PROTOCOL_JSON)
        {
            $body = $this->parser->serializeJson($rootName, $itemArray, $attr);
        }
        else
        {
            $body = $this->parser->serializeXml($rootName, $itemArray, $attr);
        }

        return $body;
    }



    /**
     * Unserializes item.
     *
     * Transforms json or xml string into array while using the parser class.
     *
     * @param      string      $string           xml or json string
     *
     * @access     public
     * @final
     *
     * @return     void
     */
    final public function unserialize ( $string )
    {
        if ( $this->protocol == PROTOCOL_JSON)
        {
            $body = $this->parser->unserializeJson($string);
        }
        else
        {
            $body = $this->parser->unserializeXml($string);
        }

        return $body;
    }



    /**
     * Performs SOAP request.
     *
     * @param      string      $method     soap service
     * @param      string      $method     soap method
     * @param      array       $params     soap method parameter
     *
     * @access     public
     *
     * @return     object                  soap result object or false on error
     */
    public function doSoapRequest ( $service, $method, $params = array() )
    {
        $options['trace']    = true;
        $options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;

        if ( $this->proxyHost )
        {
            $options['proxy_host']     = $this->proxyHost;
            $options['proxy_port']     = $this->proxyPort;
            $options['proxy_login']    = $this->proxyLogin;
            $options['proxy_password'] = $this->proxyPassword;
        }

        if ( $this->httpCompression )
        {
            $options['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP;
        }

        $soap = new SoapClient($this->getWsdlUrl($service), $options);

        $params['connectId'] = $this->auth->getConnectId();

        if ( $service == SERVICE_CONNECT )
        {
            $params['publicKey'] = $this->auth->getPublicKey();
        }

        if ( $this->auth->isSecureApiCall() )
        {
            $time = gmdate('Y-m-d\TH:i:s') . ".000Z";

            $this->auth->setTimeStamp($time);
            $nonce = $this->auth->getNonce();

            $params['timestamp'] = $time;
            $params['nonce']     = $nonce;
            $params['signature'] = $this->auth->getSignature($service,
                                       $method, $nonce);
        }

        try
        {
            $result = $soap->__soapCall($method, array($params));

            if ( empty($result->code) || $result->code == 200 )
            {
                return $result;
            }
        }
        catch ( SoapFault $stacktrace )
        {
            $stacktrace   = "\n\n[STACKTRACE]\n" . $stacktrace;
            $soapRequest  = "\n\n[REQUEST]\n"    . $soap->__getLastRequest();
            $soapResponse = "\n\n[RESPONSE]\n"   . $soap->__getLastResponse();

            throw new ApiClientException($stacktrace . $soapRequest . $soapResponse);
        }

        return false;
    }



    /**
     * Performs REST request.
     *
     * The function creates the RESTful request URL out of the given resource URI
     * and the given REST interface.  A REST URI for example to request a program
     * with the id 49 looks like this: /programs/program/49
     *
     * @param      array       $resource       RESTful resource e.g. /programs
     * @param      array       $query          HTTP query parameter e.g. /programs?q=telecom
     * @param      string      $body           HTTP xml body message
     *
     * @access     public
     *
     * @return     string      $result         returns http response
     *
     */
    public function doRestfulRequest ( $resource, $parameter = false, $body = false )
    {
        $uri  = "/" . implode("/", $resource) . "/";

        $header['authorization'] = 'ZXWS ' . $this->auth->getConnectId();

        $header['user-agent'] = USERAGENT;
        $header['host'] = HOST;

        if ( $this->auth->isSecureApiCall() )
        {
            $header['nonce'] = $this->auth->getNonce();
            $header['date'] = gmdate('D, d M Y H:i:s T');

            $this->auth->setTimeStamp($header['date']);

            $sign = $this->auth->getSignature($this->httpVerb, $uri, $header['nonce']);

            $header['authorization'] = $header['authorization'] . ":" . $sign;
        }

        if ( $this->httpVerb == PUT || $this->httpVerb == POST )
        {
            $header['content-length'] = strlen($body);
        }

        if ( $body )
        {
            $header['content-type']   = $this->getContentType();
        }

        $uri = $this->getRestfulPath() . $uri;

        if ( is_array($parameter) )
        {
            $query = http_build_query($parameter, '', '&');

            if ( strlen($query) > 0 )
            {
                $uri .= '?' . $query;
            }
        }

        $result = $this->httpRequest($uri, $header, $body);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * HTTP REST Connection (GET/POST)
     *
     * @param          string      $uri            uri path
     * @param          string      $header         list of header params
     * @param          string      $body           POST data
     *
     * @access         private
     *
     * @return         mixed                       response data or false
     */
    private function httpRequest ( $uri, $header, $body = false )
    {
        $responseHeader  = '';
        $responseContent = '';

        if ( $this->proxyHost )
        {
            $fp = fsockopen($this->proxyHost, $this->proxyPort);
        }
        else if ( $this->httpProtocol == HTTPS )
        {
            $fp = fsockopen(SSL_PREFIX . HOST, HTTPS_PORT);
        }
        else
        {
            $fp = fsockopen(HOST, HTTP_PORT);
        }

        if ( !$fp )
        {
            throw new ApiClientException("Coudn't open socket!");
        }

        if ( $this->proxyHost )
        {
            $requestHeader =  $this->httpVerb . " " . $uri .
                " HTTP/1.0\r\nHost: " . $this->proxyHost . "\r\n\r\n";
        }
        else
        {
            $requestHeader  = $this->httpVerb . " " . $uri . " HTTP/1.1\r\n";
        }

        foreach ( $header as $key => $value )
        {
            $requestHeader .= ucwords($key) . ": " . $value . "\r\n";
        }

        if ( $this->httpCompression )
        {
            $requestHeader .= "Accept-Encoding: gzip, deflate, compress;q=0.9\r\n";
        }

        $requestHeader .= "connection: close\r\n\r\n";

        if ( $body )
        {
            $requestHeader .= $body;
        }

        fwrite($fp, $requestHeader);

        do
        {
            if (feof($fp)) break;
            $responseHeader .= fread($fp, 1);
        }
        while (!preg_match('/\\r\\n\\r\\n$/', $responseHeader));

        if ( $this->isValidHeader($responseHeader) )
        {
            if (!stristr($responseHeader, "Transfer-Encoding: chunked"))
            {
                while (!feof($fp))
                {
                    $responseContent .= fgets($fp, 128);
                }
            }
            else
            {
                while ( ($chunk_length = hexdec(fgets($fp))) )
                {
                    $responseContentChunk = '';

                    $read_length = 0;

                    while ( $read_length < $chunk_length )
                    {
                        $responseContentChunk .= fread($fp, $chunk_length -
                            $read_length);

                        $read_length = strlen($responseContentChunk);
                    }

                    $responseContent .= $responseContentChunk;

                    fgets($fp);

                }
            }

            return chop($responseContent);
        }
        else
        {
            throw new ApiClientException("\n" . $requestHeader . "\n" .
               $responseHeader);
        }
    }



    /**
     * Returns wsdl api endpoint
     *
     * @param      string      $service        soap service
     *
     * @access     private
     *
     * @return     string                      wsdl url including version
     */
    private function getWsdlUrl ( $service )
    {
        if ( $this->httpProtocol == HTTPS )
        {
            $prefix = HTTPS_PREFIX;
        }
        else
        {
            $prefix = HTTP_PREFIX;
        }

        switch($service) {
          case SERVICE_PUBLISHER:
              if ( $this->version )
              {
                  return $prefix . HOST . URI_WSDL . '/' . $this->version;
              }

              return $prefix . HOST . URI_WSDL;
            break;
          case SERVICE_CONNECT:
              return $prefix . OAUTH_HOST . URI_WSDL;
            break;
          case SERVICE_DATA:
              return $prefix . DATA_HOST . URI_WSDL;
            break;
        }
    }



    /**
     * Returns restful api endpoint path
     *
     * @access     private
     *
     * @return     string                      endpoint without host
     */
    private function getRestfulPath ()
    {
        if ( $this->protocol == PROTOCOL_JSON )
        {
            $uri = URI_JSON;
        }
        else
        {
            $uri = URI_XML;
        }

        if ( $this->version )
        {
            $uri = $uri . '/' . $this->version;
        }

        return $uri;
    }



    /**
     * Returns if http response is valid.
     *
     * Method checks if request response returns HTTP status code 200
     * or not. If the status code is different from 200 the method
     * returns false.
     *
     * @param      string      $uri        request uri
     * @param      string      $uri        request uri
     *
     * @access     private
     *
     * @return     string                  encoded string
     */
    private function isValidHeader ( $responseHeader )
    {
        $header = explode("\n", $responseHeader);

        if ( count($header) > 0 )
        {
            $status_line = explode(" ", $header[0]);

            if ( count($status_line) >= 3 && $status_line[1] == '200' )
            {
                return true;
            }
        }

        return false;
    }



    /**
     * Returns restful content type
     *
     * @access     private
     *
     * @return     string                      http content type
     */
    private function getContentType ()
    {
        if ( $this->protocol == PROTOCOL_JSON )
        {
            return CONTENT_JSON;
        }

        return CONTENT_XML;
    }
}

?>