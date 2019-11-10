<?php
namespace Smyapp\Connector\Controller\cart;

class Getshippingmethods extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Cart $checkoutCart,
        \Magento\Directory\Model\Currency $currency,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->checkoutCart      = $checkoutCart;
        $this->currency          = $currency;
        $this->_checkoutSession  = $checkoutSession;
        $this->customHelper      = $customHelper;
        $this->_customerSession  = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function getCheckOutSession()
    {
        return $this->_checkoutSession;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId   = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId    = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency  = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $countryId       = $this->getRequest()->getParam('country_id');
        $postCode        = $this->getRequest()->getParam('zipcode');
        $currentCurrency = $this->currency;

        $session = $this->getCheckOutSession();
        $address = $session->getQuote()->getShippingAddress();
        $address->setCountryId($countryId)
            ->setPostcode($postCode)
            ->setSameAsBilling(1);

        $rates = $address
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->getGroupedAllShippingRates();


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 
         
         $subTotal = $cart->getQuote()->getSubtotal();
        $grandTotal = $cart->getQuote()->getGrandTotal();


// get quote items collection
$itemsCollection = $cart->getQuote()->getItemsCollection();
 
// get array of all items what can be display directly
$itemsVisible = $cart->getQuote()->getAllVisibleItems();
 
// get quote items array
$items = $cart->getQuote()->getAllItems();
 $emptyarray=[];
foreach($items as $item) {

    $cartdata=array(
    'sku' => $item->getSku(),
    'qty' => $item->getQty(),
  
    );
    // echo 'ID: '.$item->getProductId().'<br />';
    // echo 'Name: '.$item->getName().'<br />';
    // echo 'Sku: '.$item->getSku().'<br />';
    // echo 'Quantity: '.$item->getQty().'<br />';
    // echo 'Price: '.$item->getPrice().'<br />';
    // echo "<br />"; 
array_push($emptyarray, $cartdata);
}



         
    $data = array(
    'CartItems' => $emptyarray,
    'CartPrice' => $grandTotal
);
 
$payload = json_encode($data);


 
// Prepare new cURL resource
$ch = curl_init('https://member.peoplesmarket.hk/app-shipping');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLINFO_HEADER_OUT, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
 
// Set HTTP Header for POST request 
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload))
);
 
// Submit the POST request
$result1 = curl_exec($ch);


  $apiArray = json_decode($result1, true);
// print_r($apiArray);
// die('dfs');
 
// Close cURL session handle
curl_close($ch);


        $shipMethods = [];
        foreach ($rates as $carrier) {
            foreach ($carrier as $rate) {
                if($apiArray['tablerate'] == 1 && $apiArray['pitckup'] == 1){
                 
                $shipMethods[] = array(
                    'code'  => $rate->getData('code'),
                    'value' => $rate->getData('carrier_title'),
                    'price' => $rate->getData('price'),
                );
           
                } elseif($apiArray['tablerate'] == 0) {
                    if($rate->getData('code')=="storepickup_storepickup"){
                $shipMethods[] = array(
                    'code'  => $rate->getData('code'),
                    'value' => $rate->getData('carrier_title'),
                    'price' => $rate->getData('price'),
                );
            }
            } else {
                $shipMethods[] = array(
                    'code'  => $rate->getData('code'),
                    'value' => $rate->getData('carrier_title'),
                    'price' => $rate->getData('price'),
                );

            }
                
               
             }
        }
        
        //print_r($shipMethods);
       // die('adfas');


        $result->setData($shipMethods);
        return $result;
    }
}
