<?php
namespace Smyapp\Connector\Controller\customer;

class Membercard extends \Magento\Framework\App\Action\Action
{

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        //\Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Framework\Event\Manager $eventManager
    ) {
        $this->customerSession   = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerFactory   = $customerFactory;
        $this->storeManager      = $storeManager;
        $this->customHelper      = $customHelper;
        $this->logger            = $logger;
        $this->request           = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request           = $context->getRequest();
        $this->_eventManager     = $eventManager;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $result         = $this->resultJsonFactory->create();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
// Load customer
        $customer = $objectManager->create('Magento\Customer\Model\Customer')->load(2); //2 is Customer ID

// Load customer session
        $customerSession = $objectManager->create('Magento\Customer\Model\Session');
        $customerSession->setCustomerAsLoggedIn($customer);

        if ($customerSession->isLoggedIn()) {
            return $result->setData(['status' => "success", 'message' => 'Account activated successfully.']);
        } else {
            return $result->setData(['status' => "error", 'message' => 'User already logged in.']);
        }
    }

}
