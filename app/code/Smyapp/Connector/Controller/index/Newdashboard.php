<?php

namespace Smyapp\Connector\Controller\index;

use Magento\Framework\Event;

class Newdashboard extends \Magento\Framework\App\Action\Action
{
    const XML_CATEGORY_SECTION = 'configuration/dashboard/manage_category_dashboard';

    const XML_PRODUCT_SECTION = 'configuration/dashboard/manage_product_dashboard';

    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $_eventManager;

    public function __construct(
        Event\Manager $eventManager,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\CatalogInventory\Api\StockStateInterface $stockStateInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Framework\Pricing\PriceCurrencyInterface $PriceCurrencyInterface,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Smyapp\Connector\Helper\Products $productHelper
    ) {
        $this->imageHelper              = $imageHelper;
        $this->date                     = $date;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->stockStateInterface      = $stockStateInterface;
        $this->storeManager             = $storeManager;
        $this->customHelper             = $customHelper;
        $this->cache                    = $cache;
        $this->resultJsonFactory        = $resultJsonFactory;
        $this->_eventManager            = $eventManager;
        $this->scopeConfig              = $scopeConfig;
        $this->productStatus            = $productStatus;
        $this->productVisibility        = $productVisibility;
        $this->PriceCurrencyInterface   = $PriceCurrencyInterface;
        $this->priceHelper              = $priceHelper;
        $this->directoryHelper          = $directoryHelper;
        $this->productHelper            = $productHelper;
        parent::__construct($context);
    }

    public function getBaseCurrencyCode()
    {
        return $this->storeManager->getStore()->getBaseCurrencyCode();
    }

    public function execute()
    {
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->viewConfig($this->getRequest()->getHeader('currency'));
        $store          = $this->storeManager->getStore()->getStoreId();
        $result         = $this->resultJsonFactory->create();
        /*check cache for dashboard API*/
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cacheObj      = $objectManager->get('Magento\Framework\App\Cache');
        $cacheKey      = "Smyapp_newdashboard_store_" . $this->storeId;
        $cacheTag      = "Smyapp";
        // if ($cacheObj->load($cacheKey)) {
        //     $resultArray = json_decode($cacheObj->load($cacheKey), true);
        //     $result->setData($resultArray);
        //     return $result;
        // }
        /*check cache for dashboard API*/
        $_objectManager      = \Magento\Framework\App\ObjectManager::getInstance();
        $customerFactory     = $_objectManager->get('\Smyapp\Connector\Model\Dashboard');
        $collectionDashboard = $customerFactory->getCollection()
            ->addFieldToFilter('status', '1')
            ->setOrder('position', 'ASC');
        $resultArray = array();
        if (!empty($collectionDashboard->getData())) {
            $i = 0;
            foreach ($collectionDashboard as $dashKey => $dashValue) {
                $resultArray[$i]['id']                 = $dashValue->getId();
                $resultArray[$i]['title']              = $dashValue->getTileTittle();
                $resultArray[$i]['banner_description'] = $dashValue->getBannerDescription();
                $resultArray[$i]['tile_type']          = $dashValue->getTileType();
                if ($dashValue->getTileType() == 1) {
                    $resultArray[$i]['category_id']     = $dashValue->getCategoryDisplayId();
                    $resultArray[$i]['display_product'] = $dashValue->getCategoryDisplay();
                    $resultArray[$i]['products_array']  = $this->getCategoryProduct($dashValue->getCategoryDisplayId());
                } else if ($dashValue->getTileType() == 2) {
                    $imageMedia = $_objectManager->get('Magento\Store\Model\StoreManagerInterface')
                        ->getStore()
                        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                    $resultArray[$i]['banner_type'] = $dashValue->getBannerType();
                    $resultArray[$i]['category_id'] = $dashValue->getCategoryDisplayId();
                    if ($dashValue->getBannerName()) {
                        $resultArray[$i]['banner_image'] = $imageMedia . '/' . $dashValue->getBannerName();
                    } else {
                        $resultArray[$i]['banner_image'] = '';
                    }
                } else {
                    $resultArray[$i]['display_product'] = $dashValue->getPromotionDisplay();
                    $resultArray[$i]['products_array']  = $this->getPermotionalProdcts($dashValue->getPromotionDisplayId());
                }
                $i++;
            }
            //$this->createNewcache('dashboard', json_encode(array($resultArray)));

            $cacheObj->save(json_encode($resultArray), $cacheKey, [$cacheTag], 300);
            $resultArray = new \Magento\Framework\DataObject($resultArray);
            $this->_eventManager->dispatch('magento_mobile_shop_newdashboard', ["Smyapp_new_dashboard" => $resultArray]);
            $result->setData($resultArray);

            return $result;
        } else {
            $result->setData(['status' => 'false', 'message' => 'No products selected.']);
            return $result;
        }
    }

