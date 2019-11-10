<?php
namespace Smyapp\Connector\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

class ControllerActionPredispatch implements ObserverInterface
{
    const XML_SECURE_KEY        = 'Smyapp/secure/key';
    const ACTIVATION_URL        = 'https://www.Smyapp.com/user/Smyapp_verifiy';
    const TRNS_EMAIL            = 'trans_email/ident_general/email';
    const XML_SECURE_KEY_STATUS = 'Smyapp/key/status';
    protected $logger;
    protected $response;

    public function __construct(
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Backend\App\Action $action,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Locale\Resolver $resolver,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\Session $coreSession,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->logger                = $loggerInterface;
        $this->authSession           = $authSession;
        $this->urlInterface          = $urlInterface;
        $this->action                = $action;
        $this->coreRegistry          = $coreRegistry;
        $this->scopeConfig           = $scopeConfig;
        $this->resolver              = $resolver;
        $this->resourceConfig        = $resourceConfig;
        $this->storeManager          = $storeManager;
        $this->coreSession           = $coreSession;
        $this->cacheTypeList         = $cacheTypeList;
        $this->response              = $response;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->responseFactory       = $responseFactory;
        $this->messageManager        = $messageManager;
        $this->request               = $request;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $adminsession = \Magento\Security\Model\AdminSessionInfo::LOGGED_IN;
        $url          = $this->urlInterface->getCurrentUrl();
        $url_path     = parse_url($url, PHP_URL_PATH);
        $token        = pathinfo($url_path, PATHINFO_BASENAME);
        $decode       = $this->request->getParam('mms_id');
        $SmyappAppData   = '';

        if ($decode and !$this->coreRegistry->registry('mms_app_data')) {
            $param = base64_decode($decode);
            $this->coreRegistry->register('mms_app_data', $param);
            $SmyappAppData = $this->coreRegistry->registry('mms_app_data');
        }
        $current = $this->scopeConfig->getValue('Smyapp/secure/key');


        if (!$this->scopeConfig->getValue(self::XML_SECURE_KEY) and $adminsession) {
            $static_url = 'https://www.Smyapp.com/user/buildApp?key_info=';
            $email      = base64_encode($this->scopeConfig->getValue(self::TRNS_EMAIL));
            $url        = base64_encode($this->storeManager->getStore()->getBaseUrl());
            $key        = base64_encode('email=' . $email . '&url=' . $url);
            $href       = $static_url . $key;
            $this->messageManager->addNotice(__('Smyapp extension is not activated yet, <a href="'.$href.'">Click here</a> to activate your extension.'));
        }
        
        if ((!$current) and $adminsession and $SmyappAppData != '') {
            if ((!$current)) {
                $str        = self::ACTIVATION_URL;
                $url        = $str . '?mms_id=';
                $final_url  = $url . '' . $SmyappAppData;
                $final_urls = $str;
                $this->resourceConfig->saveConfig(self::XML_SECURE_KEY, $SmyappAppData, 'default', 0);
                $this->resourceConfig->saveConfig(self::XML_SECURE_KEY_STATUS, '1', 'default', 0);
                $lang = $this->resolver->getLocale();
                //$lang = explode("_",$locale);

                $SmyappData                              = array();
                $SmyappData[0]['mms_id']                 = base64_encode($SmyappAppData);
                $SmyappData[0]['default_store_name']     = $this->storeManager->getStore()->getCode();
                $SmyappData[0]['default_store_id']       = $this->storeManager->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
                $SmyappData[0]['default_view_id']        = $this->storeManager->getStore()->getId();
                $SmyappData[0]['default_store_currency'] = $this->storeManager->getStore()->getCurrentCurrencyCode();
                $SmyappData[0]['language']               = $lang;
                $SmyappData[0]['status']                 = 'true';
                $this->cacheTypeList->cleanType('config');
                $this->coreSession->setAppDatas($SmyappData[0]);
                $this->coreRegistry->unregister('mms_app_data');
                $customerBeforeAuthUrl = $this->urlInterface->getUrl('Smyapp_Connector/system_connector/index');
                $this->responseFactory->create()->setRedirect($customerBeforeAuthUrl)->sendResponse();
                exit();
            } elseif ($current != '' and $adminsession->isLoggedIn() and $decode != '') {
                $str        = self::ACTIVATION_URL;
                $url        = $str . '?mms_id=';
                $final_url  = $url . '' . $SmyappAppData;
                $final_urls = $str;
                $this->resourceConfig->saveConfig(self::XML_SECURE_KEY, $SmyappAppData);
                $this->resourceConfig->saveConfig(self::XML_SECURE_KEY_STATUS, '1');
                $lang = Mage::app()->getLocale();

                $SmyappData[0]['mms_id']                 = base64_encode($SmyappAppData);
                $SmyappData[0]['default_store_name']     = $this->storeManager->getStore()->getCode();
                $SmyappData[0]['default_store_id']       = $this->storeManager->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
                $SmyappData[0]['default_view_id']        = $this->storeManager->getStore()->getId();
                $SmyappData[0]['default_store_currency'] = $this->storeManager->getStore()->getCurrentCurrencyCode();
                $SmyappData[0]['language']               = $lang;
                $SmyappData[0]['status']                 = 'true';

                $this->cacheTypeList->cleanType('config');
                $this->coreSession->setAppDatas($SmyappData[0]);
                $this->coreRegistry->unregister('mms_app_data');
                $customerBeforeAuthUrl = $this->urlInterface->getUrl('Smyapp_Connector/system_connector/index');
                $this->responseFactory->create()->setRedirect($customerBeforeAuthUrl)->sendResponse();
                exit();
            }
        }
    }
}
