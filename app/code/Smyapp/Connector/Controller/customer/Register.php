<?php
namespace Smyapp\Connector\Controller\customer;

class Register extends \Magento\Framework\App\Action\Action
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
        $this->_eventManager = $eventManager;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $result         = $this->resultJsonFactory->create();
        if (!$this->customerSession->isLoggedIn()) {
            $params = $this->request->getParams();

            if ((null == $params['password']) || (null == $params['email'])) {
                return $result->setData(['status' => "error", 'message' => 'Required feild is missing.']);
            }
            try {
                $customer = $this->customerFactory->create();
                $customer->setPassword($params['password']);
                $customer->setConfirmation($this->request->getPost('password-confirmation', $params['password']));
                $customer->setFirstname($params['firstname']);
                $customer->setLastname($params['lastname']);
                $customer->setEmail($params['email']);
                $customer->setPassword($params['password']);
                $customer->setMobile($params['mobile']);
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
                if($params['is_subscribed'] == 1){
                
                $subscriber= $objectManager->create('Magento\Newsletter\Model\SubscriberFactory'); 
                $subscriber->create()->subscribe($params['email']);  
                }
                
                $customer->save();



                $customer->sendNewAccountEmail('registered', '', $this->storeManager->getStore()->getId());
                
                $result->setData(['status' => "success", 'message' => 'Account activated successfully.']);
                return $result;
            } catch (\Exception $e) {
                return $result->setData(['status' => "error", 'message' => $e->getMessage()]);
            }
        } else {
            return $result->setData(['status' => "error", 'message' => 'User already logged in.']);
        }
    }
}
