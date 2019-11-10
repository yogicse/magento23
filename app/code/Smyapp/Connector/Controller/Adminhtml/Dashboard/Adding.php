<?php
namespace  Smyapp\Connector\Controller\Adminhtml\Dashboard;

use Magento\Framework\Controller\ResultFactory;

class Adding extends \Magento\Backend\App\Action
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
    }
    public function execute()
    {
        $rowId   = (int)$this->getRequest()->getParam('id');
        $rowData = $this->_objectManager->create('Smyapp\Connector\Model\Dashboard');
        if ($rowId) {
            $rowData = $rowData->load($rowId);
            $rowName = $rowData->getName();
            if (!$rowData->getId()) {
                $this->messageManager->addError(__('row data no longer exist.'));
                $this->_redirect('Smyapp_Connector/dashboard/index');
                return;
            }
        }
        $this->_coreRegistry->register('row_data', $rowData);
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $title      = $rowId ? _('Edit Dashboard Tile') . $rowName : _('Add Dashboard Tile');
        $resultPage->getConfig()->getTitle()->prepend($title);
        return $resultPage;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Smyapp_Connector::adding');
    }
}
