<?php
namespace Smyapp\Sociallogin\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    private $sociallogin_support = array('facebook', 'google');

    private $f_url               = 'https://graph.facebook.com/me?fields=first_name,last_name,gender,email&format=json&access_token=';

    private $g_url               = 'https://www.googleapis.com/oauth2/v3/tokeninfo?alt=json&id_token=';

    private $sociallogin_type;

    /**
	 * @var \Magento\Store\Model\StoreResolver
	 */
	private $storeResolver;

	/**
	 * @param \Magento\Store\Model\StoreResolver $storeResolver
	 */
	public function __construct(
	    \Magento\Store\Model\StoreManagerInterface $storeManager,
	    \Magento\Customer\Model\Session $customerSession
	) {
		$this->_customerSession = $customerSession;
	    $this->_storeManager    = $storeManager;
	}

	/**
	 * Returns the current store id, if it can be detected or default store id
	 * 
	 * @return int|string
	 */
	public function getCurrentWebsiteId()
	{
	    return $this->_storeManager->getStore()->getWebsiteId();
	}

    public function socialloginRequest($token,$sociallogintype){
		if($token && $sociallogintype):			
			$this->sociallogin_type = $sociallogintype;
			$checkDiffLogin = $this->sociallogin_support($sociallogintype);
			if ($checkDiffLogin['status'] == 'error') {
				return $checkDiffLogin;
			}
			return $this->getSocialdetails($token,$sociallogintype);
		else:
			return true;
		endif;
	}

	private function sociallogin_support($sociallogintype)
	{
		if(!in_array($sociallogintype, $this->sociallogin_support)):
			return array('status'=>'error','message'=> __('Social Login is not supported by Smyapp.'));
		else:
			return true;
		endif;
		return true;
	}

	private function getSocialdetails($token,$sociallogintype)
	{
		switch($sociallogintype) 
		{
			case 'facebook':
				return $this->getfacebookdetails($token);
			break;
			case 'google':
				return $this->getgoogledetails($token);
			break;
			default:
				return array('status'=>'error','message'=> __('Social Login is not supported by Smyapp.'));
		}
	}

	private function getFacebookdetails($token)
	{
		$user_details = $this->f_url.$token;
	    $ch = curl_init(); 
	    curl_setopt($ch, CURLOPT_URL, $user_details);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	    $output = curl_exec($ch);
	    curl_close($ch);
		$response = json_decode($output);
		if(isset($response->email)):
			return $this->checkuser($response);
		else:
			return array('status'=>'error','message'=> __('Token is invalid.'));
		endif;
	}

	private function getgoogledetails($token)
	{
		$user_details = $this->g_url.$token;
	    $ch = curl_init(); 
	    curl_setopt($ch, CURLOPT_URL, $user_details);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	    $output = curl_exec($ch);
	    curl_close($ch);
		$response = json_decode($output);
		if(isset($response->email)):
			return $this->checkuser($response);
		else:
			return array('status'=>'error','message'=> __('Token is invalid.'));
		endif;

	}

	private function checkuser($response)
	{
		try{
			$_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$customer = $_objectManager->create('Magento\Customer\Model\Customer');
			$customer->setWebsiteId($this->getCurrentWebsiteId());
			$customer->loadByEmail($response->email);	 		
	 		if($customer->getId()):
	 			$this->_customerSession->setCustomerAsLoggedIn($customer);
	 			$customerinfo  = array(
                        "id"    => $customer->getId(),
                        "name"  => $customer->getName(),
                        "email" => $customer->getEmail(),
                    );
	 			return array('status'=>'success','message'=> $customerinfo);
	 		else:
	 			return $this->registerUser($response);
	 		endif;
	 	} catch(exception $e){
	 		return array('status'=>'error','message'=> __($e->getMessage()));
	 	}
	}

	private function registerUser($response)
	{
		$session = $this->_customerSession;
		$_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$customer = $_objectManager->create('Magento\Customer\Model\Customer');
		$customer = $customer->setId(null);
		$customer->setData('email',$response->email);
		if ($this->sociallogin_type == 'facebook') {
			$customer->setData('firstname',$response->first_name);
			$customer->setData('lastname',$response->last_name);
			$customer->setData('gender',$response->gender);
		} else {
			$name = explode(' ', $response->name);
			$customer->setData('firstname', $name[0]);
			$customer->setData('lastname', $name[1]);
		}
		// $customer->setData('sociallogin_type',$this->sociallogin_type);
		$customer->setData('password',$this->radPassoword());

		try{
			$customer->setConfirmation(null);
			$customer->save(); 
			if ($customer->isConfirmationRequired ()):
				$customer->sendNewAccountEmail ( 'confirmation', $session->getBeforeAuthUrl (), $this->_storeManager->getStore()->getId());
				return array('status'=>'error','message'=> __('Account confirmation required.'));
			else:
				$session->setCustomerAsLoggedIn($customer);
				$customer->sendNewAccountEmail ('registered','', $this->_storeManager->getStore()->getId());
			endif;
			return array('status'=> 'success','message'=> $this->checkStatus());
			
		} catch(Exception $ex){
			return array('status'=>'error','message'=> __('Error in creating user account.'));
		}
	}

	private function radPassoword()
	{
		return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', mt_rand(1,6))),1,6);
	}

	private function checkStatus()
	{	
		if ($this->_customerSession->isLoggedIn()) {
		    return true;
 		} else {
		    return false;
		}
	}
}
