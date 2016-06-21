<?php

require_once DIR . '/version/2011-03-01/model/IMethods.php';
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
 * @version     2011-03-01
 * @copyright   Copyright (c) 2007-2011 zanox.de AG
 */
class RestfulMethods extends ApiMethods implements IMethods
{

    /**
     * done
     *
     * Get a single product.
     *
     * @param      string      $productId      product id hash
     * @param      int         $adspaceId      adspace id (optional)
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            single product item or false
     */
    public function getProduct ( $productId, $adspaceId = NULL )
    {
        $resource = array('products', 'product', $productId);

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
     * done
     *
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
    public function getProductCategories ( $rootCategory = 0, $includeChilds = false )
    {
    	$resource = array('products', 'categories');

        $parameter['parent'] = $rootCategory;

        if ($includeChilds)
        {
            $parameter['includechilds'] = 'true';
        }
        else
        {
            $parameter['includechilds'] = 'false';
        }

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
     * done
     *
     * Search for products.
     *
     * @param      string      $query          search string
     * @param      string      $searchType     search type (optional)
     *                                         (contextual or phrase)
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
    public function searchProducts ( $query, $searchType = 'phrase',
        $region = NULL, $categoryId = NULL, $programs = array(),
        $hasImages = true, $minPrice = 0, $maxPrice = NULL, $adspaceId = NULL,
        $page = 0, $items = 10 )
    {
        $resource = array('products');

        $parameter['q']          = $query;
        $parameter['searchType'] = $searchType;
        $parameter['region']     = $region;
        $parameter['category']   = $categoryId;
        $parameter['programs']  = implode(",", $programs);
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
     * Get single incentive.
     *
     * @param       int         $incentiveId    incentive id (mandatory)
     * @param       int         $adspaceId      adspace id (optional)
     *
     * @access      public
     * @category    nosignature
     *
     * @return      object or string            incentive or false
     */
    public function getIncentive ( $incentiveId, $adspaceId = NULL )
    {
        $resource = array('incentives', 'incentive', $incentiveId);

        $parameter['adspaceId']     = $adspaceId;

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
     * Get single exclusive incentive.
     *
     * @param       int         $incentiveId    incentive id (mandatory)
     * @param       int         $adspaceId      adspace id (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string            incentive or false
     */
    public function getExclusiveIncentive ( $incentiveId, $adspaceId = NULL )
    {
        $resource = array('incentives', 'incentive', $incentiveId);

        $parameter['adspaceId']     = $adspaceId;

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
     * Search for incentives.
     *
     * @param       int         $programId      limit search to program list of
     *                                          programs (optional)
     * @param       int         $adspaceId      adspace id (optional)
     * @param       string      $incentiveType  type of incentive (optional)
     *                                          (coupons, samples, bargains,
     *                                          freeProducts, noShippingCosts,
     *                                          lotteries)
     * @param       string      $region         program region (optional)
     * @param       int         $page           page of result set (optional)
     * @param       int         $items          items per page (optional)
     *
     * @access      public
     * @category    nosignature
     *
     * @return      object or string            list of incentives or false
     */
    public function searchIncentives ( $programId = NULL, $adspaceId = NULL,
        $incentiveType = NULL, $region = NULL, $page = 0, $items = 10 )
    {
        $resource = array('incentives');

        $parameter['programId']     = $programId;
        $parameter['adspaceId']     = $adspaceId;
        $parameter['incentiveType'] = $incentiveType;
        $parameter['region']        = $region;
        $parameter['page']          = $page;
        $parameter['items']         = $items;

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
     * Search for exclusive incentives.
     *
     * @param       int         $programId      limit search to program list of
     *                                          programs (optional)
     * @param       int         $adspaceId      adspace id (optional)
     * @param       string      $incentiveType  type of incentive (optional)
     *                                          (coupons, samples, bargains,
     *                                          freeProducts, noShippingCosts,
     *                                          lotteries)
     * @param       string      $region         program region (optional)
     * @param       int         $page           page of result set (optional)
     * @param       int         $items          items per page (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string            list of incentives or false
     */
    public function searchExclusiveIncentives ( $programId = NULL, $adspaceId = NULL,
        $incentiveType = NULL, $region = NULL, $page = 0, $items = 10  )
    {
        $resource = array('incentives');

        $parameter['programId']     = $programId;
        $parameter['adspaceId']     = $adspaceId;
        $parameter['incentiveType'] = $incentiveType;
        $parameter['region']        = $region;
        $parameter['page']          = $page;
        $parameter['items']         = $items;

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
     * done
     *
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
    public function getAdmedium ( $admediumId, $adspaceId = NULL )
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
     * done
     *
     * Get admedium categories.
     *
     * @param      int         $programId      program admedium categories
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            list of admedium categories
     */
    public function getAdmediumCategories ( $programId )
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
     * done
     *
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
    public function getAdmedia ( $programId = NULL, $region = NULL,
        $format = NULL, $partnerShip = NULL, $purpose = NULL,
        $admediumType = NULL, $categoryId = NULL, $adspaceId = NULL, $page = 0,
        $items = 10 )
    {
        $resource = array('admedia');

        $parameter['program']      = $programId;
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
     * Get single application.
     *
     * @param       int         $applicationId      application id (mandatory)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                application item or false
     */
    public function getApplication ( $applicationId )
    {
        $resource = array('applications', 'application', $applicationId);

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
     * Get applications.
     *
     * @param       string      $name               name of the application (optional)
     * @param       int         $width              width of application (optional)
     * @param       int         $height             height of application (optional)
     * @param       string      $format             format of application (optional)
     * @param       string      $role               role of the application (optional)
     *                                              (developer, customer, tester)
     * @param       string      $applicationType    type of application (optional)
     *                                              (widget, saas, software)
     * @param       int         $page               page of result set (optional)
     * @param       int         $items              items per page (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string            application item or false
     */
    public function getApplications ( $name = NULL, $width = NULL,
        $height = NULL, $format = NULL, $role = NULL, $applicationType = NULL,
        $page = 0, $items = 0 )
    {
        $resource = array('applications');

        $parameter['name']              = $name;
        $parameter['roletype']          = $role;
        $parameter['applicationType']   = $applicationType;
        $parameter['size']['width']     = $width;
        $parameter['size']['height']    = $height;
        $parameter['size']['format']    = $format;
        $parameter['page']              = $page;
        $parameter['items']             = $items;

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
     * Create an application
     *
     * @param       string      $name                   name (optional)
     * @param       string      $version                version (optional)
     * @param       int         $adrank                 adrank (optional)
     * @param       string      $tags                   tags (optional)
     * @param       int         $status                 status (optional)
     * @param       boolean     $mediaSlotCompatible    compatible to media slot (optional)
     * @param       boolean     $inline
     * @param       string      $integrationCode        integration code (optional)
     * @param       string      $integrationNotes       integration notes (optional)
     * @param       string      $description            description (optional)
     * @param       string      $terms                  terms of service (optional)
     * @param       string      $connectRole            role of the application (optional)
     *                                                  (developer, customer, tester)
     * @param       string      $connectId              connect id (optional)
     * @param       string      $connectStatus          connect status (optional)
     *                                                  (active, inactive)
     * @param       string      $connectUrl             connect url (optional)
     * @param       string      $cancelUrl              cancel url (optional)
     * @param       string      $documentationUrl       documentation url (optional)
     * @param       string      $companyUrl             company url (optional)
     * @param       string      $developer              developer (optional)
     * @param       float       $pricingShare           price for share model (optional)
     * @param       float       $pricingSetup           price for setup (optional)
     * @param       float       $pricingMonthly         price for monthly usage (optional)
     * @param       string      $pricingCurrency        pricing currency (optional)
     * @param       string      $pricingDescription     pricing description (optional)
     * @param       string      $startDate              start date (optional)
     * @param       string      $modifiedDate           modification date (optional)
     * @param       string      $installableTo          who can install the (optional)
     *                                                  application
     *                                                  (advertiser, publisher)
     * @param       string      $applicationType        type of application (optional)
     *                                                  (widget, saas, software)
     * @param       int         $width                  width of application (optional)
     * @param       int         $height                 height of application (optional)
     * @param       string      $format                 format of application (optional)
     * @param       string      $technique              technique (optional)
     * @param       string      $logoUrl                logo url (optional)
     * @param       string      $previewUrl             preview url (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string            application item or false
     */
    public function createApplication ( $name = NULL, $version = NULL, $adrank = 0,
        $tags = NULL, $status = 0, $mediaSlotCompatible = false, $inline = false,
        $integrationCode = NULL, $integrationNotes = NULL, $description = NULL,
        $terms = NULL, $connectRole = NULL, $connectId = NULL,
        $connectStatus = NULL, $connectUrl = NULL, $cancelUrl = NULL,
        $documentationUrl = NULL, $companyUrl = NULL, $developer = NULL,
        $pricingShare = 0, $pricingSetup = 0, $pricingMonthly = 0,
        $pricingCurrency = NULL, $pricingDescription = NULL, $startDate = NULL,
        $modifiedDate = NULL, $installableTo = NULL, $applicationType = NULL,
        $width = NULL, $height = NULL, $format = NULL, $technique = NULL,
        $logoUrl = NULL, $previewUrl = NULL )
    {
        $resource = array('applications', 'application');

        $applicationItem['name']                   	= $name;
        $applicationItem['version']                 = $version;
        $applicationItem['adrank']                  = $adrank;
        $applicationItem['tags']                    = $tags;
        $applicationItem['status']                  = $status;
        $applicationItem['mediaSlotCompatible']     = $mediaSlotCompatible;
        $applicationItem['inline']                  = $inline;
        $applicationItem['integrationCode']         = $integrationCode;
        $applicationItem['integrationNotes']        = $integrationNotes;
        $applicationItem['description']             = $description;
        $applicationItem['terms']                   = $terms;
        $applicationItem['connect']['role']         = $connectRole;
        $applicationItem['connect']['connectId']    = $connectId;
        $applicationItem['connect']['status']       = $connectStatus;
        $applicationItem['cancelUrl']               = $cancelUrl;
        $applicationItem['documentationUrl']        = $documentationUrl;
        $applicationItem['companyUrl']              = $companyUrl;
        $applicationItem['developer']               = $developer;
        $applicationItem['pricing']['share']        = $pricingShare;
        $applicationItem['pricing']['setup']        = $pricingSetup;
        $applicationItem['pricing']['monthly']      = $pricingMonthly;
        $applicationItem['pricing']['currency']     = $pricingCurrency;
        $applicationItem['pricing']['description']  = $pricingDescription;
        $applicationItem['startDate']               = $startDate;
        $applicationItem['modifiedDate']            = $modifiedDate;
        $applicationItem['installableTo']           = $installableTo;
        $applicationItem['applicationType']         = $applicationType;
        $applicationItem['size']['width']           = $width;
        $applicationItem['size']['height']          = $height;
        $applicationItem['size']['format']          = $format;
        $applicationItem['technique']               = $technique;
        $applicationItem['logoUrl']                 = $logoUrl;
        $applicationItem['previewUrl']              = $previewUrl;

        $attributes = false;

        $body = $this->serialize('applicationItem', $applicationItem, $attributes);

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
     * Update an application
     *
     * @param       int         $applicationId          application id (mandatory)
     * @param       string      $name                   name (optional)
     * @param       string      $version                version (optional)
     * @param       int         $adrank                 adrank (optional)
     * @param       string      $tags                   tags (optional)
     * @param       int         $status                 status (optional)
     * @param       boolean     $mediaSlotCompatible    compatible to media slot (optional)
     * @param       boolean     $inline
     * @param       string      $integrationCode        integration code (optional)
     * @param       string      $integrationNotes       integration notes (optional)
     * @param       string      $description            description (optional)
     * @param       string      $terms                  terms of service (optional)
     * @param       string      $connectRole            role of the application (optional)
     *                                                  (developer, customer, tester)
     * @param       string      $connectId              connect id (optional)
     * @param       string      $connectStatus          connect status (optional)
     *                                                  (active, inactive)
     * @param       string      $connectUrl             connect url (optional)
     * @param       string      $cancelUrl              cancel url (optional)
     * @param       string      $documentationUrl       documentation url (optional)
     * @param       string      $companyUrl             company url (optional)
     * @param       string      $developer              developer (optional)
     * @param       float       $pricingShare           price for share model (optional)
     * @param       float       $pricingSetup           price for setup (optional)
     * @param       float       $pricingMonthly         price for monthly usage (optional)
     * @param       string      $pricingCurrency        pricing currency (optional)
     * @param       string      $pricingDescription     pricing description (optional)
     * @param       string      $startDate              start date (optional)
     * @param       string      $modifiedDate           modification date (optional)
     * @param       string      $installableTo          who can install the (optional)
     *                                                  application
     *                                                  (advertiser, publisher)
     * @param       string      $applicationType        type of application (optional)
     *                                                  (widget, saas, software)
     * @param       int         $width                  width of application (optional)
     * @param       int         $height                 height of application (optional)
     * @param       string      $format                 format of application (optional)
     * @param       string      $technique              technique (optional)
     * @param       string      $logoUrl                logo url (optional)
     * @param       string      $previewUrl             preview url (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string            application item or false
     */
    public function updateApplication ( $applicationId, $name = NULL,
        $version = NULL, $adrank = 0, $tags = NULL, $status = 0,
        $mediaSlotCompatible = false, $inline = false, $integrationCode = NULL,
        $integrationNotes = NULL, $description = NULL, $terms = NULL,
        $connectRole = NULL, $connectId = NULL, $connectStatus = NULL,
        $connectUrl = NULL, $cancelUrl = NULL, $documentationUrl = NULL,
        $companyUrl = NULL, $developer = NULL, $pricingShare = 0,
        $pricingSetup = 0, $pricingMonthly = 0, $pricingCurrency = NULL,
        $pricingDescription = NULL, $startDate = NULL, $modifiedDate = NULL,
        $installableTo = NULL, $applicationType = NULL, $width = NULL,
        $height = NULL, $format = NULL, $technique = NULL, $logoUrl = NULL,
        $previewUrl = NULL )
    {
        $resource = array('applications', 'application');

        $applicationItem['@id'] = $applicationId;

        if (isset($name))
        {
            $applicationItem['name'] = $name;
        }

        if (isset($version))
        {
            $applicationItem['version'] = $version;
        }

        if (isset($adrank))
        {
            $applicationItem['adrank'] = $adrank;
        }

        if (isset($tags))
        {
            $applicationItem['tags'] = $tags;
        }

        if (isset($status))
        {
            $applicationItem['status'] = $status;
        }

        if (isset($mediaSlotCompatible))
        {
            $applicationItem['mediaSlotCompatible'] = $mediaSlotCompatible;
        }

        if (isset($inline))
        {
            $applicationItem['inline'] = $inline;
        }

        if (isset($integrationCode))
        {
            $applicationItem['integrationCode'] = $integrationCode;
        }

        if (isset($integrationNotes))
        {
            $applicationItem['integrationNotes'] = $integrationNotes;
        }

        if (isset($description))
        {
            $applicationItem['description'] = $description;
        }

        if (isset($terms))
        {
            $applicationItem['terms'] = $terms;
        }

        if (isset($connectRole))
        {
            $applicationItem['connect']['role'] = $connectRole;
        }

        if (isset($connectId))
        {
            $applicationItem['connect']['connectId'] = $connectId;
        }

        if (isset($connectStatus))
        {
            $applicationItem['connect']['status'] = $connectStatus;
        }

        if (isset($cancelUrl))
        {
            $applicationItem['cancelUrl'] = $cancelUrl;
        }

        if (isset($documentationUrl))
        {
            $applicationItem['documentationUrl'] = $documentationUrl;
        }

        if (isset($companyUrl))
        {
            $applicationItem['companyUrl'] = $companyUrl;
        }

        if (isset($developer))
        {
            $applicationItem['developer'] = $developer;
        }

        if (isset($pricingShare))
        {
            $applicationItem['pricing']['share'] = $pricingShare;
        }

        if (isset($pricingSetup))
        {
            $applicationItem['pricing']['setup'] = $pricingSetup;
        }

        if (isset($pricingMonthly))
        {
            $applicationItem['pricing']['monthly'] = $pricingMonthly;
        }

        if (isset($pricingCurrency))
        {
            $applicationItem['pricing']['currency'] = $pricingCurrency;
        }

        if (isset($pricingDescription))
        {
            $applicationItem['pricing']['description'] = $pricingDescription;
        }

        if (isset($startDate))
        {
            $applicationItem['startDate'] = $startDate;
        }

        if (isset($modifiedDate))
        {
            $applicationItem['modifiedDate'] = $modifiedDate;
        }

        if (isset($installableTo))
        {
            $applicationItem['installableTo'] = $installableTo;
        }

        if (isset($applicationType))
        {
            $applicationItem['applicationType'] = $applicationType;
        }

        if (isset($width))
        {
            $applicationItem['size']['width'] = $width;
        }

        if (isset($height))
        {
            $applicationItem['size']['height'] = $height;
        }

        if (isset($format))
        {
            $applicationItem['size']['format'] = $format;
        }

        if (isset($technique))
        {
            $applicationItem['technique'] = $technique;
        }

        if (isset($logoUrl))
        {
            $applicationItem['logoUrl'] = $logoUrl;
        }

        if (isset($previewUrl))
        {
            $applicationItem['previewUrl'] = $previewUrl;
        }

        $attributes = false;

        $body = $this->serialize('applicationItem', $applicationItem, $attributes);

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
     * Delete application.
     *
     * @param       int         $applicationId      application id (mandatory)
     *
     * @access      public
     * @category    signature
     *
     * @return      boolean                true on success
     */
    public function deleteApplication ( $applicationId )
    {
        $resource = array('applications', 'application', $applicationId);

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
     * Get single setting.
     *
     * @param       int         $applicationId  application id (mandatory)
     * @param       int         $mediaslotId    media slot id (optional)
     * @param       key         $key            application specific key
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string            application item or false
     */
    public function getSetting ( $applicationId, $mediaslotId = NULL, $key )
    {
        $resource = array('applications', 'settings', 'application', $applicationId, 'key', $key );

        $parameter['mediaslotid'] = $mediaslotId;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(false);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }
    }



    /**
     * Get settings.
     *
     * @param       int         $applicationId      application id (mandatory)
     * @param       int         $mediaslotId        media slot id (optional)
     * @param       int         $page               page of result set (optional)
     * @param       int         $items              items per page (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                list of settings or false
     */
    public function getSettings ( $applicationId, $mediaslotId = NULL,
        $page = 0, $items = 0 )
    {
        $resource = array('applications', 'settings', 'application', $applicationId);

        $parameter['mediaslotId']   = $mediaslotId;

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
     * Create setting
     *
     * @param       int         $applicationId      application id (mandatory)
     * @param       int         $mediaslotId        media slot id (optional)
     * @param       string      $key                settings key (mandatory)
     * @param       string      $value              settings value (optional)
     * @param       string      $customValue        settings custom value (optional)
     * @param       string      $type               settings type (optional)
     *                                              (boolean, color, number,
     *                                               string, date)
     * @param       string      $name               settings name (optional)
     * @param       string      $description        settings description (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                setting
     */
    public function createSetting ( $applicationId, $mediaslotId = NULL, $key,
        $value, $customValue, $type = NULL, $name = NULL, $description = NULL )
    {
        $resource = array('applications', 'settings', 'application', $applicationId);

        $settingItem['application']['@id']      = $applicationId;
        $settingItem['application']['#text']    = "asd";
        $settingItem['key']                     = $key;
        $settingItem['value']                   = $value;

        if ($mediaslotId != NULL)
        {
            $settingItem['mediaslot']['@id']    = $mediaslotId;
            $settingItem['mediaslot']['#text']  = "asd";
        }

        if ($customValue != NULL)
        {
            $settingItem['customValue'] = $customValue;
        }

        if ($type != NULL)
        {
            $settingItem['type'] = $type;
        }

        if ($name != NULL)
        {
            $settingItem['name'] = $name;
        }

        if ($description != NULL)
        {
            $settingItem['description'] = $description;
        }

        $body = $this->serialize('settingItem', $settingItem);

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
     * Update setting
     *
     * @param       int         $applicationId      application id (mandatory)
     * @param       int         $mediaslotId        media slot id (optional)
     * @param       string      $key                settings key (mandatory)
     * @param       string      $value              settings value (optional)
     * @param       string      $customValue        settings custom value (optional)
     * @param       string      $type               settings type (optional)
     *                                              (boolean, color, number,
     *                                               string, date)
     * @param       string      $name               settings name (optional)
     * @param       string      $description        settings description (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                setting
     */
    public function updateSetting ( $applicationId, $mediaslotId = NULL, $key,
        $value, $customValue, $type = NULL, $name = NULL, $description = NULL )
    {
        $resource = array('applications', 'settings', 'application', $applicationId);

        $settingItem['application']['@id']      = $applicationId;
        $settingItem['application']['#text']    = "";
        $settingItem['key']                     = $key;
        $settingItem['value']                   = $value;

        if ($mediaslotId != NULL)
        {
            $settingItem['mediaslot']['@id']    = $mediaslotId;
            $settingItem['mediaslot']['#text']  = "";
        }

        if ($customValue != NULL)
        {
            $settingItem['customValue'] = $customValue;
        }

        if ($type != NULL)
        {
            $settingItem['type'] = $type;
        }

        if ($name != NULL)
        {
            $settingItem['name'] = $name;
        }

        if ($description != NULL)
        {
            $settingItem['description'] = $description;
        }

        $body = $this->serialize('settingItem', $settingItem);

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
     * Delete setting
     *
     * @param       int         $applicationId      application id (mandatory)
     * @param       string      $mediaslotId        mediaslot id (optional)
     * @param       string      $key                settings key (mandatory)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                setting
     */
    public function deleteSetting ( $applicationId, $mediaslotId, $key )
    {
        $resource = array('applications', 'settings', 'application', $applicationId, 'key', $key);

        $this->setRestfulAction(DELETE);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get media slots.
     *
     * @param       int         $mediaslotId            media slot id (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                    media slot object or false
     */
    public function getMediaSlot ( $mediaslotId )
    {
        $resource = array('mediaslots', 'mediaslot', $mediaslotId);

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
     * Get media slots.
     *
     * @param       int         $adspaceId              advertising space id (optional)
     * @param       int         $width                  width of application (optional)
     * @param       int         $height                 height of application (optional)
     * @param       string      $format                 format of application (optional)
     * @param       int         $page                   page of result set (optional)
     * @param       int         $items                  items per page (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                    list of media slot objects or false
     */
    public function getMediaSlots ( $adspaceId, $width = 0, $height = 0,
        $format = NULL, $page = 0, $items = 0 )
    {
        $resource = array('mediaslots');

        $parameter['adspaceId']         = $adspaceId;
        $parameter['size']['width']     = $width;
        $parameter['size']['height']    = $height;
        $parameter['size']['format']    = $format;
        $parameter['page']              = $page;
        $parameter['items']             = $items;

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
     * Create media slot.
     *
     * @param       string      $name                   media slot name (mandatory)
     * @param       int         $adspaceId              adspace id (mandatory)
     * @param       string      $adspaceName            name of the adspace (optional)
     * @param       string      $applicationId          application id (mandatory)
     * @param       string      $applicationName        name of the application (optional)
     * @param       string      $status                 media slot status (mandatory)
     *                                                  (active, deleted)
     * @param       int         $width                  width of application (optional)
     * @param       int         $height                 height of application (optional)
     * @param       string      $format                 format of application (optional)
     * @param       string      $createDate             create date (optional)
     * @param       string      $modifiedDate           modified date (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                    list of media slot objects or false
     */
    public function createMediaSlot ( $name, $adspaceId, $adspaceName = NULL,
        $applicationId, $applicationName = NULL, $status = NULL, $width = 0,
        $height = 0, $format = NULL, $createDate = NULL, $modifiedDate = NULL )
    {
        $resource = array('mediaslots', 'mediaslot');

        $mediaSlotItem['name']                  = $name;
        $mediaSlotItem['adspace']['@id']        = $adspaceId;
        $mediaSlotItem['adspace']['#text']      = $adspaceName;
        $mediaSlotItem['application']['@id']    = $applicationId;
        $mediaSlotItem['application']['#text']  = $applicationName;
        $mediaSlotItem['status']                = $status;
        $mediaSlotItem['size']['width']         = $width;
        $mediaSlotItem['size']['height']        = $height;
        $mediaSlotItem['size']['format']        = $format;
        $mediaSlotItem['createDate']            = $createDate;
        $mediaSlotItem['modifiedDate']          = $modifiedDate;

        $body = $this->serialize('mediaSlotItem', $mediaSlotItem);

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
     * Update media slot.
     *
     * @param       int         $mediaslotId            media slot id (mandatory)
     * @param       string      $name                   media slot name (mandatory)
     * @param       int         $adspaceId              adspace id (mandatory)
     * @param       string      $adspaceName            name of the adspace (optional)
     * @param       string      $applicationId          application id (mandatory)
     * @param       string      $applicationName        name of the application (optional)
     * @param       string      $status                 media slot status (mandatory)
     *                                                  (active, deleted)
     * @param       int         $width                  width of application (optional)
     * @param       int         $height                 height of application (optional)
     * @param       string      $format                 format of application (optional)
     * @param       string      $createDate             create date (optional)
     * @param       string      $modifiedDate           modified date (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                    list of media slot objects or false
     */
    public function updateMediaSlot ( $mediaslotId, $name, $adspaceId,
        $adspaceName = NULL, $applicationId, $applicationName = NULL,
        $status = NULL, $width = 0, $height = 0, $format = NULL,
        $createDate = NULL, $modifiedDate = NULL )
    {
        $resource = array('mediaslots', 'mediaslot');

        $mediaSlotItem['name']                  = $name;
        $mediaSlotItem['adspace']['@id']        = $adspaceId;
        $mediaSlotItem['adspace']['#text']      = $adspaceName;
        $mediaSlotItem['application']['@id']    = $applicationId;
        $mediaSlotItem['application']['#text']  = $applicationName;
        $mediaSlotItem['status']                = $status;
        $mediaSlotItem['size']['width']         = $width;
        $mediaSlotItem['size']['height']        = $height;
        $mediaSlotItem['size']['format']        = $format;
        $mediaSlotItem['createDate']            = $createDate;
        $mediaSlotItem['modifiedDate']          = $modifiedDate;

        $attributes['id'] = $mediaslotId;

        $body = $this->serialize('mediaSlotItem', $mediaSlotItem, $attributes);

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
     * Delete media slot.
     *
     * @param       int         $mediaslotId            media slot id (mandatory)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                    true if success
     */
    public function deleteMediaSlot ( $mediaslotId )
    {
        $resource = array('mediaslots', 'mediaslot', $mediaslotId);

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
     * done
     *
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
     * done
     *
     * Get advertiser program categories.
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            category result set or false
     */
    public function getProgramCategories ()
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
     * done
     *
     * Search zanox advertiser programs.
     *
     * @param      string      $query          search string
     * @param      string      $startDate      program start date (optional)
     * @param      string      $partnerShip    partnership status (optional)
     *                                         (direct or indirect)
     * @param      boolean     $hasProducts    program has product data
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
    public function searchPrograms ( $query = NULL, $startDate = NULL,
        $partnerShip = NULL, $hasProducts = false, $region = NULL,
        $categoryId = NULL, $page = 0, $items = 10 )
    {
        $resource = array('programs');

        $parameter['q']           = $query;
        $parameter['startDate']   = $startDate;
        $parameter['partnerShip'] = $partnerShip;
        $parameter['hasProducts'] = $hasProducts;
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
     * 404 error
     *
     * Get advertiser program applications for a user.
     *
     * @param      int         $programId       restrict results to applications (optional)
     *                                          to the id of this program (optional)
     * @param      int         $adspaceId       advertising space id (optional)
     * @param      string      $status          restrict results to program applications
     *                                          with this status:
     *                                          "open", "confirmed", "rejected",
     *                                          "deferred", "waiting", "blocked",
     *                                          "terminated", "canceled", "called",
     *                                          "declined", "deleted"
     * @param      int         $page            page of result set (optional)
     * @param      int         $items           items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            program result set or false
     */
    public function getProgramApplications ( $programId = NULL,
        $adspaceId = NULL, $status = NULL, $page = 0, $items = 10 )
    {
        $resource = array('programapplications');

        $parameter['adspace']      = $adspaceId;
        $parameter['program']      = $programId;
        $parameter['status']       = $status;
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
    public function createProgramApplication ( $programId, $adspaceId )
    {
    	$resource = array('programapplications', 'program', $programId, 'adspace', $adspaceId);

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
     * Get sales report.
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

        $parameter['datetype'] = $dateType;
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
     * Get leads report.
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

        $parameter['datetype'] = $dateType;
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
    public function getReportBasic ( $fromDate, $toDate, $dateType = NULL,
        $currency = NULL, $programId = NULL, $admediumId = NULL,
        $admediumFormat = NULL, $adspaceId = NULL, $reviewState = NULL,
        $groupBy = NULL )
    {
        $resource = array('reports', 'basic');

        $parameter['fromdate']       = $fromDate;
        $parameter['todate']         = $toDate;
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
    public function getPayments ( $page = 0, $items = 10 )
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
    public function getPayment ( $paymentId )
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
     * Get account balance
     *
     * @param      int         $currency       currence code of balance account
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            payment item or false
     */
    public function getBalance ( $currency )
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
    public function getBalances ( $page = 0, $items = 10 )
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
    public function getBankAccounts ( $page = 0, $items = 10 )
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
    public function getBankAccount ( $bankAccountId )
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
     * Returns a single advertising spaces.
     *
     * @param      int         $adspaceId      advertising space id
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            adspace item or false
     */
    public function getAdspace ( $adspaceId )
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
     * @param      string      $language       language of adspace (e.g. en)
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
     * @param      int         $checkNumber
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            adspace item or false
     */
    public function createAdspace ( $name, $language, $url, $contact, $description,
        $adspaceType, $scope, $visitors, $impressions, $keywords = NULL,
        $regions = array(), $categories = array(), $checkNumber )
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
        $adspaceItem['checkNumber'] = $checkNumber;
        $adspaceItem['language']    = $language;

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
     * @param      string      $language       language of adspace (e.g. en)
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
     * @param      int         $checkNumber
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            adspace item or false
     */
    public function updateAdspace ( $adspaceId, $name, $language, $url, $contact,
        $description, $adspaceType, $scope, $visitors, $impressions,
        $keywords = NULL, $regions = array(), $categories = array(), $checkNumber )
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
        $adspaceItem['language']    = $language;

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
    public function deleteAdspace ( $adspaceId )
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
    public function getProfile ()
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
     * @param      array        $profileId      user profile id
     * @param      string       $loginName      login name
     * @param      string       $userName       user name
     * @param      string       $firstName      first name
     * @param      string       $lastName       last name
     * @param      string       $email          email address
     * @param      string       $country        country or residence
     * @param      string       $street1        street 1
     * @param      string       $street2        street 2 (optional)
     * @param      string       $city           city
     * @param      string       $company        name of company (optional)
     * @param      string       $phone          phone number (optional)
     * @param      string       $mobile         mobile number (optional)
     * @param      string       $fax            fax number (optional)
     * @param      boolean      $isAdvertiser   is Advertiser account
     * @param      boolean      $isSublogin     is Sublogin account
     *
     * @access     public
     * @category   signature
     *
     * @return     boolean                     true on success
     */
    public function updateProfile ( $profileId, $loginName, $userName,
        $firstName = NULL, $lastName = NULL, $email = NULL, $country = NULL,
        $street1 = NULL, $street2 = NULL, $city = NULL, $zipcode = NULL,
        $company = NULL, $phone = NULL, $mobile = NULL, $fax = NULL,
        $isAdvertiser, $isSublogin )
    {
        $resource = array('profiles');

        $profileItem['loginName']       = $loginName;
        $profileItem['userName']        = $userName;
        $profileItem['isAdvertiser']    = $isAdvertiser;
        $profileItem['isSublogin']      = $isSublogin;

        if ($firstName != NULL) $profileItem['firstName'] = $firstName;
        if ($lastName != NULL)  $profileItem['lastName']  = $lastName;
        if ($email != NULL)     $profileItem['email']     = $email;
        if ($country != NULL)   $profileItem['country']   = $country;
        if ($street1 != NULL)   $profileItem['street1']   = $street1;
        if ($street2 != NULL)   $profileItem['street2']   = $street2;
        if ($city != NULL)      $profileItem['city']      = $city;
        if ($zipcode != NULL)   $profileItem['zipcode']   = $zipcode;
        if ($company != NULL)   $profileItem['company']   = $company;
        if ($phone != NULL)     $profileItem['phone']     = $phone;
        if ($mobile != NULL)    $profileItem['mobile']    = $mobile;
        if ($fax != NULL)       $profileItem['fax']       = $fax;

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
    public function getUiUrl ( $connectId, $sessionKey )
    {
        throw new ApiClientException("Restful API Interface doesn't
            support getUiUrl()! Please use the SOAP Interface.");
    }


    /**
     * Get tracking categories for ad space; if not program member, returns program's default categories
     * NOTE: not yet implemented with REST protocol!!!!
     *
     * @param      int         $adspaceId      adspace id (mandatory)
     * @param      int         $programId      advertiser program id (mandatory)     
     * @param      int         $page           result set page (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            program result set of trackingCategoryItems
     */
    public function getTrackingCategories ( $adspaceId, $programId, $page = 0, $items = 50 )
    {
        /*
        $resource = array('trackingcategories');

        $parameter['adspaceId']    = $adspaceId;
        $parameter['programId']    = $programId;
        $parameter['page']         = $page;
        $parameter['items']        = $items;

        $this->setRestfulAction(GET);
        $this->setSecureApiCall(true);

        $result = $this->doRestfulRequest($resource, $parameter);

        if ( $result )
        {
            return $result;
        }
			*/
        return false;
    }

}

?>
