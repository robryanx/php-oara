<?php

/**
 * Api Constants Definitions.
 *
 * Supported Version: PHP >= 5.1.0
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
 */


/**
 * Api Protocol Types
 */
define("PROTOCOL_XML",                  "xml");
define("PROTOCOL_JSON",                 "json");
define("PROTOCOL_SOAP",                 "soap");


/**
 * Default Api Protocol
 *
 */
define("RESTFUL_INTERFACE",             "RestfulMethods");
define("SOAP_INTERFACE",                "SoapMethods");


/**
 * Supported Api Versions
 */
define("VERSION_2009_07_01",            "2009-07-01");
define("VERSION_2011_03_01",            "2011-03-01");


/**
 * Default Version & Protocol
 *
 */
define("PROTOCOL_DEFAULT",              PROTOCOL_XML);
define("VERSION_DEFAULT",               VERSION_2011_03_01);


/**
 * Api Endpoint Definitions
 */
define("HTTP",                          "HTTP");
define("HTTPS",                         "HTTPS");
define("HTTP_PREFIX",                   "http://");
define("HTTPS_PREFIX",                  "https://");
define("SSL_PREFIX",                    "ssl://");
define("HTTP_PORT",                     80);
define("HTTPS_PORT",                    443);
define("HOST",                          "api.zanox.com");
define("OAUTH_HOST",                    "auth.zanox.com");
define("URI_WSDL",                      "/wsdl");
define("URI_SOAP",                      "/soap");
define("URI_XML",                       "/xml");
define("URI_JSON",                      "/json");


/**
 * User Agent
 */
define("USERAGENT",                     "zxPhp ApiClient");


/**
 * Service Names
 */
define("SERVICE_PUBLISHER",             "publisherservice");
define("SERVICE_CONNECT",               "connectservice");


/**
 * HTTP Restful Actions
 */
define("GET",                           "GET");
define("POST",                          "POST");
define("PUT",                           "PUT");
define("DELETE",                        "DELETE");


/**
 * HTTP Content Types
 */
define("CONTENT_XML",                   "text/xml");
define("CONTENT_JSON",                  "application/json");


/**
 * ApiClient Internal Error Types
 */
define("CLI_ERROR_VERSION",             "Unsupported API version");
define("CLI_ERROR_PROTOCOL_VERSION",    "Unsupported API protocol/version");
define("CLI_ERROR_PROTOCOL_CLASS",      "Protocol class does not exist");
define("CLI_ERROR_PROTOCOL_CLASSFILE",  "Could not find protocol class file");
define("CLI_ERROR_PROTOCOL",            "Unsupported API protocol");

?>