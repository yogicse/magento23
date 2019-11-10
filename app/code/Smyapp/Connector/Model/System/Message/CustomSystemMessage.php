<?php
namespace Smyapp\Connector\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;

class CustomSystemMessage implements MessageInterface
{
    const XML_SECURE_KEY = 'Smyapp/secure/key';
    const ACTIVATION_URL = 'https://www.Smyapp.com/mobile-connect';
    const TRNS_EMAIL     = 'trans_email/ident_general/email';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }
    /**
     * Retrieve unique system message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return self::XML_SECURE_KEY;
    }

    /**
     * Check whether the system message should be shown
     *
     * @return bool
     */
    public function isDisplayed()
    {
        if (!$this->scopeConfig->getValue('Smyapp/secure/key')) {
            return true;
        }
    }
    /**
     * Retrieve system message text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getText()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager  = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        /*return __('<strong class="label">Smyapp</strong> extension is not activated yet, <a href="' . self::ACTIVATION_URL . '?email=' . $this->scopeConfig->getValue(self::TRNS_EMAIL) . '&url=' . $storeManager->getStore()->getBaseUrl()
            . '" target="_blank">Click here</a> to activate your extension.');*/
    }
    /**
     * Retrieve system message severity
     * Possible default system message types:
     * - MessageInterface::SEVERITY_CRITICAL
     * - MessageInterface::SEVERITY_MAJOR
     * - MessageInterface::SEVERITY_MINOR
     * - MessageInterface::SEVERITY_NOTICE
     *
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }
}
