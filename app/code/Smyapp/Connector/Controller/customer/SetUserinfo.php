<?php
namespace Smyapp\Connector\Controller\customer;
use Magento\Framework\App\ObjectManager;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;

class SetUserinfo extends \Magento\Framework\App\Action\Action
{

    const XML_PATH_CHANGE_EMAIL_TEMPLATE = 'customer/account_information/change_email_template';

    const XML_PATH_FORGOT_EMAIL_IDENTITY = 'customer/password/forgot_email_identity';

    const FORM_DATA_EXTRACTOR_CODE = 'customer_account_edit';

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Customer\Model\Authentication $authentication,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepo,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        CustomerViewHelper $customerViewHelper,
        DataObjectProcessor $dataProcessor,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig,
        CustomerExtractor $customerExtractor
        //\Magento\Framework\App\RequestInterface $requestInterface
    ) {
        $this->customerSession   = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerFactory   = $customerFactory;
        $this->storeManager      = $storeManager;
        $this->customerRepo      = $customerRepo;
        $this->customHelper      = $customHelper;
        $this->authenticate      = $authentication;
        $this->addressFactory    = $addressFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request           = $context->getRequest();
        $this->customerViewHelper = $customerViewHelper;
        $this->dataProcessor = $dataProcessor;
        $this->customerRegistry = $customerRegistry;
        $this->customerExtractor = $customerExtractor;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $result         = $this->resultJsonFactory->create();
        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerFactory->create();
            $customer->load($this->customerSession->getId());
            $data = $this->request->getParams();
            if (isset($data)) {
                $customer->setFirstname($data['firstname']);
                $customer->setLastname($data['lastname']);
                 $customer->setMobile($data['mobile']);

                if (isset($data['profile_image'])) {

                    $imageResult = $this->base64_to_jpeg($data['profile_image'], $data['type']);

                    if ($imageResult['status'] == 'error') {
                        $result->setData(['status' => 'error', 'message' => __('Unable to upload image.')]);
                        return $result;
                    }

                    $customerData = $customer->getDataModel();
                    $customerData->setCustomAttribute('Smyapp_profile_image_customer', $this->storeManager->getStore()->getBaseUrl().$imageResult['file_url']);

                    $customer->updateData($customerData);

                    try {
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $customRepo = $objectManager->get('\Magento\Customer\Model\ResourceModel\CustomerFactory')->create();
                        $customRepo->save($customer,'Smyapp_profile_image_customer');
                    } catch (Exception $e) {
                        $result->setData(['status' => 'error', 'message' => __($e->getMessage())]);
                        return $result;
                    }
                }

                $address = $customer->getPrimaryBillingAddress();
                if (!$address) {
                    $address = $this->addressFactory->create();
                    $address->setCustomerId($customer->getId());
                    $address->setIsDefaultBilling(true);
                }
                if (isset($data['email']) && $this->customerSession->getCustomer()->getId()) {
                    if (isset($data['password'])) {
                        try {
                            $this->authenticate->authenticate($this->customerSession->getCustomer()->getId(), base64_decode($data['password']));
                            $currentCustomerDataObject = $this->getCustomerDataObject($this->customerSession->getId());
                            $customerCandidateDataObject = $this->populateNewCustomerDataObject(
                                $this->_request,
                                $currentCustomerDataObject
                            );
                            $this->emailChanged($customerCandidateDataObject, $customer->getEmail());
                            $this->emailChanged($customerCandidateDataObject, $data['email']);

                            $customer->setEmail($data['email']);
                        } catch (\Exception $e) {
                            $result->setData(['status' => 'error', 'message' => __($e->getMessage())]);
                            return $result;
                        }
                    } else {
                        $result->setData(['status' => 'error', 'message' => __('Please enter password to change email.')]);
                        return $result;
                    }
                }
                try {
                    //Customer address save.
                    $customer->save();
                    $result->setData(['status' => 'success', 'message' => __('Data updated successfully.')]);
                    return $result;
                } catch (\Exception $e) {
                    $result->setData(['status' => 'error', 'message' => __('Data not updated.')]);
                    return $result;
                }
            } else {
                $result->setData(['status' => 'error', 'message' => __('Data not updated.')]);
                return $result;
            }
        } else {
            $result->setData(['status' => 'error', 'message' => __('Session expired, Please login again.')]);
            return $result;
        }
    }

    /**
     * Base64 to image convertion
     * @param  Base64encode $base64_string
     * @return Array
     */
    public function base64_to_jpeg($base64_string, $type)
    {
        define('UPLOAD_DIR', 'pub/profile_image/');
        if (!file_exists('pub/profile_image')) {
            mkdir('pub/profile_image', 0777, true);
        }
        try {
            /*$image_parts = explode(";base64,", $base64_string);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);*/
            $file = UPLOAD_DIR . uniqid() . $type;
            file_put_contents($file, base64_decode($base64_string));
            return array('status' => 'success','file_url'=> $file);
        } catch (Exception $e) {
            return array('status' => 'error','message'=> $e->getMessage());
        }
    }

    /**
     * Get email notification
     *
     * @return EmailNotificationInterface
     * @deprecated
     */
    private function getEmailNotification()
    {
        return ObjectManager::getInstance()->get(
                EmailNotificationInterface::class
            );
    }

    /**
     * Send email to customer when his email is changed
     *
     * @param CustomerInterface $customer
     * @param string $email
     * @return void
     */
    private function emailChanged(CustomerInterface $customer, $email)
    {

        $storeId = $customer->getStoreId();
        if (!$storeId) {
            $storeId = $this->storeId;
        }

        $customerEmailData = $this->getFullCustomerObject($customer);

        $this->sendEmailTemplate(
            $customer,
            self::XML_PATH_CHANGE_EMAIL_TEMPLATE,
            self::XML_PATH_FORGOT_EMAIL_IDENTITY,
            ['customer' => $customerEmailData, 'store' => $this->storeManager->getStore($storeId)],
            $storeId,
            $email
        );
    }

    private function sendEmailTemplate(
        $customer,
        $template,
        $sender,
        $templateParams = [],
        $storeId = null,
        $email = null
    ) {
        $templateId = $this->scopeConfig->getValue($template, 'store', $storeId);
        if ($email === null) {
            $email = $customer->getEmail();
        }
        $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
            ->setTemplateVars($templateParams)
            ->setFrom($this->scopeConfig->getValue($sender, 'store', $storeId))
            ->addTo($email, $this->customerViewHelper->getCustomerName($customer))
            ->getTransport();

        $transport->sendMessage();
    }

    /**
     * Create an object with data merged from Customer and CustomerSecure
     *
     * @param CustomerInterface $customer
     * @return \Magento\Customer\Model\Data\CustomerSecure
     */
    private function getFullCustomerObject($customer)
    {
        // No need to flatten the custom attributes or nested objects since the only usage is for email templates and
        // object passed for events
        $mergedCustomerData = $this->customerRegistry->retrieveSecureData($customer->getId());
        $customerData = $this->dataProcessor
            ->buildOutputDataArray($customer, \Magento\Customer\Api\Data\CustomerInterface::class);
        $mergedCustomerData->addData($customerData);
        $mergedCustomerData->setData('name', $this->customerViewHelper->getCustomerName($customer));
        return $mergedCustomerData;
    }

    /**
     * Create Data Transfer Object of customer candidate
     *
     * @param \Magento\Framework\App\RequestInterface $inputData
     * @param \Magento\Customer\Api\Data\CustomerInterface $currentCustomerData
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    private function populateNewCustomerDataObject(
        \Magento\Framework\App\RequestInterface $inputData,
        \Magento\Customer\Api\Data\CustomerInterface $currentCustomerData
    ) {
        $attributeValues = $this->getCustomerMapper()->toFlatArray($currentCustomerData);
        $customerDto = $this->customerExtractor->extract(
            self::FORM_DATA_EXTRACTOR_CODE,
            $inputData,
            $attributeValues
        );
        $customerDto->setId($currentCustomerData->getId());
        if (!$customerDto->getAddresses()) {
            $customerDto->setAddresses($currentCustomerData->getAddresses());
        }
        if (!$inputData->getParam('email')) {
            $customerDto->setEmail($currentCustomerData->getEmail());
        }

        return $customerDto;
    }

    /**
     * Get Customer Mapper instance
     *
     * @return Mapper
     *
     * @deprecated
     */
    private function getCustomerMapper()
    {
        return ObjectManager::getInstance()->get('Magento\Customer\Model\Customer\Mapper');
    }

    /**
     * Get customer data object
     *
     * @param int $customerId
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    private function getCustomerDataObject($customerId)
    {
        return $this->customerRepo->getById($customerId);
    }
}
