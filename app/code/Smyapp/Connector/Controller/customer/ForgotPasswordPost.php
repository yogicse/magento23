<?php
namespace Smyapp\Connector\Controller\customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;

/**
 * ForgotPasswordPost controller
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ForgotPasswordPost extends \Magento\Customer\Controller\AbstractAccount
{
    /** @var AccountManagementInterface */
    protected $customerAccountManagement;

    /** @var Escaper */
    protected $escaper;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param Escaper $escaper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        Escaper $escaper,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory

    ) {
        $this->session                   = $customerSession;
        $this->customHelper              = $customHelper;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->escaper                   = $escaper;
        $this->jsonHelper                = $jsonHelper;
        $this->resultJsonFactory         = $resultJsonFactory;
        $this->request                   = $context->getRequest();
        parent::__construct($context);
    }

    /**
     * Forgot customer password action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $result         = $this->resultJsonFactory->create();
        $result         = array();
        $email          = (string) $this->request->getPost('email');
        if ($email) {
            if (!\Zend_Validate::is($email, 'EmailAddress')) {
                $this->session->setForgottenEmail($email);
                $result->setData(['status' => 'error', 'message' => __('Invalid email address.')]);
                return $result;
            }
            try {
                $this->customerAccountManagement->initiatePasswordReset(
                    $email,
                    AccountManagement::EMAIL_RESET
                );
            } catch (SecurityViolationException $exception) {
                $result->setData(['status' => 'error', 'message' => __($exception->getMessage())]);
                return $result;
            } catch (\Exception $exception) {
                $result->setData(['status' => 'error', 'message' => __('We\'re unable to send the password to reset email.')]);
                return $result;
            }
            $result->setData(['status' => 'success', 'message' => $this->getSuccessMessage($email)]);
            return $result;
        } else {
            $result->setData(['status' => 'error', 'message' => __('Please enter your email.')]);
            return $result;
        }
    }

    /**
     * Retrieve success message
     *
     * @param string $email
     * @return \Magento\Framework\Phrase
     */
    protected function getSuccessMessage($email)
    {
        return __(
            'If there is an account associated with %1 you will receive an email with a link to reset your password.',
            $this->escaper->escapeHtml($email)
        );
    }
}
