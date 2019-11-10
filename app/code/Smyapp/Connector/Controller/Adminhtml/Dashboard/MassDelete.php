<?php
namespace Smyapp\Connector\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Smyapp\Connector\Model\ResourceModel\Dashboard\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;

use Magento\Framework\App\ResponseInterface;



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
        $collection    = $this->_filter->getCollection($this->_collectionFactory->create());
        $recordDeleted = 0;
        
//        foreach ($collection->getItems() as $auctionProduct) {
//            print_r($auctionProduct->getId());
//             
//            $auctionProduct->setId($auctionProduct->getId());
//            $auctionProduct->delete();
//            $recordDeleted++;
//        }
        
        foreach ($collection->getItems() as $auctionProduct) {
$row = $this->_objectManager->get('Smyapp\Connector\Model\Dashboard')->load($auctionProduct->getId());
$row->delete();
}

        
        
       
        $this->messageManager->addSuccess(
            __('A total of %1 record(s) have been deleted.', count($collection->getItems()))
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Smyapp_Connector::massdelete');
    }
}
