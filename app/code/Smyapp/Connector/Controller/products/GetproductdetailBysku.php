<?php
namespace Smyapp\Connector\Controller\products;

class GetproductdetailBysku extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Smyapp\Connector\Helper\Products $productHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory

    ) {
        $this->customHelper      = $customHelper;
        $this->productHelper     = $productHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request           = $context->getRequest();
        parent::__construct($context);
    }

    /*
     * @params productid
     * @description : get the detail of product.
     * @return Json
     */
    public function execute()
    {
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $result         = $this->resultJsonFactory->create();
        $sku            = $this->request->getParam('productsku');
        if (!$sku) {
            $result->setData(['status' => false, 'message' => __('Product sku is required.')]);
            return $result;
        }
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productId = $objectManager->get('Magento\Catalog\Model\Product')->getIdBySku($sku);

        if ($productId) {
            $results = $this->productHelper->loadProduct($productId,$this->currency);
        } else {
            $results = array('status' => 'error', 'message' => __('Product Id not found.'));
        }

        $result->setData($results);
        return $result;
    }
}
