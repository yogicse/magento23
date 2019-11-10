<?php
namespace Smyapp\Connector\Controller\Cart;

class Add extends \Magento\Framework\App\Action\Action
{
    protected $_messageManager;
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
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Checkout\Model\Cart $checkoutCart,
        \Magento\Framework\Locale\ResolverInterface $resolverInterface,
        \Magento\Checkout\Model\Session $session,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
        //\Magento\Framework\App\RequestInterface $requestInterface
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutCart      = $checkoutCart;
        $this->productModel      = $productModel;
        $this->jsonHelper        = $jsonHelper;
        $this->resolverInterface = $resolverInterface;
        $this->_messageManager   = $context->getMessageManager();
        $this->session           = $session;
        $this->customHelper      = $customHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request           = $context->getRequest();
        parent::__construct($context);
    }
    public function execute()
    {
        $final_params = array();
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $result     = $this->resultJsonFactory->create();
        try {
            $params = file_get_contents("php://input");
            $params = $this->jsonHelper->jsonDecode($params, true);
            $product_id = $params['product'];
            $product    = $this->productModel->load($product_id);
            if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->resolverInterface->getLocale()]
                );
                $params['qty']       = $filter->filter($params['qty']);
                $final_params['qty'] = $params['qty'];
            } elseif ($product_id == '') {
           $this->_messageManager->addError(__('Product not added. The SKU added %1 does not exists.',$sku));
            }

            if ($product) {
                $final_params['product'] = $params['product'];
                if (isset($params['super_attribute'])) {
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
                $this->checkoutCart->addProduct($product, $final_params);
                $this->checkoutCart->save();
            }

            $quote = $this->session->getQuote();
            $items = $quote->getAllVisibleItems();
            foreach ($items as $item) {
                $cartItemArr = $item->getId();
            }

            $items_qty = floor($quote->getItemsQty());
            $result->setData(['status' => 'success', 'items_qty' => $items_qty, "cart_item_id" => $cartItemArr]);
            return $result;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $result->setData(['status' => 'error', 'message' => str_replace("\"", "||", $e->getMessage())]);
            return $result;
        } catch (\Exception $e) {
            $result->setData(['status' => 'error', 'message' => str_replace("\"", "||", $e->getMessage())]);
            return $result;
        }
    }
}
