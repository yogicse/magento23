<?php
namespace Smyapp\Connector\Controller\Cart;

class CancelOrder extends \Magento\Framework\App\Action\Action
{

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
        //\Magento\Framework\App\RequestInterface $requestInterface
    ) {
        $this->customerSession = $customerSession;
        $this->order           = $orderFactory;
        $this->customHelper    = $customHelper;
        $this->resultJsonFactory  = $resultJsonFactory;
        $this->request            = $context->getRequest();
        parent::__construct($context);
    }

    public function execute()
    {
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $orderId        = (int) $this->request->getParam('orderId');
        $result         = $this->resultJsonFactory->create();
        if ($orderId) {
            if ($this->customerSession->isLoggedIn()) {
                try {
                    $order = $this->order->create()->loadByIncrementId($orderId);
                    if (!$order->getId()) {
                        $result->setData(['status' => 'error', 'message' => __('Invalid Order Id.')]);
                        return $result;
                    }

                    $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED)
                        ->setStatus('Canceled')
                        ->addStatusHistoryComment('Order marked as cancelled by user.', false)
                        ->setIsCustomerNotified(true);

                    $order->save();

                    if ($order->canCancel()) {
                        $order->cancel()->save();
                    }
                     $result->setData(['status' => 'success', 'message' => __('Order marked as cancelled by user.')]);
                        return $result;
                } catch (\Exception $e) {
                        $result->setData(['status' => 'error', 'message' => __($e->getMessage())]);
                        return $result;
                }
                    $result->setData(['status' => 'success', 'message' => __('Order marked as cancelled by user.')]);
                        return $result;
            } else {
                    $result->setData(['status' => 'error', 'message' => __('Login first to cancel Order.')]);
                        return $result;
            }
        } else {
                $result->setData(['status' => 'error', 'message' => __('Please send Order Id to cancel.')]);
                        return $result;
        }
    }
}
