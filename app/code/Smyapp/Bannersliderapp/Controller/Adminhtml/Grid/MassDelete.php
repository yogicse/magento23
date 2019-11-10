<?php
namespace Smyapp\Bannersliderapp\Controller\Adminhtml\Grid;

use Smyapp\Bannersliderapp\Model\ResourceModel\Grid\CollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends \Magento\Backend\App\Action
{
    protected $_filter;
    protected $_collectionFactory;
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->_filter            = $filter;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context);
    }
    public function execute()
    {   
        $this->getRequest()->getParams();
        $collection    = $this->_filter->getCollection($this->_collectionFactory->create());
        
        foreach ($collection->getItems() as $auctionProduct) {
           $row = $this->_objectManager->get('Smyapp\Bannersliderapp\Model\Grid')->load($auctionProduct->getBannerId());
            $row->delete();
            }

          
        $this->messageManager->addSuccess(
            __('A total of %1 record(s) have been deleted.', count($collection->getItems()))
        );
       

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Smyapp_Bannersliderapp::row_data_delete');
    }
}
