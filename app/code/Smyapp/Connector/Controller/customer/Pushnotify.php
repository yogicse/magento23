<?php
namespace Smyapp\Connector\Controller\customer;

class Pushnotify extends \Magento\Framework\App\Action\Action
{

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Smyapp\Connector\Helper\Data $customHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        //\Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Framework\Event\Manager $eventManager
    ) {
        $this->customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->customHelper = $customHelper;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $context->getRequest();
        $this->_eventManager = $eventManager;
        parent::__construct($context);
    }

    public function execute()
    {

        $result = $this->resultJsonFactory->create();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('Smyapp_Pushnotification');

        $sql = "Select * FROM Smyapp_Pushnotification WHERE user_id='" . $this->request->getParam('customer_id') . "' AND device_type='" . $this->request->getParam('type') . "' ";
        $resultdata = $connection->fetchAll($sql);

        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
         $baseurl1 = $storeManager->getStore()->getBaseUrl();
        $url1 = $this->request->getParam('weburl');
        define('API_ACCESS_KEY', 'AAAAwnkuMMI:APA91bE3v7gVxPVyo2svT44myaKvTrLZCVlHr2UyxQDoSgPqNi7n7f-usgJX8-OTciLeWNwnrr0bp96_wvPEWmjngUtrCDoSy-u_aypNUnw6L16dOo61Rph7n_z6lGLlBc8M3xOeSrIk');
 if($baseurl1 == $url1 ) {
    if($resultdata){
        foreach ($resultdata as $rowdata) {
            
            if ($rowdata['device_type'] == 0) {
               
                $fcm_url = 'https://fcm.googleapis.com/fcm/send';
                $headers = array
                    (
                    'Authorization: key=' . API_ACCESS_KEY,
                    'Content-Type: application/json',
                );

                $fields = array(
                    'registration_ids' => array(
                        $rowdata['registration_id'],
                    ),
                    'priority' => 10,
                    'notification' => array('title' => 'Peoples Market', 'body' => $this->request->getParam('message'), 'sound' => 'Default', 'image' => '', 'type' => 'notification'),
                );

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $fcm_url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                $result = curl_exec($ch);
                if ($result === false) {
                    die('FCM Send Error: ' . curl_error($ch));
                }
                curl_close($ch);
                echo json_encode(['status' => "success", 'message' => 'Notification send successfully']);

            } else {

                $path = '/home/lms/www' . '/apns-dev.pem';
                $token = $rowdata['registration_id'];

                $apnsHost = 'gateway.sandbox.push.apple.com';
                $apnsCert = $path;
                $apnsPort = 2195;

                $passphrase = "Admin123#";

                $streamContext = stream_context_create();
                stream_context_set_option($streamContext, 'ssl', 'local_cert', $apnsCert);
                stream_context_set_option($streamContext, 'ssl', 'passphrase', $passphrase);

                $apns = stream_socket_client('ssl://' . $apnsHost . ':' . $apnsPort, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $streamContext);

                $payload['aps'] = array('alert' => $this->request->getParam('message'), 'badge' => 1, 'sound' => 'default');
                $output = json_encode($payload);
                $token = pack('H*', str_replace(' ', '', $token));
                $apnsMessage = chr(0) . chr(0) . chr(32) . $token . chr(0) . chr(strlen($output)) . $output;
                fwrite($apns, $apnsMessage);

                echo json_encode(['status' => "success", 'message' => 'Notification send successfully']);
                
            }
            

        } 


    } else {
         echo json_encode(['status' => "success", 'message' => 'User Id Invalid']);
        exit;
    }

    } else {
        echo json_encode(['status' => "success", 'message' => 'Invalid Request']);
        exit;
    }

    }

}