    /**
     * Get the products according to category id
     * @param  [type] $catId [description]
     * @return Array
     */
    public function getCategoryProduct($catId)
    {
        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $category       = $_objectManager->create('Magento\Catalog\Model\Category')->load($catId);
        $collection     = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addCategoryFilter($category);
        $collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
        $collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $collection->setPageSize(5);

        $new_productlist = $this->getproductCollection($collection);
        return array('title' => $category->getName(), 'count' => count($new_productlist), 'products' => $new_productlist);
    }

    public function getnewproducts()
    {

        $storeId    = $this->storeId;
        $collection = $this->productCollectionFactory->create();
        $todayDate  = date('Y-m-d', time());
        $collection->addAttributeToSelect('*')
            ->setPageSize(5)
            ->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()])
            ->setVisibility($this->productVisibility->getVisibleInSiteIds())
            ->addAttributeToFilter('news_from_date', array('date' => true, 'to' => $todayDate));
        $collection->getSelect()->order('RAND()');
        $new_productlist = $this->getproductCollection($collection);
        return $new_productlist;
    }

    public function getBestsellerProducts()
    {

        $storeId    = $this->storeId;
        $collection = $this->productCollectionFactory->create()->addAttributeToSelect('*');
        $collection->addStoreFilter()
            ->joinField(
                'qty_ordered',
                'sales_bestsellers_aggregated_monthly',
                'qty_ordered',
                'product_id=entity_id',
                'at_qty_ordered.store_id=' . $storeId,
                'at_qty_ordered.qty_ordered > 0',
                'left'
            )->setPageSize(5);
        $collection->getSelect()
            ->group('e.entity_id');
        $collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
        $collection->setVisibility($this->productVisibility->getVisibleInSiteIds());
        $collection->getSelect()->order('RAND()');
        $new_productlist = $this->getproductCollection($collection);
        return $new_productlist;
    }

    public function getsaleproducts()
    {
        $order        = ($this->getRequest()->getParam('order')) ? ($this->getRequest()->getParam('order')) : 'entity_id';
        $dir          = ($this->getRequest()->getParam('dir')) ? ($this->getRequest()->getParam('dir')) : 'desc';
        $page         = ($this->getRequest()->getParam('page')) ? ($this->getRequest()->getParam('page')) : 1;
        $limit        = ($this->getRequest()->getParam('limit')) ? ($this->getRequest()->getParam('limit')) : 5;
        $todayDate    = $this->date->gmtDate();
        $tomorrow     = mktime(0, 0, 0, date('m'), date('d') + 1, date('y'));
        $dateTomorrow = date('m/d/y', $tomorrow);
        $storeId      = $this->storeId;
        $collection   = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*')->addAttributeToFilter('visibility', array(
            'neq' => 1,
        ))->addAttributeToFilter('status', 1)->addAttributeToFilter('special_price', array(
            'neq' => "0",
        ))->addAttributeToFilter('special_from_date', array(
            'date' => true,
            'to'   => $todayDate,
        ))->addAttributeToFilter(array(
            array(
                'attribute' => 'special_to_date',
                'date'      => true,
                'from'      => $dateTomorrow,
            ),
            array(
                'attribute' => 'special_to_date',
                'null'      => 1,
            ),
        ))
            ->setVisibility($this->productVisibility->getVisibleInSiteIds());
        //->addAttributeToSort($order, $dir);
        $collection->getSelect()->order('RAND()');
        /*$pages = $collection->setPageSize($limit)->getLastPageNumber();
        $count = $collection->getSize();
        if (!$count) {
        return array();
        }
        if ($page <= $pages) {
        $collection->setPage($page, $limit);
        $product = $collection->getItems();
        }*/

        $new_productlist = $this->getproductCollection($collection);
        return $new_productlist;
    }

    /*api to get product collection with category filter start*/
    public function getproductCollection($collection)
    {
        $new_productlist = array();
        foreach ($collection as $product) {
            /*if ($product->getTypeId() == 'simple') {
            $regular_price = number_format(
            $this->directoryHelper->currencyConvert($product->getPrice(), $this->storeManager->getStore()->getBaseCurrencyCode(),
            $this->currency), 2, '.', '');
            } else {
            $regular_price = number_format(
            $this->directoryHelper->currencyConvert($product->getFinalPrice(), $this->storeManager->getStore()->getBaseCurrencyCode(),
            $this->currency), 2, '.', '');
            }*/
            $specialprice         = $product->getPriceInfo()->getPrice('special_price')->getAmount()->getValue();
            $final_price_with_tax = $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
            if ($specialprice >= $final_price_with_tax) {
                $specialprice = $final_price_with_tax;
            }
            $new_productlist[] = array(
                'entity_id'              => $product->getId(),
                'sku'                    => $product->getSku(),
                'name'                   => $product->getName(),
                'news_from_date'         => $product->getNewsFromDate() ?: '',
                'news_to_date'           => $product->getNewsToDate() ?: '',
                'special_from_date'      => $product->getSpecialFromDate() ?: '',
                'special_to_date'        => $product->getSpecialToDate() ?: '',
                'image_url'              => $this->imageHelper
                    ->init($product, 'product_page_image_large')
                    ->setImageFile($product->getFile())
                    ->resize('300', '300')
                    ->getUrl(),
                'url_key'                => $product->getProductUrl(),
                'qty'                    => $this->stockStateInterface->getStockQty($product->getId(), $product->getStore()->getWebsiteId()),
                'review'                 => array(),
                'symbol'                 => $this->customHelper->getCurrencysymbolByCode($this->currency),
                'currency_rate'          => $this->storeManager->getStore()->getCurrentCurrencyRate(),

                /*'final_price_with_tax'   => number_format(
                $this->directoryHelper->currencyConvert(
                $product->getFinalPrice(),
                $this->getBaseCurrencyCode(),
                $this->currency
                ),
                2,
                '.',
                ''
                ),

                'regular_price_with_tax' => $regular_price,
                'specialprice'           => number_format(
                $product->getId(),
                2,
                '.',
                ''
                ),*/
                'regular_price_with_tax' => number_format($product->getPrice(), 2, '.', ''),
                'final_price_with_tax'   => number_format($product->getFinalPrice(), 2, '.', ''),
                'specialprice'           => number_format($specialprice, 2, '.', ''),
                'wishlist'               => $this->productHelper->checkWishlist($product->getId()),
            );
        }
        return $new_productlist;
    }
