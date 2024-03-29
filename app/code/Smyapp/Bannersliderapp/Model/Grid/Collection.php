<?php

namespace Smyapp\Bannersliderapp\Model\ResourceModel\Grid;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';
    /**
     * Define resource model.
     */
    protected function _construct()
    {
        $this->_init('Smyapp\Bannersliderapp\Model\Grid', 'Smyapp\Bannersliderapp\Model\ResourceModel\Grid');
    }
}
