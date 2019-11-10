<?php
namespace Smyapp\Connector\Controller\customer;

use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\EmailNotConfirmedException;

class Login extends \Magento\Framework\App\Action\Action
{

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CustomerUrl $customerHelperData,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory

    ) {
        parent::__construct($context);
        $this->customerSession           = $customerSession;
        $this->customerUrl               = $customerHelperData;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customHelper              = $customHelper;
        $this->request                   = $context->getRequest();
        $this->resultJsonFactory         = $resultJsonFactory;
    }

    public function execute()
    {
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $username       = $this->request->getParam('username');
        $password       = base64_decode($this->request->getParam('password'));
        $validate       = array();
        $customerinfo   = array();
        $result         = $this->resultJsonFactory->create();
        try {
/*Social Login Works from here*/
            if ($this->request->getParam('login_token') && $this->request->getParam('sociallogintype')){
                $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $customer    = $_objectManager->get('Smyapp\Sociallogin\Helper\Data');
                $resultArray = $customer->socialloginRequest($this->request->getParam('login_token'),$this->request->getParam('sociallogintype'));
                return $result->setData($resultArray);
            }
/*validations start*/
            if (($username == null) || ($password == null)) {
                $result->setData(['status' => 'error', 'message' => __('Invalid username and password.')]);
                return $result;
            }
/*validations End*/
            else {
/*Customer login portion start*/
                $customer = $this->customerAccountManagement->authenticate($username, $password);
                $this->customerSession->setCustomerDataAsLoggedIn($customer);
                $this->customerSession->regenerateId();

                if ($this->customerSession->isLoggedIn()) {
                    $customer_data = $this->customerSession->getCustomer()->getData();

                    $customerinfo = array(
                        "id"    => $customer_data['entity_id'],
                        "name"  => $customer_data['firstname'] . $customer_data['lastname'],
                        "email" => $customer_data['email'],
                    );
                    return $result->setData(['status' => 'success', 'message' => $customerinfo]);
                } else {
                    return $result->setData(['status' => 'error', 'message' => __('Error in authentication')]);
                }
/*Customer login portion end*/
            }
        } catch (EmailNotConfirmedException $e) {
            $value   = $this->customerUrl->getEmailConfirmationUrl($username);
            $message = __(
                'This account is not confirmed. Please confirm your account.',
                $value
            );
            return $result->setData(['status' => 'error', 'message' => $message]);
            //   $this->customerSession->setUsername($username);
        } catch (UserLockedException $e) {
            $message = __(
                'The account is locked. Please wait and try again or contact %1.',
                $this->getScopeConfig()->getValue('contact/email/recipient_email')
            );
            return $result->setData(['status' => 'error', 'message' => $message]);
            //  $this->customerSession->setUsername($username);
        } catch (AuthenticationException $e) {
            $message = __('Invalid email or password.');
            return $result->setData(['status' => 'error', 'message' => $message]);
            //  $this->customerSession->setUsername($username);
        } catch (\Exception $ex) {
            $message = __('An unspecified error occurred. Please contact us for assistance.');
            return $result->setData(['status' => 'error', 'message' => $message]);
        }
    }
}
