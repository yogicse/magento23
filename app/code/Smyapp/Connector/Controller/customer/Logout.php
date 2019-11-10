<?php
namespace Smyapp\Connector\Controller\customer;

class Logout extends \Magento\Framework\App\Action\Action
{

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->customerSession   = $customerSession;
        $this->customHelper      = $customHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
            $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
            $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
            $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
            $lastCustomerId = $this->customerSession->getId();
            $this->customerSession->logout($lastCustomerId);
            $result = $this->resultJsonFactory->create();
            if (!empty($lastCustomerId)) {
                $result->setData(['status' => 'success', 'message' => "Logged out successfully."]);
                return $result;
            } else {
                $result->setData(['status' => 'success', 'message' => "Customer is already logged out."]);
                return $result;
            }
        } catch (\Exception $e) {
            $result->setData(['status' => 'error', 'message' => _($e->getMessage())]);
            return $result;
        }
    }
}
