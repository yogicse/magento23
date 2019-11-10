<?php

namespace Smyapp\Connector\Controller\cart;

class PlaceOrder extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\Data\Form\FormKey $formkey,
        \Magento\Quote\Model\QuoteFactory $quote,
        // \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Service\OrderService $orderService,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Checkout\Model\Cart $checkoutCart,
        \Magento\Checkout\Helper\Cart $checkoutCartHelper,
        \Magento\Customer\Model\Address $customerAddress,
        \Magento\Checkout\Model\Session $checkoutSession,
        // \Magento\Sales\Model\Quote  $saleQuote,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Quote\Model\Quote $quotes,
        \Magento\Quote\Model\QuoteIdMaskFactory $QuoteIdMaskFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        //\Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Sales\Model\Order $order
    ) {
        $this->storeManager = $storeManager;
        $this->product = $product;
        $this->formkey = $formkey;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->customHelper = $customHelper;
        $this->checkoutCart = $checkoutCart;
        $this->checkoutCartHelper = $checkoutCartHelper;
        $this->customerAddress = $customerAddress;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customer = $customer;
        $this->quotes = $quotes;
        $this->QuoteIdMaskFactory = $QuoteIdMaskFactory;
        $this->quoteRepository = $quoteRepository;
        $this->_eventManager = $eventManager;
        $this->request = $context->getRequest();
        $this->jsonHelper = $jsonHelper;
        $this->order = $order;
        /*   $this->saleQuote = $saleQuote;*/
        parent::__construct($context);
    }
    public function execute()
    {
        //$this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $result = $this->resultJsonFactory->create();

        if ($this->customerSession->isLoggedIn()) {
            $session = $this->customerSession;
            $customerId = $session->getId();
            $totalItems = $this->checkoutCartHelper->getSummaryCount();
            if ($totalItems > 0) {

                $params = file_get_contents("php://input");
                $finalJosn = $this->jsonHelper->jsonDecode($params, true);

                $usershippingid = $finalJosn['usershippingid'];
                $userbillingid = $finalJosn['userbillingid'];
                $shipping_method = $finalJosn['shippingmethod'];
                $paymentmethod = $finalJosn['paymentmethod'];
                // $registration_id =   $finalJosn['registration_id'] ?: null;
                $deviceType = $finalJosn['deviceType'] ?: 0;

                if (!isset($registration_id)) {
                    $registration_id = "";
                } else {
                    $registration_id = $finalJosn['registration_id'] ?: null;
                }

                /*  $card_details =   $finalJosn['card_details'];
                $save_cc = $finalJosn['save_cc'];*/

                /*    if($paymentmethod == 'authorizenet')
                $this->validateCarddtails(json_decode($card_details,1));*/
                if (!\Zend_Validate::is($usershippingid, 'NotEmpty')) {
                    $result->setData(array('Status' => 'error', 'message' => (__('Please select address.'))));
                    return $result;
                }
                if (!\Zend_Validate::is($userbillingid, 'NotEmpty')) {
                    $result->setData(array('Status' => 'error', 'message' => (__('Please select address.'))));
                    return $result;
                }
                if (!\Zend_Validate::is($shipping_method, 'NotEmpty')) {
                    $result->setData(array('Status' => 'error', 'message' => (__('Please select shipping method.'))));
                    return $result;
                }
                if (!\Zend_Validate::is($paymentmethod, 'NotEmpty')) {
                    $result->setData(array('status' => 'error', 'message' => (__('Please select payment method.'))));
                    return $result;
                }
                if ($usershippingid == '' && $userbillingid == '') {
                    $result->setData(array('status' => 'error', 'message' => (__('Please select address.'))));
                    return $result;
                }

                $customers = $this->customerSession->getCustomer()->getId();
                try {
                    $usershippingidData = $this->customerAddress->load($usershippingid)->getData();
                    $userbillingidData = $this->customerAddress->load($userbillingid)->getData();
                    $quote = $this->checkoutSession->getQuote();
                    $quote->setMms_order_type('app');
                    $quote->setDeviceData(json_encode(['device_registraton' => $registration_id, 'device_type' => $deviceType]))->save();

                    if (isset($finalJosn['storelocator_id'])) {
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $storelocator = $objectManager->create('Magedelight\Storepickup\Model\ResourceModel\Storelocator\Collection')->addFieldToFilter('storelocator_id', $finalJosn['storelocator_id']);

                        foreach ($storelocator as $rowdata) {

                            $om = \Magento\Framework\App\ObjectManager::getInstance();
                            $customerSession = $om->create('Magento\Customer\Model\Session');

                            $shippingAddress = array(
                                'firstname' => $customerSession->getCustomer()->getfirstname(),
                                'lastname' => $customerSession->getCustomer()->getlastname(),
                                'email' => $customerSession->getCustomer()->getEmail(),
                                'street' => $rowdata->getaddress(),
                                'city' => $rowdata->getcity(),
                                'postcode' => $rowdata->getzipcode(),
                                'country_id' => $rowdata->getcountry(),
                                'state' => $rowdata->getstate(),
                                'region_id' => $rowdata->getregion_id(),
                                'telephone' => $rowdata->gettelephone(),
                                'customer_password' => '',
                                'confirm_password' => '',
                                'save_in_address_book' => '0',
                                'is_default_shipping' => 1,
                                'is_default_billing' => 0,
                            );

                            $shippingAddress = $quote->getShippingAddress()->addData($shippingAddress);
                        }
                    } else {
                        $shippingAddress = $quote->getShippingAddress()->addData($usershippingidData);
                    }

                    $billingAddress = $quote->getBillingAddress()->addData($userbillingidData);

                    $shippingAddress->setCollectShippingRates(true)->collectShippingRates()

                        ->setShippingMethod($shipping_method);

                    $shippingAddress->setPaymentMethod($paymentmethod);
                    $quote->getPayment()->importData(array('method' => $paymentmethod));

                    $quote->collectTotals()->save();
                    //

                    $order = $this->quoteManagement->submit($quote);

                    $order->setEmailSent(0);
                    if ($order->getCanSendNewEmailFlag()) {
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
                        $emailSender->send($order);
                        $order->setEmailSent(1);
                    }



                    $itemcount = $order->getTotalItemCount();
                    $grandTotal = $order->getData('grand_total');
                    
                    $order->setpickup_date(@$finalJosn['pickup_date']);
                    $order->setpickup_store(@$finalJosn['storelocator_id']);
                    $increment_id = $order->getRealOrderId();
                    $order->setmms_order_type('app');
                    $order->setDeviceData(json_encode(['device_registraton' => $registration_id, 'device_type' => $deviceType]))->save();
                    $cart = $this->checkoutCart;
                    if ($cart->getQuote()->getItemsCount()) {
                        $current_cart = $this->checkoutCart;
                        $current_cart->truncate();
                        $current_cart->save();
                    }
                    $allItems = $this->checkoutSession->getQuote()->getAllVisibleItems();
                    foreach ($allItems as $item) {
                        $itemId = $item->getItemId(); //item id of particular item
                        //    $quoteItem=$this->getItemModel()->load($itemId);//load particular item which you want to delete by his item id
                        $this->cart->removeItem($itemId)->save();
                    }

                    //$this->_eventManager->dispatch('place_order_api_connector', ['order' => $order, 'device_type' => $deviceType, 'device_registraton' => $registration_id]);
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $order_data = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($increment_id);
                   

            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $tableName = $resource->getTableName('sales_order_payment');
            if(isset($finalJosn['token'])){
                $sql = "Select * FROM paypal WHERE TRANSACTIONID='".$finalJosn['token']."' ";
            $resultdata = $connection->fetchRow($sql);
           
            $sql1 = "Update " . $tableName . " Set PAYERID = '".$resultdata['PAYERID']."',EMAIL = '".$resultdata['EMAIL']."',PAYERSTATUS = '".$resultdata['PAYERSTATUS']."',ADDRESSSTATUS = '".$resultdata['ADDRESSSTATUS']."',CORRELATIONID = '".$resultdata['CORRELATIONID']."' ,TRANSACTIONID = '".$resultdata['TRANSACTIONID']."' where parent_id = ".$order_data->getEntityId()."";
            $connection->query($sql1);
            }
            

                  // $additionalInformation = $order->getPayment()->setData('bankowner', 'dfhfdhdfhfdhfdh');;
                   

                        //  $payment->setAdditionalInformation($order_array);
                        // $order_data->save();
                   
                   // $shippingmethod1=$order->getShippingAddress()->getShippingMethod();


                    $finalResult = [
                        'message' => ('Order placed successfully.'),
                        'orderid' => $order->getRealOrderId(),
                        'items_count' => $itemcount,
                        'grand_total' => $grandTotal,
                        'status' => 'success',
                    ];

                    /* this will only work if the payu is enabled for app*/
                    if ($paymentmethod == 'payu') {
                        $finalResult['checkout_request'] = $this->buildCheckoutRequest($order);
                        $finalResult['url'] = $this->getCgiUrl();
                    }
                    /* this will only work if the payu is enabled for app*/

                    return $result->setData($finalResult);
                } catch (\Exception $e) {
                    $result->setData(['status' => 'error', 'message' => $e->getMessage()]);
                    return $result;
                }
            } else {
                $result->setData(['message' => 'cart is empty', 'status' => 'success']);
                return $result;
            }
        } else {

            ini_set('memory_limit', '128M');

            $params = file_get_contents("php://input");
            $finalJosn = $this->jsonHelper->jsonDecode($params, true);
            // print_r($finalJosn); die("dfs");
            $billingJosn = $finalJosn['data'][0];
            $shippingJosn = $finalJosn['data'][1];
            $paymentmethod = $finalJosn['paymentmethod'];
            $shipping_method = $finalJosn['shippingmethod'];
            /*$card_details    = $finalJosn['cards_details'];
            $save_cc         = $finalJosn['save_cc'];*/
            //$registration_id =   $finalJosn['registration_id'] ?: null;
            if (!isset($registration_id)) {
                $registration_id = "";
            } else {
                $registration_id = $finalJosn['registration_id'] ?: null;
            }
            $deviceType = $finalJosn['deviceType'] ?: 0;
            try {

                $checkout_session = $this->checkoutSession->getQuoteId();

                $quote = $this->quotes->load($checkout_session);

                $quote->setStoreId($this->storeManager->getStore()->getId());
                $billingAddress = array(
                    'firstname' => $billingJosn['firstname'],
                    'lastname' => $billingJosn['lastname'],
                    'email' => $billingJosn['email'],
                    'street' => array(
                        $billingJosn['street'],
                    ),
                    'city' => $billingJosn['city'],
                    'postcode' => $billingJosn['postcode'],
                    'country_id' => $billingJosn['country_id'],
                    'telephone' => $billingJosn['telephone'],
                    'customer_password' => '',
                    'confirm_password' => '',
                    'save_in_address_book' => '0',
                    'is_default_shipping' => $billingJosn['is_default_shipping'],
                    'is_default_billing' => $billingJosn['is_default_billing'],
                );

                $shippingAddress = array(
                    'firstname' => $shippingJosn['firstname'],
                    'lastname' => $shippingJosn['lastname'],
                    'email' => $billingJosn['email'],
                    'street' => array(
                        $shippingJosn['street'],
                    ),
                    'city' => $shippingJosn['city'],
                    'postcode' => $shippingJosn['postcode'],
                    'country_id' => $shippingJosn['country_id'],
                    'telephone' => $shippingJosn['telephone'],
                    'customer_password' => '',
                    'confirm_password' => '',
                    'save_in_address_book' => '0',
                    'is_default_shipping' => $shippingJosn['is_default_shipping'],
                    'is_default_billing' => $shippingJosn['is_default_billing'],
                );

                if (isset($shippingJosn['region'])) {
                    //die("1");
                    $shippingAddress['region'] = $shippingJosn['region'];
                } else {
                    //die("2");
                    $shippingAddress['region_id'] = $shippingJosn['region_id'];
                }
                if (isset($billingJosn['region'])) {
                    //die("3");
                    $billingAddress['region'] = $billingJosn['region'];
                } else {
                    //die("4");
                    $billingAddress['region_id'] = $billingJosn['region_id'];
                }
                /*customer start*/
                $result = $this->resultJsonFactory->create();
                $customerFactory = $this->customerFactory;
                $customer = $customerFactory->create();
                $customer->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
                $customer = $customer->loadByEmail($billingJosn['email']);
                if ($customer->getEntityId()) {
                    $customer_id = $customer->getEntityId();
                } else {
                    $customer->setWebsiteId($this->storeManager->getStore()->getWebsiteId())
                        ->setFirstname($billingJosn['firstname'])
                        ->setLastname($billingJosn['firstname'])
                        ->setEmail($billingJosn['email'])
                        ->setPassword($billingJosn['email']);
                    $customer->save();
                    $customer_id = $customer->getEntityId();
                }

                /*customer end*/
                $customer = $this->customerRepository->getById($customer->getEntityId());
                $quote->setCurrency();
                $quote->assignCustomer($customer);

                $quote->getBillingAddress()->addData($billingAddress);

                $quote->getShippingAddress()->addData($shippingAddress)->setShippingMethod($shipping_method);

                $quote->getShippingAddress()->setCollectShippingRates(true);
                $quote->collectTotals();
                if ($paymentmethod != 'authorizenet') {
                    $quote->setPaymentMethod($paymentmethod);
                }
                $quote->setMms_order_type('app');
                $quote->setDeviceData(json_encode(['device_registraton' => $registration_id, 'device_type' => $deviceType]));
                $quote->save();

                $quote->getPayment()->importData(array('method' => $paymentmethod));
                $quote->collectTotals()->save();
                $order = $this->quoteManagement->submit($quote);

                //print_r($order->getRealOrderId());
                //die('aef');
                $order->setEmailSent(0);
                if ($order->getCanSendNewEmailFlag()) {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
                    $emailSender->send($order);
                    $order->setEmailSent(1);
                }
                $order->setMms_order_type('app');
                $order->setDeviceData(json_encode(['device_registraton' => $registration_id, 'device_type' => $deviceType]))->save();
                /*if ($order->getCanSendNewEmailFlag()) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
                $emailSender->send($order);

                }*/
                $itemcount = $order->getTotalItemCount();
                $grandTotal = $order->getData('grand_total');
                $increment_id = $order->getRealOrderId();
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $order_data = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($increment_id);

                if ($paymentmethod == "paypal_express") {
                    //$this->_initToken();
                    $payment = $order_data->getPayment();
                    $order_array = array(
                        'paypal_correlation_id' => $finalJosn['tx'],
                        'order_incremental_id' => $order->getIncrementId(),
                        'paypal_payer_status' => $finalJosn['st'],
                        'paypal_express_checkout_token' => "EC-981900249B799783",
                        'paypal_payer_email' => $order->getCustomerEmail(),
                        //as your need
                    );
                    $payment->setAdditionalInformation($order_array);
                    $order_data->save();
                }

                //$this->_eventManager->dispatch('place_order_api_connector', ['order' => $order, 'device_type' => $deviceType, 'device_registraton' => $registration_id]);
                $finalResult = [
                    'status' => 'success',
                    'orderid' => $increment_id,
                    'items_count' => $itemcount,
                    'grand_total' => $grandTotal,
                ];

                /* this will only work if the payu is enabled for app*/
                if ($paymentmethod == 'payu') {
                    $finalResult['checkout_request'] = $this->buildCheckoutRequest($order);
                    $finalResult['url'] = $this->getCgiUrl();
                }
                /* this will only work if the payu is enabled for app*/

                $result->setData($finalResult);
                return $result;
            } catch (\Exception $e) {
                $result->setData(['status' => 'error', 'message' => __($e->getMessage())]);
                return $result;
            }
        }
    }

    /**
     * Payment processing for PayUIndia_Payu
     */
    public function buildCheckoutRequest($order)
    {
        $billing_address = $order->getBillingAddress();

        $params = array();
        $params["key"] = $this->scopeConfig->getValue("payment/payu/merchant_key");
        if ($this->scopeConfig->getValue('payment/payu/account_type') == 'payumoney') {
            $params["payment/payu/service_provider"] = $this->scopeConfig->getValue("payment/payu/service_provider");
        }
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $urlObj = $objectManager->create('\PayUIndia\Payu\Model\Payu');
        $params["txnid"] = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
        $params["amount"] = round($order->getBaseGrandTotal(), 2);
        $params["productinfo"] = $order->getRealOrderId();
        $params["firstname"] = $billing_address->getFirstName();
        $params["lastname"] = $billing_address->getLastname();
        $params["city"] = $billing_address->getCity();
        $params["state"] = $billing_address->getRegion();
        $params["zip"] = $billing_address->getPostcode();
        $params["country"] = $billing_address->getCountryId();
        $params["email"] = $order->getCustomerEmail();
        $params["phone"] = $billing_address->getTelephone();
        $params["curl"] = $urlObj->getCancelUrl();
        $params["furl"] = $urlObj->getReturnUrl();
        $params["surl"] = $urlObj->getReturnUrl();

        $params["hash"] = $this->generatePayuHash(
            $params['txnid'],
            $params['amount'],
            $params['productinfo'],
            $params['firstname'],
            $params['email']
        );

        return $params;
    }

    /**
     * Return url according to environment
     * @return string
     */
    public function getCgiUrl()
    {
        $env = $this->scopeConfig->getValue('payment/payu/environment');
        if ($env === 'production') {
            return $this->scopeConfig->getValue('payment/payu/production_url');
        }
        return $this->scopeConfig->getValue('payment/payu/sandbox_url');
    }

    public function generatePayuHash(
        $txnid,
        $amount,
        $productInfo,
        $name,
        $email
    ) {
        $SALT = $this->scopeConfig->getValue('payment/payu/salt');

        $posted = array(
            'key' => $this->scopeConfig->getValue("payment/payu/merchant_key"),
            'txnid' => $txnid,
            'amount' => $amount,
            'productinfo' => $productInfo,
            'firstname' => $name,
            'email' => $email,
        );

        $hashSequence = 'key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10';

        $hashVarsSeq = explode('|', $hashSequence);
        $hash_string = '';
        foreach ($hashVarsSeq as $hash_var) {
            $hash_string .= isset($posted[$hash_var]) ? $posted[$hash_var] : '';
            $hash_string .= '|';
        }
        $hash_string .= $SALT;
        return strtolower(hash('sha512', $hash_string));
    }
}
