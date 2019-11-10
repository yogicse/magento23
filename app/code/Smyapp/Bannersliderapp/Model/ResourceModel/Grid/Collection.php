<?php
namespace Smyapp\Bannersliderapp\Model\ResourceModel\Grid;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'banner_id';
    protected function _construct()
    {
        $this->_init('Smyapp\Bannersliderapp\Model\Grid', 'Smyapp\Bannersliderapp\Model\ResourceModel\Grid');
    }
}
