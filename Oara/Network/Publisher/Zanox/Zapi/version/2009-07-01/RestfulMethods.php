<?php

require_once DIR . '/version/2009-07-01/model/IMethods.php';
require_once DIR . '/includes/ApiMethods.php';
require_once DIR . '/includes/ApiConstRest.php';

/**
 * Restful Api Methods
 *
 * The module implements all Api methods defined by the IMethods interface.
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
 * @copyright   Copyright (c) 2007-2009 zanox.de AG
 */
class RestfulMethods extends ApiMethods implements IMethods
{

    /**
     * Get a single product.
     *
     * @param      string      $zupId          product id hash
     * @param      int         $adspaceId      adspace id (optional)
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            single product item or false
     */
    public function getProduct( $zupId, $adspaceId = NULL )
    {
    	$resource = array('products', 'product', $zupId);

    	$parameter['adspace'] = $adspaceId;

    	$this->setRestfulAction(GET);
        $this->setSecureApiCall(false);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get product categories.
     *
     * @param      int         $rootCategory   category id (optional)
     * @param      bool        $includeChilds  include child nodes (optional)
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            single product item or false
     */
    public function getProductCategories( $rootCategory = 0, $includeChilds = false )
    {
    	$resource = array('products', 'categories');

        $parameter['parent']        = $rootCategory;
        $parameter['includeChilds'] = $includeChilds;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(false);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get products by advertiser program.
     *
     * @param      int         $programId      advertiser program id
     * @param      int         $adspaceId      adspace id (optional)
     * @param      string      $modifiedDate   last modification date (optional)
     * @param      int         $page           page of result set (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            product result set or false
     */
    public function getProducts( $programId, $adspaceId = NULL,
        $modifiedDate = NULL, $page = 0, $items = 10 )
    {
        $resource = array('products', 'program', $programId);

        $parameter['adspace']  = $adspaceId;
        $parameter['modified'] = $modifiedDate;
        $parameter['page']     = $page;
        $parameter['items']    = $items;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(false);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Search for products.
     *
     * @param      string      $query          search string
     * @param      string      $searchType     search type (optional)
     *                                         (contextual or phrase)
     * @param      string      $ip             products with sales region
     *                                         within the region of the ip
     *                                         address (optional)
     * @param      string      $region         limit search to region (optional)
     * @param      int         $categoryId     limit search to categorys (optional)
     * @param      array       $programId      limit search to program list of
     *                                         programs (optional)
     * @param      boolean     $hasImages      products with images (optional)
     * @param      float       $minPrice       minimum price (optional)
     * @param      float       $maxPrice       maximum price (optional)
     * @param      int         $adspaceId      adspace id (optional)
     * @param      int         $page           page of result set (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            list of products or false
     */
    public function searchProducts( $query, $searchType = 'phrase', $ip = NULL,
        $region = NULL, $categoryId = NULL, $programId = array(), $hasImages = true,
        $minPrice = 0, $maxPrice = NULL, $adspaceId = NULL, $page = 0,
        $items = 10 )
    {
        $resource = array('products');

        $parameter['q']          = $query;
        $parameter['searchType'] = $searchType;
        $parameter['ip']         = $ip;
        $parameter['region']     = $region;
        $parameter['category']   = $categoryId;
        $parameter['programs']  = implode(",", $programId);
        $parameter['hasImages']  = $hasImages;
        $parameter['minPrice']   = $minPrice;
        $parameter['maxPrice']   = $maxPrice;
        $parameter['adspace']    = $adspaceId;
        $parameter['page']       = $page;
        $parameter['items']      = $items;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(false);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Retrieve a single zanox advertiser program item.
     *
     * @param      int         $programId      id of program to retrieve
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            program item or false
     */
    public function getProgram ( $program_id )
    {
        $resource = array('programs', 'program', $program_id);

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(false);

        $result = $this->doRestfulRequest($resource);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get advertiser program categories.
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            category result set or false
     */
    public function getProgramCategories()
    {
        $resource = array('programs', 'categories');

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(false);

        $result = $this->doRestfulRequest($resource);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get advertiser program applications by adspace.
     *
     * @param      int         $adspaceId      advertising space id
     * @param      int         $page           page of result set (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            program result set or false
     */
    public function getProgramsByAdspace( $adspaceId, $page = 0, $items = 10 )
    {
        $resource = array('programs', 'adspace', $adspaceId);

        $parameter['page']         = $page;
        $parameter['items']        = $items;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Search zanox advertiser programs.
     *
     * @param      string      $query          search string
     * @param      string      $startDate      program start date (optional)
     * @param      string      $partnerShip    partnership status (optional)
     *                                         (direct or indirect)
     * @param      boolean     $hasProducts    program has product data
     * @param      string      $ip             programs in region of ip address
     * @param      string      $region         program region
     * @param      string      $categoryId     program category id
     * @param      int         $page           page of result set
     * @param      int         $items          items per page
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            programs result set or false
     */
    public function searchPrograms( $query, $startDate = NULL,
        $partnerShip = NULL, $hasProducts = false, $ip = NULL,
        $region = NULL, $categoryId = NULL, $page = 0, $items = 10 )
    {
        $resource = array('programs');

        $parameter['q']           = $query;
        $parameter['startDate']   = $startDate;
        $parameter['partnerShip'] = $partnerShip;
        $parameter['hasProducts'] = $hasProducts;
        $parameter['ip']          = $ip;
        $parameter['region']      = $region;
        $parameter['category']    = $categoryId;
        $parameter['page']        = $page;
        $parameter['items']       = $items;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(false);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Create program application for a given adspace.
     *
     * @param      int         $programId     advertiser program id
     * @param      int         $adspaceId     advertising space id
     *
     * @access     public
     * @category   signature
     *
     * @return     boolean                    true or false
     */
    public function createProgramApplication( $programId, $adspaceId )
    {
    	$resource = array('programs', 'program', $programId, 'adspace', $adspaceId);

        $this->setRestfulAction(POST);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Delete program application.
     *
     * @param      int         $programId     advertiser program id
     * @param      int         $adspaceId     advertising space id
     *
     * @access     public
     * @category   signature
     *
     * @return     boolean                     true or false
     */
    public function deleteProgramApplication ( $programId, $adspaceId )
    {
        $resource = array('programs', 'program', $programId, 'adspace', $adspaceId);

        $this->setRestfulAction(DELETE);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get a single admedium.
     *
     * @param      int         $admediumId     advertising medium id
     * @param      int         $adspaceId      advertising space id (optional)
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            single product item or false
     */
    public function getAdmedium( $admediumId, $adspaceId = NULL )
    {
        $resource = array('admedia', 'admedium', $admediumId);

        $parameter['adspace']  = $adspaceId;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(false);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get admedium categories.
     *
     * @param      int         $programId      program admedium categories
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            list of admedium categories
     */
    public function GetAdmediumCategories( $programId )
    {
        $resource = array('admedia', 'categories', 'program', $programId);

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(false);

        $result = $this->doRestfulRequest($resource);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Retrieve all advertising media items.
     *
     * Note: The admedium categories are specific to each advertiser program.
     *
     * Supported admedium types are
     *
     *    801: Text
     *    802: Image
     *    803: Image with text
     *    804: HTML (may also include Flash)
     *    805: Script (may also include Flash)
     *
     * @param      int         $programId      advertiser program id (optional)
     * @param      string      $ip             ip address (geo feature) (optional)
     * @param      string      $region         limit search to region (optional)
     * @param      string      $format         admedia format (optional)
     * @param      string      $partnerShip    partnership status (optional)
     *                                         (direct or indirect)
     * @param      string      $purpose        purpose of admedia (optional)
     *                                         (startPage, productDeeplink,
     *                                         categoryDeeplink, searchDeeplink)
     * @param      string      $admediumType   type of admedium (optional)
     *                                         (html, script, lookatMedia, image,
     *                                         imageText, text)
     * @param      int         $categoryId     admedium category id (optional)
     * @param      int         $adspaceId      adspace id (optional)
     * @param      int         $page           page of result set (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            admedia result set or false
     */
    public function getAdmedia( $programId = NULL, $ip = NULL, $region = NULL,
        $format = NULL, $partnerShip = NULL, $purpose = NULL,
        $admediumType = NULL, $categoryId = NULL, $adspaceId = NULL, $page = 0,
        $items = 10 )
    {
        $resource = array('admedia');

        $parameter['program']      = $programId;
        $parameter['ip']           = $ip;
        $parameter['region']       = $region;
        $parameter['format']       = $format;
        $parameter['partnerShip']  = $partnerShip;
        $parameter['purpose']      = $purpose;
        $parameter['admediumType'] = $admediumType;
        $parameter['category']     = $categoryId;
        $parameter['adspace']      = $adspaceId;
        $parameter['page']         = $page;
        $parameter['items']        = $items;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(false);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Returns a single advertising spaces.
     *
     * @param      int         $adspaceId      advertising space id
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            adspace item or false
     */
    public function getAdspace( $adspaceId )
    {
        $resource = array('adspaces', 'adspace', $adspaceId);

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Returns all advertising spaces.
     *
     * @param      int         $page           result set page (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            adspaces result set or false
     */
    public function getAdspaces ( $page = 0, $items = 10 )
    {
        $resource = array('adspaces');

        $parameter['page']         = $page;
        $parameter['items']        = $items;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Create advertising space (signature).
     *
     * ---
     *
     * Usage example:
     * <code>
     *
     *      $api = ZanoxAPI::factory(PROTOCOL_XML);
     *
     *      $name = "example";
     *      $lang = "en";
     *      $url  = "http://www.example.org";
     *      $contact = "webmaster@example.org";
     *      $description = "example demonstrates how to use the api";
     *      $adspaceType = "website";
     *      $scope = "private";
     *      $visitors = 1;
     *      $impressions = 1;
     *      $keywords = "keyword1, keyword2, keyword3";
     *      $regions['region'] = array("DE", "US");
     *      $categories['category'] = array('1', '2');
     *
     *      $result = $api->createAdspace($name, $lang, $url, $contact,
     *          $description, $adspaceType, $scope, $visitors, $impressions,
     *          $keywords, $regions, $categories);
     *
     * </code>
     *
     * ---
     *
     * @param      string      $name           adspace name
     * @param      string      $lang           language of adspace (e.g. en)
     * @param      string      $url            url of adspace
     * @param      string      $contact        contact address (email)
     * @param      string      $description    description of adspace
     * @param      string      $adspaceType    adspace typ (website, email or searchengine)
     * @param      array       $scope          adspace scope (private or business)
     * @param      int         $visitors       adspace monthly visitors
     * @param      int         $impressions    adspace monthly page impressions
     * @param      string      $keywords       keywords for adspace (optional)
     * @param      array       $regions        adspace customer regions (optional)
     * @param      array       $categories     adspace categories (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            adspace item or false
     */
    public function createAdspace ( $name, $lang, $url, $contact, $description,
        $adspaceType, $scope, $visitors, $impressions, $keywords = NULL,
        $regions = array(), $categories = array() )
    {
        $resource = array('adspaces', 'adspace');

        $adspaceItem['name']        = $name;
        $adspaceItem['url']         = $url;
        $adspaceItem['contact']     = $contact;
        $adspaceItem['description'] = $description;
        $adspaceItem['adspaceType'] = $adspaceType;
        $adspaceItem['scope']       = $scope;
        $adspaceItem['visitors']    = $visitors;
        $adspaceItem['impressions'] = $impressions;
        $adspaceItem['keywords']    = $keywords;
        $adspaceItem['regions']     = $regions;
        $adspaceItem['categories']  = $categories;

        $attributes['lang'] = $lang;

        $body = $this->serialize('adspaceItem', $adspaceItem, $attributes);

        $this->setRestfulAction(POST);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource, false, $body);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Update advertising space.
     *
     * ---
     *
     * Usage example:
     * <code>
     *
     *      $api = ZanoxAPI::factory(PROTOCOL_XML);
     *
     *      $id = 234324;
     *      $name = "example";
     *      $lang = "en";
     *      $url  = "http://www.example.org";
     *      $contact = "webmaster@example.org";
     *      $description = "example demonstrates how to use the api";
     *      $adspaceType = "website";
     *      $scope = "private";
     *      $visitors = 1;
     *      $impressions = 1;
     *      $keywords = "keyword1, keyword2, keyword3";
     *      $regions['region'] = array("DE", "US");
     *      $categories['category'] = array('1', '2');
     *
     *      $result = $api->createAdspace($id, $name, $lang, $url, $contact,
     *          $description, $adspaceType, $scope, $visitors, $impressions,
     *          $keywords, $regions, $categories);
     *
     * </code>
     *
     * ---
     *
     * @param      int         $adspaceId      adspace id
     * @param      string      $name           adspace name
     * @param      string      $lang           language of adspace (e.g. en)
     * @param      string      $url            url of adspace
     * @param      string      $contact        contact address (email)
     * @param      string      $description    description of adspace
     * @param      string      $adspaceType    adspace typ (website, email or searchengine)
     * @param      array       $scope          adspace scope (private or business)
     * @param      int         $visitors       adspace monthly visitors
     * @param      int         $impressions    adspace monthly page impressions
     * @param      string      $keywords       keywords for adspace (optional)
     * @param      array       $regions        adspace customer regions (optional)
     * @param      array       $categories     adspace categories (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            adspace item or false
     */
    public function updateAdspace ( $adspaceId, $name, $lang, $url, $contact,
        $description, $adspaceType, $scope, $visitors, $impressions,
        $keywords = NULL, $regions = array(), $categories = array() )
    {
        $resource = array('adspaces', 'adspace', $adspaceId);

        $adspaceItem['name']        = $name;
        $adspaceItem['url']         = $url;
        $adspaceItem['contact']     = $contact;
        $adspaceItem['description'] = $description;
        $adspaceItem['adspaceType'] = $adspaceType;
        $adspaceItem['scope']       = $scope;
        $adspaceItem['visitors']    = $visitors;
        $adspaceItem['impressions'] = $impressions;
        $adspaceItem['keywords']    = $keywords;
        $adspaceItem['regions']     = $regions;
        $adspaceItem['categories']  = $categories;

        $attributes['lang'] = $lang;
        $attributes['id']   = $adspaceId;

        $body = $this->serialize('adspaceItem', $adspaceItem, $attributes);

        $this->setRestfulAction(PUT);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource, false, $body);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Removes advertising space.
     *
     * @param      int         $adspaceId      advertising space id
     *
     * @access     public
     * @category   signature
     *
     * @return     boolean                     true on success
     */
    public function deleteAdspace( $adspaceId )
    {
        $resource = array('adspaces', 'adspace', $adspaceId);

        $this->setRestfulAction(DELETE);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Return zanox user profile.
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            profile item
     */
    public function getProfile()
    {
    	$resource = array('profiles');

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Update zanox user profile.
     *
     * @param      array       $profileId      user profile id
     * @param      array       $firstName      first name
     * @param      array       $lastName       last name
     * @param      array       $email          email address
     * @param      array       $country        country or residence
     * @param      array       $street1        street 1
     * @param      array       $street2        street 2 (optional)
     * @param      array       $city           city
     * @param      array       $company        name of company (optional)
     * @param      array       $phone          phone number (optional)
     * @param      array       $mobile         mobile number (optional)
     * @param      array       $fax            fax number (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     boolean                     true on success
     */
    public function updateProfile( $profileId, $firstName, $lastName, $email,
        $country, $street1, $street2 = NULL, $city, $zipcode, $company = NULL,
        $phone = NULL, $mobile = NULL, $fax = NULL )
    {
        $resource = array('profiles');

        $profileItem['firstName'] = $firstName;
        $profileItem['lastName']  = $lastName;
        $profileItem['email']     = $email;
        $profileItem['country']   = $country;
        $profileItem['street1']   = $street1;
        $profileItem['street2']   = $street2;
        $profileItem['city']      = $city;
        $profileItem['zipcode']   = $zipcode;
        $profileItem['company']   = $company;
        $profileItem['phone']     = $phone;
        $profileItem['mobile']    = $mobile;
        $profileItem['fax']       = $fax;

        $attributes['id'] = $profileId;

        $body = $this->serialize('profileItem', $profileItem, $attributes);

        $this->setRestfulAction(PUT);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource, false, $body);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get back accounts.
     *
     * @param      int         $page           result set page (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            account balances result set or
     *                                         false
     */
    public function getBankAccounts( $page = 0, $items = 10 )
    {
        $resource = array('payments', 'bankaccounts');

        $parameter['page']  = $page;
        $parameter['items'] = $items;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get single back account.
     *
     * @param      int         $bankAccountId  result set page
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            account balances result set or
     *                                         false
     */
    public function getBankAccount( $bankAccountId )
    {
        $resource = array('payments', 'bankaccounts', 'bankaccount', $bankAccountId);

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get account balance
     *
     * @param      int         $currency       currence code of balance account
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            payment item or false
     */
    public function getBalance( $currency )
    {
        $resource = array('payments', 'balances', 'balance', $currency);

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get currency account balances.
     *
     * @param      int         $page           result set page (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            account balances result set or
     *                                         false
     */
    public function getBalances( $page = 0, $items = 10 )
    {
        $resource = array('payments', 'balances');

        $parameter['page']  = $page;
        $parameter['items'] = $items;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get payment transactions of the current zanox account.
     *
     * @param      int         $page           page of result set (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            payments result set or false
     */
    public function getPayments( $page = 0, $items = 10 )
    {
        $resource = array('payments');

        $parameter['page']  = $page;
        $parameter['items'] = $items;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get a single payment item.
     *
     * @param      int         $paymentId      payment item id
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            payment item or false
     */
    public function getPayment( $paymentId )
    {
        $resource = array('payments', 'payment', $paymentId);

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get basic sales/leads report.
     *
     * @param      string      $fromDate       report start date
     * @param      string      $toDate         report end date
     * @param      string      $dateType       type of date to filter by (optional)
     *                                         (clickDate, trackingDate,
     *                                         modifiedDate)
     * @param      string      $currency       currency (optional)
     * @param      int         $programId      program id (optional)
     * @param      int         $admediumId     admedium id (optional)
     * @param      int         $admediumFormat admedium format id (optional)
     * @param      int         $adspaceId      adspace id (optional)
     * @param      string      $reviewState    filter by review status (optional)
     *                                         (confirmed, open, rejected or
     *                                         approved)
     * @param      string      $groupBy        group report by option (optional)
     *                                         (country, region, city, currency,
     *                                         admedium, program, adspace,
     *                                         linkFormat, reviewState,
     *                                         trackingCategory, month, day,
     *                                         hour, year, dayOfWeek)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            payment item or false
     */
    public function getReportBasic( $fromDate, $toDate, $dateType = NULL,
        $currency = NULL, $programId = NULL, $admediumId = NULL,
        $admediumFormat = NULL, $adspaceId = NULL, $reviewState = NULL,
        $groupBy = NULL )
    {
        $resource = array('reports', 'basic');

        $parameter['fromDate']       = $fromDate;
        $parameter['toDate']         = $toDate;
        $parameter['dateType']       = $dateType;
        $parameter['currency']       = $currency;
        $parameter['program']        = $programId;
        $parameter['admedium']       = $admediumId;
        $parameter['admediumFormat'] = $admediumFormat;
        $parameter['adspace']        = $adspaceId;
        $parameter['state']          = $reviewState;
        $parameter['groupBy']        = $groupBy;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }


    /**
     * Get sales report.
     *
     * ---
     *
     * Sample usage:
     * <code>
     *
     *     $zx = ZanoxAPI::factory(PROTOCOL_XML);
     *
     *     $zx->getSales('2009-09-01');
     *
     * </code>
     *
     * ---
     *
     * @param      string      $date           date of sales
     * @param      string      $dateType       type of date to filter by (optional)
     *                                         (clickDate, trackingDate,
     *                                         modifiedDate)
     * @param      int         $programId      filter by program id (optional)
     * @param      int         $adspaceId      filter by adspace id (optional)
     * @param      array       $reviewState    filter by review status (optional)
     *                                         (confirmed, open, rejected or
     *                                         approved)
     * @param      int         $page           page of result set (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            sales result set or false
     */
    public function getSales ( $date, $dateType = NULL, $programId = NULL,
        $adspaceId = NULL, $reviewState = NULL, $page = 0, $items = 10 )
    {
        $resource = array('reports', 'sales', 'date', $date);

        $parameter['dateType'] = $dateType;
        $parameter['program']  = $programId;
        $parameter['adspace']  = $adspaceId;
        $parameter['state']    = $reviewState;
        $parameter['page']     = $page;
        $parameter['items']    = $items;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get single sale item.
     *
     * @param      int         $saleId         sale id
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            sales result set or false
     */
    public function getSale ( $saleId )
    {
        $resource = array('reports', 'sales', 'sale', $saleId);

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get leads report.
     *
     * ---
     *
     * Sample usage:
     * <code>
     *
     *     $zx = ZanoxAPI::factory(PROTOCOL_XML);
     *
     *     $zx->getLeads('2009-09-01');
     *
     * </code>
     *
     * ---
     *
     * @param      string      $date           date of sales
     * @param      string      $dateType       type of date to filter by (optional)
     *                                         (clickDate, trackingDate,
     *                                         modifiedDate)
     * @param      int         $programId      filter by program id (optional)
     * @param      int         $adspaceId      filter by adspace id (optional)
     * @param      array       $reviewState    filter by review status (optional)
     *                                         (confirmed, open, rejected or
     *                                         approved)
     * @param      int         $page           page of result set (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            sales result set or false
     */
    public function getLeads ( $date, $dateType = NULL, $programId = NULL,
        $adspaceId = NULL, $reviewState = NULL, $page = 0, $items = 10 )
    {
        $resource = array('reports', 'leads', 'date', $date);

        $parameter['dateType'] = $dateType;
        $parameter['program']  = $programId;
        $parameter['adspace']  = $adspaceId;
        $parameter['state']    = $reviewState;
        $parameter['page']     = $page;
        $parameter['items']    = $items;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }


    /**
     * Get single sale item.
     *
     * @param      int         $leadId         lead id
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            sales result set or false
     */
    public function getLead ( $leadId )
    {
        $resource = array('reports', 'leads', 'lead', $leadId);

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Returns new OAuth user session
     *
     * @param      string      $authToken      authentication token
     *
     * @access     public
     *
     * @return     object                      user session
     */
    public function getSession ( $authToken )
    {
        throw new ApiClientException("Restful API Interface doesn't
            support getSession()! Please use the SOAP Interface.");
    }



    /**
     * Closes OAuth user session
     *
     * @access     public
     *
     * @param      string      $connectId      connect ID
     *
     * @return     bool                        returns true on success
     *
     * @annotation(secure => true, paging = false)
     */
    public function closeSession ( $connectId )
    {
        throw new ApiClientException("Restful API Interface doesn't
            support closeSession()! Please use the SOAP Interface.");
    }



    /**
     * Get zanox User Interface Url
     *
     * @param      string      $connectId      connect ID
     * @param      string      $sessionKey     session key
     *
     * @access     public
     * @category   signature
     *
     * @return     bool                        returns true on success
     */
    public function getUiUrl( $connectId, $sessionKey )
    {
        throw new ApiClientException("Restful API Interface doesn't
            support getUiUrl()! Please use the SOAP Interface.");
    }


}

?>