<?php
declare(strict_types=1);

namespace PeachCode\GoogleOneTap\Controller\Checkout;

use Exception;
use PeachCode\GoogleOneTap\Model\Config\Data;
use Google\Client as Google_Client;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Exception\{InputException, LocalizedException, NoSuchEntityException, State\InputMismatchException};

class Response implements ActionInterface
{

    /**
     * @param Data $config
     * @param RequestInterface $request
     * @param CustomerFactory $customerFactory
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        private readonly Data $config,
        private readonly RequestInterface $request,
        private readonly CustomerFactory $customerFactory,
        private readonly Session $customerSession,
        private readonly StoreManagerInterface $storeManager,
        private readonly CustomerInterfaceFactory $customerInterfaceFactory,
        private readonly CustomerRepositoryInterface $customerRepositoryInterface,
        private readonly JsonFactory $resultJsonFactory
    ) {}

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $result = $this->resultJsonFactory->create();

        try {
            $idToken = $this->request->getParam('id_token');
            if (!$idToken) {
                throw new InputException(__('Missing ID token.'));
            }

            $googleOauthClientId = $this->config->getClientId();
            $client = new Google_Client(['client_id' => $googleOauthClientId]);
            $payload = $client->verifyIdToken($idToken);

            if (!$payload || ($payload['aud'] ?? null) !== $googleOauthClientId) {
                throw new LocalizedException(__('Invalid Google ID token.'));
            }

            $email = $payload["email"] ?? null;
            $nameParts = explode(' ', $payload["name"] ?? '');
            $firstName = $nameParts[0] ?? 'Guest';
            $lastName = $nameParts[1] ?? 'User';

            $customer = $this->customerFactory->create();
            $customer->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
            $customer->loadByEmail($email);

            if (!$customer->getId()) {
                $customer = $this->customerInterfaceFactory->create();
                $customer->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
                $customer->setEmail($email);
                $customer->setFirstname($firstName);
                $customer->setLastname($lastName);
                $this->customerRepositoryInterface->save($customer);

                $customer = $this->customerFactory->create();
                $customer->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
                $customer->loadByEmail($email);
            }

            $this->customerSession->setCustomerAsLoggedIn($customer);

            return $result->setData(['success' => true]);

        } catch (Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

}
