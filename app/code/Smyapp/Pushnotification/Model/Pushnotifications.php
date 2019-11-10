<?php
 
namespace Smyapp\Pushnotification\Model;
 
use Magento\Framework\Model\AbstractModel;
 
class Pushnotifications extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Smyapp\Pushnotification\Model\ResourceModel\Pushnotifications');
    }
}
