<?php
declare(strict_types=1);

namespace PeachCode\GoogleOneTap\Block;

use Magento\Customer\Model\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context as AuthContext;
use PeachCode\GoogleOneTap\Model\Config\Data;

class OneTap extends Template
{

    /**
     * @param Template\Context $context
     * @param Data $config
     * @param CustomerSession $customerSession
     * @param AuthContext $authContext
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private readonly Data $config,
        private readonly CustomerSession $customerSession,
        private readonly AuthContext $authContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Check is Customer Logged In
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        return $this->authContext->getValue(Context::CONTEXT_AUTH);
    }

    /**
     * Get Google client ID from Config
     *
     * @return string
     * @throws LocalizedException
     */
    public function getClientId(): string
    {
        $websiteId = (int)$this->_storeManager->getWebsite()->getId();

        return $this->config->getClientId($websiteId);
    }

    /**
     * Prepare config
     *
     * @return mixed
     */
    public function getClickDisable(): mixed
    {
        return $this->config->getClickDisable();
    }

    /**
     * Prepare config
     *
     * @return mixed
     */
    public function getAutoSign(): mixed
    {
        return $this->config->getAutoSign();
    }

    /**
     * Prepare config
     *
     * @return mixed
     */
    public function getPosition(): mixed
    {
        return $this->config->getPosition();
    }

    /**
     * Prepare config
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isEnable(): bool
    {
        $websiteId = (int)$this->_storeManager->getWebsite()->getId();

        return $this->config->isEnable($websiteId);
    }
}
