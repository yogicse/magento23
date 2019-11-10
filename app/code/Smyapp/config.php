<?php

  //start session in all pages
  if (session_status() == PHP_SESSION_NONE) { session_start(); } //PHP >= 5.4.0
  //if(session_id() == '') { session_start(); } //uncomment this line if PHP < 5.4.0 and comment out line above

	// sandbox or live
	define('PPL_MODE', 'sandbox');

	if(PPL_MODE=='sandbox'){
		
		define('PPL_API_USER', 'Smyapp.xolo-facilitator_api1.gmail.com');
		define('PPL_API_PASSWORD', 'GNBWPHLBCP5LA9XZ');
		define('PPL_API_SIGNATURE', 'AFcWxV21C7fd0v3bYYYRCpSSRl31A1n9mGle-oGv7sbx3H.Y5CjVmWwN');
	}
	else{
		
		define('PPL_API_USER', 'somepaypal_api.yahoo.co.uk');
		define('PPL_API_PASSWORD', '123456789');
		define('PPL_API_SIGNATURE', 'opupouopupo987kkkhkixlksjewNyJ2pEq.Gufar');
	}
	
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/app/bootstrap.php';
$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$obj = $bootstrap->getObjectManager();
$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');
   
$objectManager =  \Magento\Framework\App\ObjectManager::getInstance();        
 	
$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
$store = $storeManager->getStore();


 $baseurl=$store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB); 



	define('PPL_LANG', 'EN');
	
	define('PPL_LOGO_IMG', 'http://url/to/site/logo.png');
	
	define('PPL_RETURN_URL', $baseurl.'process.php');
	define('PPL_CANCEL_URL', $baseurl.'cancel_url.php');

	define('PPL_CURRENCY_CODE', 'HKD');
