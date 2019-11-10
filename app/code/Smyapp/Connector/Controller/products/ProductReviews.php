<?php
 
namespace Smyapp\Connector\Controller\products;
 

 
class ProductReviews extends \Magento\Framework\App\Action\Action
{
    public function __construct(
           \Magento\Framework\App\Action\Context $context,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->_reviewFactory = $reviewFactory;
        $this->_ratingFactory = $ratingFactory;
        $this->_storeManager = $storeManager;
        $this->request       = $context->getRequest();
    }
 
    public function execute()
    {

        $productId = $this->request->getParam('productid');
        $productTitle = $this->request->getParam('producttitle');
        $productDetail = $this->request->getParam('productdetail');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        if($customerSession->isLoggedIn()) {
            $customerId= $customerSession->getCustomerId();
           
                $_review = $objectManager->get("Magento\Review\Model\Review")
                ->setEntityPkValue($productId)//product Id
                ->setStatusId(\Magento\Review\Model\Review::STATUS_PENDING)// approved
                ->setTitle($productTitle)
                ->setDetail($productDetail)
                ->setEntityId(1)
                ->setStoreId(1)
                ->setStores(1)
                ->setCustomerId($customerId)//get dynamically here
                ->setNickname($customerSession->getCustomer()->getName())
                ->save();


           

            //just Assume user selected rating options

            $ratingOptions = array(
                '1' => $this->request->getParam('quality'),
                '2' => $this->request->getParam('value'),
                '3' => $this->request->getParam('price'),
                '4' => $this->request->getParam('rating'),
            );

             

            foreach ($ratingOptions as $ratingId => $optionIds) {
            $objectManager->get("Magento\Review\Model\Rating")
                ->setRatingId($ratingId)
                ->setReviewId($_review->getId())
                ->addOptionVote($optionIds, $productId);
            }

            $_review->aggregate();
          echo json_encode(array('status' => 'success','message'=>'Rating has been saved success !!!!!!!!!'));
            exit;
        } else {
            echo json_encode(array('status' => 'success','message'=>'Please Login First'));
            exit;
        }

    }
}
