<?php
namespace Smyapp\Connector\Controller\cart;

//use Magento\Checkout\Model\Session;

class GetMinimumorder extends \Magento\Framework\App\Action\Action
{

    /**
     * @var Session
     */
    // protected $checkoutSession;
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $checkoutCart;
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $product;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Cart $checkoutCart,
        // Session $checkoutSession,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Helper\Cart $checkoutHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->checkoutCart      = $checkoutCart;
        $this->checkoutHelper    = $checkoutHelper;
        $this->productModel      = $productModel;
        $this->jsonHelper        = $jsonHelper;
        $this->scopeConfig       = $scopeConfig;
        $this->customHelper      = $customHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }
    public function execute()
    {
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $result         = $this->resultJsonFactory->create();
        $params = file_get_contents("php://input");
        $finalJosn           = $this->jsonHelper->jsonDecode($params, true);
        $cart_data = $finalJosn['cart_data'];

        if (!sizeof($cart_data)) {
            $result->setData(['status' => 'error', 'message' => __('Cart is empty.')]);
            return $result;
        }
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$cart = $objectManager->get('\Magento\Checkout\Model\Cart');

$allItems = $cart->getQuote()->getAllVisibleItems();
        foreach ($allItems as $item) {
            $itemId = $item->getItemId();
            $cart->removeItem($itemId)->save();
        }

    //      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();//instance of object manager 
    //     $checkoutSession = $objectManager->get('Magento\Checkout\Model\Session');//checkout session
    //     $allItems = $checkoutSession->getQuote()->getAllVisibleItems();//returns all teh items in session
    //     $itemModel = $objectManager->create('Magento\Quote\Model\Quote\Item');//Quote item model to load quote item
    //     foreach ($allItems as $item) {
    //     $itemId = $item->getItemId();//item id of particular item
    //     $quoteItem=$this->getItemModel()->load($itemId);//load particular item which you want to delete by his item id
    //     $quoteItem->delete();//deletes the item
    // }
        //$carts = $this->checkoutHelper->getCart();
        //$carts->truncate();
        $cart = $this->checkoutCart;
        //$cart->setQuote($carts->getQuote());
        foreach ($cart_data['items'] as $params) {
            try {
                $final_params  = [];
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $product = $objectManager->create('Magento\Catalog\Model\Product')->load($params['product']);

                if ($product) {
                    if (isset($params['qty'])) {
                        $final_params['qty'] = $params['qty'];
                    }
                    $final_params['product'] = $params['product'];
                    if (isset($params['super_attribute'])) {
                        $subject = ($params['super_attribute']);
                        foreach($params['super_attribute'] as $attribute) {
                            $final_params['super_attribute'][$attribute['attribute_id']] = $attribute['option_id'];
                        }
                    }
                    if (isset($params['options'])) {
                        foreach($params['options'] as $attributeOption) {
                            $final_params['options'][$attributeOption['attribute_id']] = $attributeOption['option_id'];
                        }
                    }

                    if (isset($params['bundle_option'])) {
                        $final_params['bundle_option'] = $this->jsonHelper->jsonDecode($params['bundle_option']);
                    }

                    $request = new \Magento\Framework\DataObject($final_params);
                    $cart->addProduct($product, $request);
                }
            } catch (\Exception $e) {
                $result->setData(['status' => 'error', 'message' => $e->getMessage()]);
                return $result;
            }
        }

        try {
            $cart->save();
        } catch (\Exception $e) {
            $result->setData(['status' => 'error', 'message' => $e->getMessage()]);
                return $result;
            return $result;
        }

        if ($this->scopeConfig->getValue('sales/minimum_order/active')) {
            $check_grand_total = $this->checkoutHelper->getQuote()->getBaseSubtotalWithDiscount();

            $amount = $this->scopeConfig->getValue('sales/minimum_order/amount');
            if ($check_grand_total < $amount) {
                $message = $this->scopeConfig->getValue('sales/minimum_order/error_message');
                if (!$message) {
                    $message = 'Minimum Order limit is. ' . $amount;
                }

                 $result->setData(['status' => 'error', 'message' => $this->$message]);
                 return $result;
            }
        }
         $result->setData(['status' => 'success', 'message' => 'true']);
        return $result;
    }
}
