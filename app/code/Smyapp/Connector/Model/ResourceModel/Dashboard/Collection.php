<?php
namespace Smyapp\Connector\Model\ResourceModel\Dashboard;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected function _construct()
    {
        $this->_init(

            'Smyapp\Connector\Model\Dashboard',
            'Smyapp\Connector\Model\ResourceModel\Dashboard'
        );        
    }
}
