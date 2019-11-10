<?php
 
namespace Smyapp\Connector\Controller\products;
 

 
class Productshare extends \Magento\Framework\App\Action\Action
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

        $producturl = $this->request->getParam('url');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $tableName = $resource->getTableName('url_rewrite');
            
            $sql = "Select * FROM url_rewrite WHERE request_path='".$producturl."' ";
            $resultdata = $connection->fetchRow($sql);

           if($resultdata){
             echo json_encode(array('status' => 'success','data'=>$resultdata));
            exit;
        } else {
             echo json_encode(array('status' => 'error','data'=>'Record Not Found'));
            exit;
        }
          


        
          
     }
}
