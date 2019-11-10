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
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        //\Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Framework\Event\Manager $eventManager
    ) {
        $this->customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->customHelper = $customHelper;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $context->getRequest();
        $this->_eventManager = $eventManager;
        parent::__construct($context);
    }

    public function execute()
    {
      
        $result = $this->resultJsonFactory->create();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $key = $this->scopeConfig->getValue('Smyapp/secure/token');

        $token = $this->request->getParam('token');

        $date = $this->request->getParam('timestamp');

        $maindata = $key . '-' . $this->request->getParam('customer_id') . '-' . $date;
        $encrypteddata = md5($maindata);

        if ($encrypteddata == $this->request->getParam('token') ) {

// Load customer
            $customer = $objectManager->create('Magento\Customer\Model\Customer')->load($this->request->getParam('customer_id')); //2 is Customer ID

// Load customer session
            //$customerSession = $objectManager->create('Magento\Customer\Model\Session');
            //$customerSession->setCustomerAsLoggedIn($customer);
            // header("Location: http://localhost/rajnkant/peoplemarket/code/membercard/");
           

                

                header("Location: https://member.peoplesmarket.hk/app/landing?customer_id=".$this->request->getParam('customer_id')."&timestamp=".$date."&token=".$token.""); 
  
                    exit; 

            
        } else {
            echo json_encode(['message'=> "Unauthorized User"]);
            exit;
        }

    }

}
