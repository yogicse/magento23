<?php
namespace Smyapp\Connector\Block\Adminhtml;

class Dashboard extends \Magento\Backend\Block\Widget\Form\Container
{
    protected $_coreRegistry = null;
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

	protected function _construct()
	{
        $this->_objectId   = 'row_id';
        $this->_blockGroup = 'Smyapp_Connector';
        $this->_controller = 'adminhtml_dashboard';
        parent::_construct();
        if ($this->_isAllowedAction('Smyapp_Bannersliderapp::adding')) {
            $this->buttonList->update('save', 'label', __('Save'));
        } else {
            $this->buttonList->remove('save');
        }
	}
	public function getHeaderText()
    {
        return __('Add Dashboard Tile');
    }
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
    public function getFormActionUrl()
    {
        if ($this->hasFormActionUrl()) {
            return $this->getData('form_action_url');
        }
        return $this->getUrl('*/*/save');
    }
}
