<?php
namespace Smyapp\Connector\Controller\cart;

class Getpaymentmethods extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Payment\Model\Config $paymentMethodConfig,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->paymentMethodConfig = $paymentMethodConfig;
        $this->scopeConfig         = $scopeConfig;
        $this->customHelper        = $customHelper;
        $this->resultJsonFactory   = $resultJsonFactory;
        parent::__construct($context);
    }
    public function execute()
    {
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $payments       = $this->paymentMethodConfig->getActiveMethods();
        $result         = $this->resultJsonFactory->create();
        $methods        = array();

        // $payments = $this->paymentMethodConfig->getActiveMethods();
        // $methods  = array();
        // foreach ($payments as $paymentCode => $paymentModel) {
        //     $paymentTitle = $this->scopeConfig
        //         ->getValue('payment/'.$paymentCode.'/title');
        //     $methods[$paymentCode] = array(
        //         'label' => $paymentTitle,
        //         'code' => $paymentCode
        //     );
        // }
       

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart          = $objectManager->get('\Magento\Checkout\Model\Cart');
        $quote_id      = $cart->getQuote()->getEntityId();

        foreach ($payments as $paymentCode => $paymentModel) {
if ($paymentCode == 'vsfpaypal') {
                $methods[] = array(
                    'value'    => 'Paypal Express',
                    'code'     => $paymentCode,
                    'quote_id' => $quote_id,
                );

}
            //  if ($this->scopeConfig->getValue('Smyapp_payment'.'/'.$paymentCode)) {
            $paymentTitle = $this->scopeConfig->getValue('payment/' . $paymentCode . '/title');
            if ($paymentCode == 'cashondelivery') {
                $methods[] = array(
                    'value'    => $paymentTitle,
                    'code'     => $paymentCode,
                    'quote_id' => $quote_id,
                );
            // } elseif ($paymentCode == 'authorizenet_directpost') {
            //     $methods[] = array(
            //         'value' => $paymentTitle,
            //         'code'  => $paymentCode,
            //         'cards' => array('Visa' => 'VI', 'Mastercard' => 'MC', 'American Express' => 'AE', 'Discover' => 'DI'),
            //     );
            // } else {
            //     if ($paymentCode == 'paypal_express') {
            //         $methods[] = array(
            //             'value'    => $paymentTitle,
            //             'code'     => $paymentCode,
            //             'quote_id' => $quote_id,
            //         );
            //     } //else {
                //     $methods[] = array(
                //         'value' => $paymentTitle,
                //         'code'  => $paymentCode,
                //         'quote_id'=>  $quote_id,
                //     );
                // }
                // }
            }
        }
        //Smyapp_payment/cashondelivery/cod_status
        $result->setData($methods);
        return $result;
    }
}
