<?php
namespace Smyapp\Bannersliderapp\Controller\Banner;

class Banner extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->customHelper      = $customHelper;
        $this->storeManager      = $storeManager;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $result        = $this->resultJsonFactory->create();
            /*check cache for dashboard API*/
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $cacheObj = $objectManager->get('Magento\Framework\App\Cache');
            $cacheKey = "Smyapp_bannerslider_store_".$this->storeId;
            $cacheTag = "Smyapp";
            if ($cacheObj->load($cacheKey)) {
                $resultArray = json_decode($cacheObj->load($cacheKey), true);
                $result->setData(['status' => 'success', 'data' => $resultArray]);
            return $result;
            }
            /*check cache for dashboard API*/
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $connection    = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('                                 \Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
            $fetchData        = $connection->fetchAll("SELECT * FROM magentomobile_bannersliderapp where status = 'enable'");
            $array         = array();
            $k             = 0;
            
            foreach ($fetchData as $key => $value) {
                $array[$k]['banner_id']         = $value['banner_id'];
                $array[$k]['title']              = $value['name'];
                $array[$k]['order_banner']      = $value['order_banner'];
                $array[$k]['status']            = $value['status'];
                $array[$k]['link_type']         = $value['url_type'];
                $array[$k]['check_type']        = $value['check_type'];
                $array[$k]['category_name']     = '';
                $array[$k]['product_id']        = $value['product_id'];
                $array[$k]['id']       = $value['category_id'];
                //$array[$k]['image_url']         = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'images/' . $value['thumbnail'];
                $array[$k]['image_url']         = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $value['thumbnail'];
                $array[$k]['image_description'] = $value['image_alt']?:'';
                $k++;
            }
            $cacheObj->save(json_encode($array), $cacheKey, [$cacheTag], 300);
            $result->setData(['status' => 'success', 'data' => $array]);
            return $result;
        } catch (\Exception $e) {
            $result->setData(['status' => 'success', 'data' => __($e->getMessage())]);
            return $result;
        }
    }
}