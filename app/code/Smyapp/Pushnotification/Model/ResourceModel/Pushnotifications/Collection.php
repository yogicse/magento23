<?php
 
namespace Smyapp\Pushnotification\Model\ResourceModel\Pushnotifications;
 
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
 
class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Smyapp\Pushnotification\Model\Pushnotifications',
            'Smyapp\Pushnotification\Model\ResourceModel\Pushnotifications'
        );
    }
}