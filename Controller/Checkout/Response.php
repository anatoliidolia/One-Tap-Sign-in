<?php
declare(strict_types=1);

namespace PeachCode\GoogleOneTap\Controller\Checkout;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Google\Client as Google_Client;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use PeachCode\GoogleOneTap\Model\Config\Data;

class Response implements ActionInterface
{

    /**
     * @param Data $config
     * @param RequestInterface $request
     * @param Google_Client $googleClient
     * @param CustomerFactory $customerFactory
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        private readonly Data $config,
        private readonly RequestInterface $request,
        private readonly CustomerFactory $customerFactory,
        private readonly Session $customerSession,
        private readonly StoreManagerInterface $storeManager,
        private readonly CustomerInterfaceFactory $customerInterfaceFactory,
        private readonly CustomerRepositoryInterface $customerRepositoryInterface,
    ) {}

    /**
     * @throws NoSuchEntityException
     * @throws InputMismatchException
     * @throws LocalizedException
     * @throws InputException
     */
    public function execute(): void
    {
        $id_token = $this->request->getParam('id_token');
        $googleOauthClientId = $this->config->getClientId();
        $client = new Google_Client([
            'client_id' => $googleOauthClientId
        ]);
        $payload = $client->verifyIdToken($id_token);
        if ($payload && $payload['aud'] == $googleOauthClientId) {
            $name = $payload["name"];
            $email = $payload["email"];
            $toLogin = explode(' ', $name);
            $countToLogin = count($toLogin);
            $customer = $this->customerFactory->create();
            $customer->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
            $customer->loadByEmail($email);
            if (!$customer->getId()) {
                $customer = $this->customerInterfaceFactory->create();
                $customer->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
                $customer->setEmail($email);

                $customer->setFirstname($name);
                $customer->setLastname($name);
                if ($countToLogin == 2) {
                    $customer->setFirstname($toLogin[0]);
                    $customer->setLastname($toLogin[1]);
                }
                $this->customerRepositoryInterface->save($customer);
                $customer = $this->customerFactory->create();
                $customer->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
                $customer->loadByEmail($email);
            }
            $this->customerSession->setCustomerAsLoggedIn($customer);
        }
    }
}
