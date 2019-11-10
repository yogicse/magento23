<?php

namespace Smyapp\Connector\Controller\products;

class Reviewslist extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->_reviewFactory = $reviewFactory;
        $this->imageHelper = $imageHelper;
        $this->_ratingFactory = $ratingFactory;
        $this->_storeManager = $storeManager;
        $this->request = $context->getRequest();
    }

    public function execute()
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$customerSession = $objectManager->get('Magento\Customer\Model\Session');
if($customerSession->isLoggedIn()) {
   


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_reviewsColFactory = $objectManager->get("\Magento\Review\Model\ResourceModel\Review\CollectionFactory");
        $_storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $collection = $_reviewsColFactory->create()
            ->addStoreFilter($_storeManager->getStore()->getStoreId())
            ->addStatusFilter(\Magento\Review\Model\Review::STATUS_APPROVED)
            ->setDateOrder()->addFieldToFilter('customer_id', $customerSession->getCustomerId());

        $voteFactory = $objectManager->get("Magento\Review\Model\Rating\Option\VoteFactory");

        $totalRating = array(
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        );
        if (count($collection->getdata()) > 0) {
            foreach ($collection->getItems() as $review) {

                // Magento Mobile Shop get rating of products
                $ratingCollection = $voteFactory->create()->getResourceCollection()->setReviewFilter(
                    $review->getReviewId()
                )->addRatingInfo(
                    $this->_storeManager->getStore()->getId()
                )->setStoreFilter(
                    $this->_storeManager->getStore()->getId()
                )->load();
                $review_rating = 0;
                $rating_method = array();

                $l = 0;
                foreach ($ratingCollection as $vote) {
                    $rating_method[$l][$vote->getRatingCode()] = number_format($vote->getPercent() / 20, 1, '.', ',');
                    $review_rating = $vote->getPercent();
                    $ratings[] = $vote->getPercent();
                    $totalRating[($vote->getPercent() / 20)] += 1;
                }
                $l++;
                if ($review_rating) {
                    $rating_by = ($review_rating / 20);
                }
                $product = $objectManager->create('Magento\Catalog\Model\Product')->load($review->getentity_pk_value());
                $productdetail = array(
                    'entity_id' => $product->getId(),
                    'product_type' => $product->getTypeId(),
                    'sku' => $product->getSku(),
                    'name' => $product->getName(),
                    'news_from_date' => $product->getNewsFromDate(),
                    'news_to_date' => $product->getNewsToDate(),
                    'special_from_date' => $product->getSpecialFromDate(),
                    'special_to_date' => $product->getSpecialToDate(),
                    'description' => $product->getDescription(),
                    'short_description' => $product->getShortDescription(),
                    'is_in_stock' => $product->isAvailable(),
                    'regular_price_with_tax' => number_format($product->getPrice(), 2, '.', ''),
                    'final_price_with_tax' => number_format($product->getFinalPrice(), 2, '.', ''),
                    'weight' => $product->getWeight(),

                    'minqty' => $product->getMinQty(),
                    'minsaleqty' => $product->getMinSaleQty(),

                    'image_url' => $this->imageHelper
                        ->init($product, 'product_page_image_large')
                        ->setImageFile($product->getFile())
                        ->resize('250', '250')
                        ->getUrl(),
                );

                $result['rdetails'][] = array(
                    'title' => $review->getTitle(),
                    'review_id' => $review->getreview_id(),
                    'entity_pk_value' => $review->getentity_pk_value(),
                    'description' => $review->getDetail(),
                    'reviewby' => $review->getNickname(),
                    'rating_by' => $rating_method,
                    'rating_date' => date("d M, Y", strtotime($review->getCreatedAt())),
                    'productdetails' => $productdetail,
                );
            }
            $avg = array_sum($ratings) / count($ratings);
        }
        $result['rating'] = number_format($avg / 20, 1, '.', ',');
        $result['total_rating'] = array_reverse($totalRating, true);
    } else {
        echo json_encode(array('status' => 'success', 'message' => "Please Login first"));
        exit;
    }
        echo json_encode(array('status' => 'success', 'message' => $result));
        exit;
    }
}