/*api to get product collection with category filter end*/

    public function createNewcache($key, $data, $lifeTime = 300)
    {
        try {
            $om        = \Magento\Framework\App\ObjectManager::getInstance();
            $cache     = $om->get('Magento\Framework\App\CacheInterface');
            $cache_key = "Smyapp_" . $key . "_store";
            $cache->save($data, $cache_key, array("Smyapp"), $lifeTime);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the Products according to User need
     */
    public function getPermotionalProdcts($key)
    {
        switch ($key) {
            case '1':
                $newproducts = $this->getnewproducts();

                $getnewproducts = array(
                    'title'    => __('New Products'),
                    'count'    => count($newproducts),
                    'type'     => 'slider',
                    'products' => $newproducts,
                );

                $array = $getnewproducts;
                return $array;
                break;
            case '2':
                $newproductssale = $this->getsaleproducts();

                $getnewproductssale = array(
                    'title'    => __('Sale Products'),
                    'count'    => count($newproductssale),
                    'type'     => 'slider',
                    'products' => $newproductssale,
                );

                $array = $getnewproductssale;
                return $array;
                break;
            case '0':
                $getBestseller = $this->getBestsellerProducts();

                $getBestsellerProducts = array(
                    'title'    => __('Top Products'),
                    'count'    => count($getBestseller),
                    'type'     => 'slider',
                    'products' => $getBestseller,
                );

                $array = $getBestsellerProducts;
                return $array;
                break;
        }
    }
}
