<?php
 
namespace Smyapp\Pushnotification\Block\Catalog\Product;
 
class View extends \Magento\Catalog\Block\Product\View
{
    /**
     * Retrieve current product model
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        // logging to test override    
        // die('override block');
        return parent::getProduct();
    }
}
?>

