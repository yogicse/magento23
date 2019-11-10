<?php
/**
 * Created by Smyapp.
 * User: rajnikant
 * Date: 21/08/2019
 * Time: 5:37 PM
 */


namespace Smyapp\Connector\Controller\storeinfo;

class Storelocatordata extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Store\Model\Store $storeModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->_storeManager     = $storeManager;
        $this->customHelper      = $customHelper;
        $this->storeModel        = $storeModel;
        $this->scopeConfig       = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }
    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {

        $customerEmail  = $this->getRequest()->getParam("email");
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storelocator = $objectManager->create('Magedelight\Storepickup\Model\ResourceModel\Storelocator\Collection')->addFieldToFilter('is_active',1);;
         echo json_encode(array('status' => 'success','message'=>$storelocator->getData()));
         exit;
    }
}