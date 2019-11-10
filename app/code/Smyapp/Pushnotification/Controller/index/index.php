<?php
 
namespace Smyapp\Pushnotification\Controller\Index;
 
use Magento\Framework\App\Action\Context;
use \Smyapp\Pushnotification\Model\PushnotificationsFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;
 
    public function __construct(Context $context, 
        PushnotificationsFactory $modelNotificationsFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    )
    {
        $this->_resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->modelNotifications = $modelNotificationsFactory;
        $this->scopeConfig        = $scopeConfig;
        $this->_storeManager      = $storeManager;
        $this->customHelper      = $customHelper;
        $this->jsonHelper        = $jsonHelper;
        parent::__construct($context);
    }
 
    public function execute()
    {
        $this->customHelper->loadParent($this->getRequest()->getHeader('token'));
        $this->storeId  = $this->customHelper->storeConfig($this->getRequest()->getHeader('storeid'));
        $this->viewId   = $this->customHelper->viewConfig($this->getRequest()->getHeader('viewid'));
        $this->currency = $this->customHelper->currencyConfig($this->getRequest()->getHeader('currency'));
        $params = file_get_contents("php://input");            
        $finalInput = json_decode($params, true);



        $result         = $this->resultJsonFactory->create();
        $model    = $this->modelNotifications->create();
        $deviceFilter = $model->getCollection()
            ->addFieldToFilter('device_id', array('eq' => $finalInput['device_id']))
            ->getFirstItem();
        if (count($deviceFilter)) {

            if ($finalInput['registration_id'] != $deviceFilter['registration_id']) {
               
                $finalInput['id'] = $deviceFilter['id'];
                 $finalInput['user_id'] = @$finalInput['user_id'];
                $finalInput['create_date'] = $deviceFilter['create_date'];
                $finalInput['update_date'] = date("Y-m-d");
                $finalInput['app_status'] = 1;
                $model->setData($finalInput)->save();
            //     $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                
            //     $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            //     $connection = $resource->getConnection();
            //     $tableName = $resource->getTableName('Smyapp_Pushnotification');
            
            //     $sql = "Select * FROM Smyapp_Pushnotification WHERE device_id LIKE '%".$finalInput['device_id']."%'";
                
                
            // $resultdata = $connection->fetchRow($sql);
           
            // $sql1 = "Update " . $tableName . " Set user_id = '".$finalInput['user_id']."' where device_id LIKE '%".$finalInput['device_id']."%'";
           

            // $connection->query($sql1);
            } else {
                
               $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $tableName = $resource->getTableName('Smyapp_Pushnotification');
            
                $sql = "Select * FROM Smyapp_Pushnotification WHERE device_id LIKE '%".$finalInput['device_id']."%'";
                
              
                
            $resultdata = $connection->fetchRow($sql);
           
             $sql1 = "Update " . $tableName . " Set user_id = '".$finalInput['user_id']."' where device_id LIKE '%".$finalInput['device_id']."%'";
           

            $connection->query($sql1);
          
               
            }

        } else {
           
            if(!empty($finalInput['device_id'])) {
                $finalInput['user_id'] = @$finalInput['user_id'];
                $finalInput['create_date'] = date("Y-m-d");
                $finalInput['update_date'] = date("Y-m-d");
                $finalInput['app_status'] = 1;
                $model->setData($finalInput)->save();    
            }
            
        }
        return $result->setData(['status' => "success", 'message' => 'Device registered.']);
    }
}