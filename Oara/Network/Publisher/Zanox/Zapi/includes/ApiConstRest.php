<?php

/**
 * Api Constants Enum Definitions for the REST interface
 *
 * Supported Version: PHP >= 5.1.0
 *
 * @author      Stefan Misch (stefan.misch@zanox.com)
 *
 * @see         http://wiki.zanox.com/en/Web_Services
 * @see         http://apps.zanox.com
 *
 * @package     ApiClient
 * @version     2011-03-01
 * @copyright   Copyright (c) 2007-2011 zanox.de AG
 *
 */



/**
 * applicationTypeEnum
 */
		define('WIDGET', 'widget');
		define('SAAS', 'saas');
		define('SOFTWARE', 'software');

/**
 * profileTypeEnum
 */
		define('PUBLISHER', 'publisher');
		define('ADVERTISER', 'advertiser');

/**
 * programStatusEnum
 */
		define('ACTIVE', 'active');
		define('INACTIVE', 'inactive');

/**
 * programApplicationStatusEnum
 */
		define('OPEN', 'open');
		define('CONFIRMED', 'confirmed');
		define('REJECTED', 'rejected');
		define('DEFERRED', 'deferred');
		define('WAITING', 'waiting');
		define('BLOCKED', 'blocked');
		define('TERMINATED', 'terminated');
		define('CANCELED', 'canceled');
		define('CALLED', 'called');
		define('DECLINED', 'declined');
		define('DELETED', 'deleted');

/**
 * admediaPurposeEnum
 */
		define('START_PAGE', 'start_page');
		define('PRODUCT_DEEPLINK', 'product_deeplink');
		define('CATEGORY_DEEPLINK', 'category_deeplink');
		define('SEARCH_DEEPLINK', 'SEARCh_DEEPLINK');

/**
 * adspaceTypeEnum
 */
		define('WEBSITE', 'website');
		define('EMAIL', 'email');
		define('SEARCH_ENGINE', 'search_engine');

/**
 * adspaceScopeEnum
 */
		define('PRIVATE', 'private');
		define('BUSINESS', 'business');

/**
 * reviewStateEnum
 */
		#define('CONFIRMED', 'confirmed');
		#define('OPEN', 'open');
		#define('REJECTED', 'rejected');
		define('APPROVED', 'approved');

/**
 * admediaTypeEnum
 */
		define('HTML', 'html');
		define('SCRIPT', 'script');
		define('LOOKAT_MEDIA', 'lookat_media');
		define('IMAGE', 'image');
		define('IMAGE_TEXT', 'image_text');
		define('TEXT', 'text');

/**
 * searchTypeEnum
 */
		define('CONTEXTUAL', 'contextual');
		define('PHRASE', 'phrase');

/**
 * partnerShipEnum
 */
		define('DIRECT', 'direct');
		define('INDIRECT', 'indirect');

/**
 * dateTypeEnum
 */
		define('CLICK_DATE', 'click_date');
		define('TRACKING_DATE', 'tracking_date');
		define('MODIFIED_DATE', 'modified_date');
		define('REVIEW_STATE_CHANGED_DATE', 'review_state_changed_date');

/**
 * groupByEnum
 */
		define('CURRENCY', 'currency');
		define('ADMEDIUM', 'admedium');
		define('PROGRAM', 'program');
		define('ADSPACE', 'adspace');
		define('LINK_FORMAT', 'link_format');
		define('REVIEW_STATE', 'review_state');
		define('TRACKING_CATEGORY', 'tracking_category');
		define('MONTH', 'month');
		define('DAY', 'day');
		define('YEAR', 'year');
		define('DAY_OF_WEEK', 'day_of_week');
		define('APPLICATION', 'application');
		define('MEDIA_SLOT', 'media_slot');

/**
 * incentiveTypeEnum
 */
		define('COUPONS', 'coupons');
		define('SAMPLES', 'samples');
		define('BARGAINS', 'bargains');
		define('FREE_PRODUCTS', 'free_products');
		define('NO_SHIPPING_COSTS', 'no_shipping_costs');
		define('LOTTERIES', 'lotteries');
		
		
/**
 * roleTypeEnum
 */

		define('DEVELOPER', 'developer');
		define('CUSTOMER', 'customer');
		define('TESTER', 'tester');

/**
 * settingTypeEnum
 */

		define('BOOLEAN', 'boolean');
		define('COLOR', 'color');
		define('NUMBER', 'number');
		define('STRING', 'string');
		define('DATE', 'date');

/**
 * connectStatusTypeEnum
 */

		#define('ACTIVE', 'active');
		#define('INACTIVE', 'inactive');
		

/**
 * mediaSlotStatusEnum
 */
		#define('ACTIVE', 'active');
		#define('DELETED', 'deleted');

/**
 * transactionTypeEnum
 */
		define('LEADS', 'leads');
		define('SALES', 'sales');




?>