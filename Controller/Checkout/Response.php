<?php
declare(strict_types=1);

namespace PeachCode\GoogleOneTap\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Google\Client as Google_Client;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
class Response extends Action
{
    protected $request;
    protected $googleClient;
    protected $customerFactory;
    protected $accountManagement;
    protected $customerSession;
    protected $messageManager;
    protected $_storeManager;
    protected $customerInterfaceFactory;
    protected $resultRedirectFactory;
    protected $_redirect;
    protected $customerRepositoryInterface;
    protected $scopeConfig;
    protected $encryptor;

    public function __construct(
        Context $context,
        RequestInterface $request,
        Google_Client $googleClient,
        CustomerFactory $customerFactory,
        AccountManagement $accountManagement,
        Session $customerSession,
        ManagerInterface $messageManager,
        StoreManagerInterface $storeManager,
        CustomerInterfaceFactory $CustomerInterfaceFactory,
        RedirectFactory $resultRedirectFactory,
        RedirectInterface $redirect,
        CustomerRepositoryInterface $CustomerRepositoryInterface,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
    ) {
        $this->request = $request;
        $this->googleClient = $googleClient;
        $this->customerFactory = $customerFactory;
        $this->accountManagement = $accountManagement;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->customerInterfaceFactory = $CustomerInterfaceFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->_redirect = $redirect;
        $this->customerRepositoryInterface = $CustomerRepositoryInterface;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        parent::__construct($context);
    }

    public function execute()
    {
        $id_token = $this->request->getParam('id_token');
        $google_oauth_client_id = $this->encryptor->decrypt($this->scopeConfig->getValue('GoogleOneTap/general/client_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        $client = new Google_Client([
            'client_id' => $google_oauth_client_id
        ]);
        $payload = $client->verifyIdToken($id_token);
        if ($payload && $payload['aud'] == $google_oauth_client_id) {
            $user_google_id = $payload['sub'];
            $name = $payload["name"];
            $email = $payload["email"];
            $test = explode(' ', $name);
            $counttest = count($test);
            $customer = $this->customerFactory->create();
            $customer->setWebsiteId($this->_storeManager->getStore()->getWebsiteId());
            $customer->loadByEmail($email);
            if (!$customer->getId()) {
                $customer = $this->customerInterfaceFactory->create();
                $customer->setWebsiteId($this->_storeManager->getStore()->getWebsiteId());
                $customer->setEmail($email);
                if ($counttest == 2) {
                    $customer->setFirstname($test[0]);
                    $customer->setLastname($test[1]);
                } else {
                    $customer->setFirstname($name);
                    $customer->setLastname($name);
                }
                $this->customerRepositoryInterface->save($customer);
                $customer = $this->customerFactory->create();
                $customer->setWebsiteId($this->_storeManager->getStore()->getWebsiteId());
                $customer->loadByEmail($email);
                $this->customerSession->setCustomerAsLoggedIn($customer);
            } else {
                $this->customerSession->setCustomerAsLoggedIn($customer);
            }
        }
    }
}
