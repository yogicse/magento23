<?php
namespace Smyapp\Connector\Helper;

use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;

class Filters extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        CollectionFactory $factory
    ) {
        $this->collectionFactory = $factory;
        $this->_logger           = $logger;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    public function getFilterByCategory($categoryId)
    {
        try {
            $filterableAttributes = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Catalog\Model\Layer\Category\FilterableAttributeList::class);
            $layerResolver        = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Catalog\Model\Layer\Resolver::class);

            $filterList = \Magento\Framework\App\ObjectManager::getInstance()->create(
                \Magento\Catalog\Model\Layer\FilterList::class,
                [
                    'filterableAttributes' => $filterableAttributes,
                ]
            );
            $category = $categoryId;
            $layer    = $layerResolver->get();
            $layer->setCurrentCategory($category);
            $filters = $filterList->getFilters($layer);

            $resultfilters = array();
            $k             = 0;
            foreach ($filters as $filter) {
                if ($filter->getName() == 'Price') {
                        $resultfilters[$k]['label'] = $filter->getName();
                        $resultfilters[$k]['code']  = $filter->getRequestVar();
                        $data = array();
                        $counter = count($filter->getItems());
                        $i = 0;
                        foreach ($filter->getItems() as $item) {

                            if (is_numeric(substr($item->getValue(), 0, 1))) {
                                $value = $item->getValue();
                            } else {
                                $value = '0'.$item->getValue();
                            }
                            if (!is_numeric(substr($value, -1))) {
                                $value = $item->getValue().'0';
                            }
                            /*$myfilters                    = array();
                            $myfilters['code']            = $value;
                            $myfilters['label']           = strip_tags($item->getLabel());
                            $resultfilters[$k]['value'][] = $myfilters;*///$this->getpricerange($category);

                            if(!$i) {

                                $minValue = explode('-', $value);
                                $data['min'] = $minValue[0];
                                $data['step'] = $minValue[1] - $minValue[0];
                            }
                            if ($i == ($counter -1)) {
                                $minValue = explode('-', $value);
                                $data['max'] = $minValue[1]?:$minValue[0];
                            }
                            $i++;

                        }

                        if(isset($value)){
                        $myfilters['code']            = @$value;
                        $myfilters['label']           = strip_tags(@$item->getLabel());
                        $resultfilters[$k]['value'] = $data;
                        } else {
                            echo json_encode(['status' => 'success' ,'data'=> []]);
                            exit;
                        }

                       
                    continue;
                }
                if ($filter->getItems()) {
                    $resultfilters[$k]['label'] = $filter->getName();
                    $resultfilters[$k]['code']  = $filter->getRequestVar();
                }
                foreach ($filter->getItems() as $item) {
                    $myfilters                    = array();
                    $myfilters['code']            = $item->getValue();
                    $myfilters['label']           = $item->getLabel();
                    $resultfilters[$k]['value'][] = $myfilters;
                }
                $k++;
            }
            $json = array('status' => 'success', 'category' => null, 'filters' => array_values($resultfilters));
        } catch (Exception $e) {
            $json = array('status' => 'error', 'message' => $e->getMessage());
        }
        echo json_encode(array($json));exit();
        // echo '<pre>'; print_r(array($json)); die;
    }

    public function getpricerange($maincategoryId) {
        $pricerange =array();
        $layer = $layerResolver        = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Catalog\Model\Layer\Resolver::class)->get();
        $category = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Catalog\Model\CategoryRepository::class)->get($maincategoryId);
        
        /*$category = $this->collectionFactory->load($maincategoryId);
         */
        if ($category->getId()) {
            $origCategory = $layer->getCurrentCategory();
            $layer->setCurrentCategory($category);
        }

        $r=$this->Price->setLayer($layer);

        $range = $layer->getPriceRange();
        $dbRanges = $layer->getRangeItemCounts($range);
        $data = array();
        foreach ($dbRanges as $index => $count) {
            $data[] = array(
            'label' => $this->_renderItemLabel($range, $index),
            'value' => $this->_renderItemValue($range, $index),
            'count' => $count,
            );
        }
        return $data;
    }
}
