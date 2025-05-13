<?php
declare(strict_types=1);

namespace PeachCode\GoogleOneTap\Controller\Checkout;

use Exception;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\RedirectFactory;
use PeachCode\GoogleOneTap\Model\Config\Data;
use Google\Client as Google_Client;
use Magento\Customer\Model\Session;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Exception\{InputException, LocalizedException, NoSuchEntityException};

class Response implements CsrfAwareActionInterface
{

    /**
     * @param RedirectFactory $resultRedirectFactory
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
        public readonly RedirectFactory $resultRedirectFactory,
        public readonly Data $config,
        public readonly RequestInterface $request,
        public readonly CustomerFactory $customerFactory,
        public readonly Session $customerSession,
        public readonly StoreManagerInterface $storeManager,
        public readonly CustomerInterfaceFactory $customerInterfaceFactory,
        public readonly CustomerRepositoryInterface $customerRepositoryInterface,
        public readonly JsonFactory $resultJsonFactory
    ) {}

    /**
     * @return ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute(): ResultInterface
    {
        $result = $this->resultJsonFactory->create();
        $websiteId = $this->storeManager->getStore()->getWebsiteId();

        try {
            $idToken = $this->request->getParam('id_token');
            if (!$idToken) {
                throw new InputException(__('Missing ID token.'));
            }

            $googleOauthClientId = $this->config->getClientId($websiteId);
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
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($email);

            if (!$customer->getId()) {
                $customer = $this->customerInterfaceFactory->create();
                $customer->setWebsiteId($websiteId);
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

    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
