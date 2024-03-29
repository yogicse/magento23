<?php
namespace Smyapp\Connector\Controller\Cart;

class GetShippingTotal extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Cart $checkoutCart,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Customer\Model\Address $customerAddress,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
        //\Magento\Framework\App\RequestInterface $requestInterface
    ) {
        $this->checkoutCart      = $checkoutCart;
        $this->customHelper      = $customHelper;
        $this->customer          = $customer;
        $this->customerAddress   = $customerAddress;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request           = $context->getRequest();
        parent::__construct($context);
    }
    public function execute()
    {

        $addressId       = $this->request->getParam('address_id');
        $countryId       = $this->request->getParam('country_id');
        $setRegionId     = $this->request->getParam('region_id');
        $shipping_method = $this->request->getParam('shippingmethod');
        $quote           = $this->checkoutCart->getQuote();
        if (isset($addressId)) {
            $customer      = $this->customerAddress->load($addressId);
            $countryId     = $customer['country_id'];
            $setRegionId   = $customer['region_id'];
            $regionName    = $customer['region'];
            $shippingCheck = $quote->getShippingAddress()->getData();

            if ($shippingCheck['shipping_method'] != $shipping_method) {
                if (isset($setRegionId)) {
                    $quote->getShippingAddress()
                        ->setCountryId($countryId)
                        ->setRegionId($setRegionId)
                        ->setPostcode($zipcode)
                        ->setCollectShippingRates(true);
                } else {
                    $quote->getShippingAddress()
                        ->setCountryId($countryId)
                        ->setRegion($regionName)
                        ->setPostcode($zipcode)
                        ->setCollectShippingRates(true);
                }
                $quote->save();
                $quote->getShippingAddress()->setShippingMethod($shipping_method)->save();
            }

            $quote->collectTotals()->save();
            $amount          = $quote->getShippingAddress()->getData();
            $shipping_amount = $amount['shipping_incl_tax'];
            return $shipping_amount;
        } else {
            $shippingCheck = $quote->getShippingAddress()->getData();

            if ($shippingCheck['shipping_method'] != $shipping_method) {
                if (isset($setRegionId)) {
                    $quote->getShippingAddress()
                        ->setCountryId($countryId)
                        ->setRegionId($setRegionId)
                        ->setPostcode($zipcode)
                        ->setCollectShippingRates(true);
                } else {
                    $quote->getShippingAddress()
                        ->setCountryId($countryId)
                        ->setPostcode($zipcode)
                        ->setCollectShippingRates(true);
                }
                $quote->save();
                $quote->getShippingAddress()->setShippingMethod($shipping_method)->save();
            }
            $quote->collectTotals()->save();
            $amount          = $quote->getShippingAddress();
            $shipping_amount = $amount['shipping_incl_tax'];
            return $shipping_amount;
        }
    }
}
