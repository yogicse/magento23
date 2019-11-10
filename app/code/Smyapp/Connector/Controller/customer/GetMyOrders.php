<?php
namespace Smyapp\Connector\Controller\customer;

class GetMyOrders extends \Magento\Framework\App\Action\Action
{

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Directory\Model\Currency $currentCurrency
    ) {
        $this->customerSession         = $customerSession;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->imageHelper             = $imageHelper;
        $this->customHelper            = $customHelper;
        $this->resultJsonFactory       = $resultJsonFactory;
        $this->_currency               = $currency;
        $this->currentCurrency         = $currentCurrency;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $result         = $this->resultJsonFactory->create();
        if ($this->customerSession->isLoggedIn()) {
            $cust_id      = $this->customerSession->getId();
            $res          = array();
            $totorders    = $this->__getOrders($cust_id);
            $res["total"] = count($totorders);
            # start order  loop
            foreach ($totorders as $order) {
                $shippingAddress = $order->getShippingAddress();
                if (is_object($shippingAddress)) {
                    $shippadd = array();
                    $flag     = 0;
                    if (count($totorders) > 0) {
                        $flag = 1;
                    }

                    $shippadd = array(
                        "firstname" => $shippingAddress->getFirstname(),
                        "lastname"  => $shippingAddress->getLastname(),
                        "company"   => $shippingAddress->getCompany(),
                        "street"    => $shippingAddress->getStreet()[0].' '.@$shippingAddress->getStreet()[1],//$shippingAddress->getStreetFull(),
                        "region"    => $shippingAddress->getRegion(),
                        "city"      => $shippingAddress->getCity(),
                        "pincode"   => $shippingAddress->getPostcode(),
                        "countryid" => $shippingAddress->getCountry_id(),
                        "contactno" => $shippingAddress->getTelephone(),
                        "shipmyid"  => $flag,
                    );
                }
                $billingAddress = $order->getBillingAddress();
                if (is_object($billingAddress)) {
                    $billadd = array();
                    $billadd = array(
                        "firstname" => $billingAddress->getFirstname(),
                        "lastname"  => $billingAddress->getLastname(),
                        "company"   => $billingAddress->getCompany(),
                        "street"    => $shippingAddress->getStreet()[0].' '.@$shippingAddress->getStreet()[1],//$shippingAddress->getStreetFull(),
                        "region"    => $billingAddress->getRegion(),
                        "city"      => $billingAddress->getCity(),
                        "pincode"   => $billingAddress->getPostcode(),
                        "countryid" => $billingAddress->getCountry_id(),
                        "contactno" => $billingAddress->getTelephone(),
                    );
                }
                $payment = array();
                $payment = $order->getPayment();
                try {
                    $payment_result = array(
                        "payment_method_title" => $payment->getMethodInstance()->getTitle(),
                        "payment_method_code"  => $payment->getMethodInstance()->getCode(),
                    );
                    if ($payment->getMethodInstance()->getCode() == "banktransfer") {
                        $payment_result["payment_method_description"] = $payment->getMethodInstance()->getInstructions();
                    }
                } catch (\Exception $e) {
                    $result->setData(['status' => 'error', 'message' => __($e->getMessage())]);
                    return $result;
                }

                $items                       = $order->getAllVisibleItems();
                $itemcount                   = count($items);
                $name                        = array();
                $unitPrice                   = array();
                $sku                         = array();
                $ids                         = array();
                $qty                         = array();
                $images                      = array();
                $test_p                      = array();
                $itemsExcludingConfigurables = array();
                $productlist                 = array();
                foreach ($items as $itemId => $item) {
                    $name      = $item->getName();
                    $unitPrice = number_format($item->getPrice(), 2, '.', '');
                    $sku       = $item->getSku();
                    $ids       = $item->getProductId();
                    //$qty[]=$item->getQtyToInvoice();
                    $qty           = (int) $item->getQtyOrdered();
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $products      = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());
                    $images        = $this->imageHelper
                        ->init($products, 'product_page_image_large')
                        ->setImageFile($products->getFile())
                        ->resize('250', '250')
                        ->getUrl();

                    $productlist[] = array(
                        "name"             => $name,
                        "sku"              => $sku,
                        "id"               => $ids,
                        "quantity"         => (int) $qty,
                        "unitprice"        => $unitPrice,
                        "image"            => $images,
                        "total_item_count" => $itemcount,
                        "price_org"        => $test_p,
                        "price_based_curr" => 1,
                    );
                } # item foreach close
                $order_date = $order->getCreatedAt() . '';
                $orderData  = array(
                    "id"                    => $order->getId(),
                    "order_id"              => $order->getRealOrderId(),
                    "status"                => str_replace('-', ' ', $order->getStatus()),
                    "order_date"            => $order_date,
                    "grand_total"           => number_format($order->getGrandTotal(), 2, '.', ''),
                    "shipping_address"      => @$shippadd ? $shippadd : "",
                    "billing_address"       => $billadd ? $billadd : "",
                    "shipping_message"      => $order->getShippingDescription(),
                    "shipping_amount"       => number_format($order->getShippingAmount(), 2, '.', ''),
                    "payment_method"        => $payment_result,
                    "tax_amount"            => number_format($order->getTaxAmount(), 2, '.', ''),
                    "products"              => $productlist,
                    "order_currency"        => $this->currentCurrency->load($this->currency)->getCurrencySymbol() ?
                    $this->currentCurrency->load($this->currency)->getCurrencySymbol() : $this->currency,
                    "order_currency_symbol" => $this->currentCurrency->load($this->currency)->getCurrencySymbol() ?
                    $this->currentCurrency->load($this->currency)->getCurrencySymbol() : $this->currency,
                    "currency"              => $this->currentCurrency->load($this->currency)->getCurrencySymbol() ?
                    $this->currentCurrency->load($this->currency)->getCurrencySymbol() : $this->currency,
                    "couponUsed"            => 0,
                );
                $couponCode = $order->getCouponCode();
                if ($couponCode != "") {
                    $orderData["couponUsed"]      = 1;
                    $orderData["couponCode"]      = $couponCode;
                    $orderData["discount_amount"] = floatval(number_format($order->getDiscountAmount(), 2, '.', '')) * -1;
                }
                $orderData['reward_amount'] = $order->getRewardAmount() ? $order->getRewardAmount() : "";
                $res["data"][]              = $orderData;
            } # end foreach
            $result->setData($res);
            return $result;
        } else {
            $result->setData(['status' => 'error', 'message' => __('Please Login to see the orders.')]);
            return $result;
        }
    }

    protected function __getOrders($customerId)
    {

        $this->orders = $this->_orderCollectionFactory->create()->addFieldToSelect(
            '*'
        )->addFieldToFilter(
            'customer_id',
            $customerId
        )->setOrder(
            'created_at',
            'desc'
        );
        return $this->orders;
    }
}
