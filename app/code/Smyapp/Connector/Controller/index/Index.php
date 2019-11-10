<?php
namespace Smyapp\Connector\Controller\index;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * Product Collection
     *
     * @var array
     */
    protected $_productCollectionFactory;

    /**
     * category Collection
     *
     * @var array
     */
    protected $_categoryFactory;

    /**
     * Product Id
     *
     * @var int
     */
    protected $_page;

    /**
     * order
     *
     * @var desc, asc
     */
    protected $_order;

    /**
     * Collection
     *
     * @var null
     */
    protected $_productFinalCollection;

    /**
     * limt
     *
     * @var int
     */
    protected $_limit;

    /**
     * Sort
     *
     * @var int
     */
    protected $_dir;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Smyapp\Connector\Helper\Products $productHelper,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Directory\Helper\Data $directoryHelper
    ) {
        $this->_stockItemRepository      = $stockItemRepository;
        $this->_categoryFactory          = $categoryFactory;
        $this->imageHelper               = $imageHelper;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_cacheTypeList            = $cacheTypeList;
        $this->_reviewFactory            = $reviewFactory;
        $this->_storeManager             = $storeManager;
        $this->customHelper              = $customHelper;
        $this->productHelper             = $productHelper;
        $this->resultJsonFactory         = $resultJsonFactory;
        $this->jsonHelper                = $jsonHelper;
        $this->request                   = $context->getRequest();
        $this->directoryHelper           = $directoryHelper;

        parent::__construct($context);
    }

    /**
     * @param cmd,categoryid,page,order,limit,filters,dir
     * @description : get Product listing
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $this->currency = $this->getRequest()->getHeader('currency');
        $params = file_get_contents("php://input");
        $params = $this->jsonHelper->jsonDecode($params, true);
        $cmd = $params['cmd'];
        if (!isset($params['cmd'])) {
            $result->setData(['status' => 'error', 'message' => 'Required parameter is missing.']);
            return $result;
        }
        switch ($cmd) {
            case 'catalog':
                $categoryid = $params['categoryid'];
                if (!isset($categoryid)) {
                    $result->setData(['status' => 'error', 'message' => 'Category Id is required.']);
                    return $result;
                }
                $this->_page  = isset($params['page']) ? $params['page'] : 1;
                $this->_limit = isset($params['limit']) ? $params['limit'] : 10;
                $this->_order = isset($params['order']) ? $params['order'] : 'entity_id';
                $this->_dir   = isset($params['dir']) ? $params['dir'] : 'desc';

                $collection = $this->getProductCollectionFromCatId($categoryid);

                $price_filter = array();
                /*filter added*/
                if (isset($params['filter'])) {
                    $filters      = $params['filter'];
                    foreach ($filters as $key => $filter) {
                        if (sizeof($filter)) {
                            if ($filter['code'] == 'price') {
                                $price        = $filter['value'];
                                $price_filter = array('gt' => $price['minPrice'], 'lt' => $price['maxPrice']);
                                $collection   = $collection->addAttributeToFilter('price', array('gteq' => $price['minPrice']));
                                $collection   = $collection->addAttributeToFilter('price', array('lteq' => $price['maxPrice']));
                                $collection->addFinalPrice()
                                   ->getSelect()
                                   ->where('price_index.final_price >= ' . $price['minPrice']);
                            } else {
                                $tableAlias    = $filter['code'] . '_idx';
                                $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
                                $resource      = $objectManager->get('Magento\Framework\App\ResourceConnection');
                                $connection    = $resource->getConnection();

                                $attributeModel = $objectManager->get('Magento\Eav\Model\Entity\Attribute')->getCollection()->addFieldToFilter('attribute_code', $filter['code']);

                                if ($attributeModel) {
                                    $attributeId = $attributeModel->getFirstItem()->getId();
                                } else {
                                    continue;
                                }
                                $conditions = [
                                    "{$tableAlias}.entity_id = e.entity_id",
                                    $connection->quoteInto("{$tableAlias}.attribute_id = ?", $attributeId),
                                    $connection->quoteInto("{$tableAlias}.store_id = ?", $collection->getStoreId()),
                                ];
                                $filterCode = array();
                                if (sizeof($filter['value']) > 1) {
                                    foreach($filter['value'] as $filterCodeValues) {
                                        $filterCode[] = $connection->quoteInto("{$tableAlias}.value = ?", $filterCodeValues['code']);
                                    }
                                } else {
                                    $filterCode[] = $connection->quoteInto("{$tableAlias}.value = ?", $filter['value'][0]['code']);
                                }

                                $filterArray = array_merge($conditions, $filterCode);
                                $collection->getSelect()->join(
                                    [$tableAlias => 'catalog_product_index_eav'],
                                    implode(' AND ', $filterArray),
                                    []
                                )->group('e.entity_id');
                            }
                        }
                    }
                }
                /*filter added*/

                if (isset($params['min'])) {
                    $collection = $collection->addAttributeToFilter('price', array('gt' => $params['min']));
                }
                if (isset($params['min'])) {
                    $collection = $collection->addAttributeToFilter('price', array('lt' => $params['max']));
                }
                $collection =   $collection->setOrder($this->_order, $this->_dir);
                $pages      = $collection->setPageSize($this->_limit)->getLastPageNumber();

                if ($this->_page <= $pages) {
                    $collection->setPageSize($this->_limit)->setCurPage($this->_page);
                    $this->getProductlist($collection, 'catalog', $price_filter);
                }

                $count = $collection->getSize();

                if (!$count) {
                    $result->setData([]);
                    return $result;
                }

                if (sizeof($this->_productFinalCollection)) {
                    $result->setData($this->_productFinalCollection);
                    return $result;
                } else {
                    $result->setData([]);
                    return $result;
                }
                break;
        }
    }

    /**
     * @param categoryId
     * @description : get Product Collection form cat id
     * @return array
     */
    public function getProductCollectionFromCatId($categoryId)
    {

        $category = $this->_categoryFactory->create()->load($categoryId);
        if ($category->getdata()) {
            $collection = $this->_productCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addCategoryFilter($category);
            $collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
            $collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
            return $collection;
        } else {
            $result = $this->resultJsonFactory->create();
            $result->setData(['status' => 'error', 'message' => 'Category Id not found.']);
            return $result;
        }
    }

    protected function __applyFilters($collection)
    {
        $collection->setOrder($this->_order, $this->_dir);
        return $collection;
    }

    public function getProductlist($products, $mod = 'product')
    {

        $productlist = array();
        foreach ($products as $product) {
            if ($mod == 'catalog') {
                $this->_reviewFactory->create()->getEntitySummary($product, $this->_storeManager->getStore()->getId());
                $rating_final = (int) $product->getRatingSummary()->getRatingSummary() / 20;
            }
             if ($product->getTypeId() == "virtual") {
                $qty = true;
            }elseif($product->getTypeId() == "configurable") {
                $qty = $this->_stockItemRepository->get($product->getId())->getIsInStock();
            } else {
                try {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                   $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
                    $qty = $StockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
                } catch(Exception $e) {

                }

            }

            $productlist[] = $this->__getListProduct($product, $qty, $rating_final);
        }

        $this->_productFinalCollection = $productlist;
    }

    protected function __getListProduct($product, $qty, $rating_final)
    {

        /*if ($product->getTypeId() == 'simple') {
            $regular_price = number_format(
                $this->directoryHelper->currencyConvert($product->getPrice(), $this->_storeManager->getStore()->getBaseCurrencyCode(),
                    $this->currency), 2, '.', '');
        } else {
            $regular_price = number_format(
                $this->directoryHelper->currencyConvert($product->getFinalPrice(), $this->_storeManager->getStore()->getBaseCurrencyCode(),
                    $this->currency), 2, '.', '');
        }*/
        $specialprice = $product->getPriceInfo()->getPrice('special_price')->getAmount()->getValue();
        $final_price_with_tax = $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
        if($specialprice >= $final_price_with_tax){
            $specialprice = $final_price_with_tax;
        }
        $result = array(
            'entity_id'              => $product->getId(),
            'sku'                    => $product->getSku(),
            'name'                   => $product->getName(),
            'news_from_date'         => $product->getNewsFromDate(),
            'news_to_date'           => $product->getNewsToDate(),
            'special_from_date'      => $product->getSpecialFromDate(),
            'special_to_date'        => $product->getSpecialToDate(),
            'image_url'              => $this->imageHelper
                ->init($product, 'product_page_image_large')
                ->setImageFile($product->getFile())
                ->resize('300', '300')
                ->getUrl(),
            'url_key'                => $product->getProductUrl(),
            /*'final_price_with_tax'   => number_format(
                $this->directoryHelper->currencyConvert(
                    $product->getFinalPrice(),
                    $this->_storeManager->getStore()->getBaseCurrencyCode(),
                    $this->currency
                ),
                2,
                '.',
                ''
            ),
            'regular_price_with_tax' => $regular_price,
            'specialprice'           => number_format(
                $specialprice,
                2,
                '.',
                ''
            ),*/
            'regular_price_with_tax' => number_format($product->getPrice(), 2, '.', ''),
            'final_price_with_tax'   => number_format($product->getFinalPrice(), 2, '.', ''),
            'specialprice'           => number_format($specialprice, 2, '.', ''),
            'symbol'                 => $this->customHelper->getCurrencysymbolByCode($this->currency),
            'qty'                    => $qty,
            'product_type'           => $product->getTypeId(),
            'rating'                 => $rating_final,
            'wishlist'               => $this->productHelper->checkWishlist($product->getId()),

        );
        return $result;
    }
}
