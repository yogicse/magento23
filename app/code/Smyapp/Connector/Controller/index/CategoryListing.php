<?php
namespace Smyapp\Connector\Controller\index;
use Magento\Framework\Serialize\Serializer\Serialize;

class CategoryListing extends \Magento\Framework\App\Action\Action
{
    protected $_withProductCount;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Category\TreeFactory $categoryTreeFactory,
        \Smyapp\Connector\Helper\Data $customHelper
    ) {
        $this->resultJsonFactory   = $resultJsonFactory;
        $this->storeManager        = $storeManager;
        $this->categoryTreeFactory = $categoryTreeFactory;
        $this->customHelper        = $customHelper;
        parent::__construct($context);
    }
    public function execute()
    {
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $result         = $this->resultJsonFactory->create();
        return $result->setData($this->getCategoryTree());
    }

    public function getCategoryTree()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cacheObj = $objectManager->get('Magento\Framework\App\Cache');
        $cacheKey = "Smyapp_category_sore_".$this->storeId;
        $cacheTag = "Smyapp";
        // if ($cacheObj->load($cacheKey)) {
        //     return $this->unserialize($cacheObj->load($cacheKey)); 
        // }
        $recursionLevel = 3;
        $storeId = $this->storeManager->getStore()->getId();

        if ($storeId) {
            $store  = $this->storeManager->getStore();
            $parent = $store->getRootCategoryId();
        } else {
            $parent = \Magento\Catalog\Model\Category::TREE_ROOT_ID;
        }

        $tree  = $this->categoryTreeFactory->create();
        $nodes = $tree->loadNode($parent)->loadChildren($recursionLevel)->getChildren();
        $tree->addCollectionData(null, false, $parent, true, true);

        $categoryTreeData = array();
        foreach ($nodes as $node) {
            if ($node->getIsActive() && $node->getIncludeInMenu()) {
                $categoryTreeData[] = $this->getNodeChildrenData($node);
            }
        }
        $cacheObj->save(serialize($categoryTreeData), $cacheKey, [$cacheTag], 300);
        return $categoryTreeData;
    }

    protected function getNodeChildrenData(\Magento\Framework\Data\Tree\Node $node)
    {
        $data = array(
            'id'    => $node->getData('entity_id'),
            'title' => $node->getData('name'),
            'url'   => $node->getData('url_key'),
        );

        foreach ($node->getChildren() as $childNode) {
            if (!array_key_exists('children', $data)) {
                $data['children'] = array();
            }
            if ($childNode->getIsActive()) {
                $data['children'][] = $this->getNodeChildrenData($childNode);
            }
        }
        return $data;
    }
}
