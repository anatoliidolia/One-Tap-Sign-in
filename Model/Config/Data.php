<?php
declare(strict_types=1);

namespace PeachCode\GoogleOneTap\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use PeachCode\GoogleOneTap\Model\API\DataInterface as API;

class Data implements API{

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {}

    /**
     * Get Google client ID from Config
     *
     * @return string
     */
    public function getClientId(): string
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(API::XML_CLIENT_ID, ScopeInterface::SCOPE_STORE));
    }

    /**
     * Get config
     *
     * @return mixed
     */
    public function getClickDisable(): mixed
    {
        return $this->scopeConfig->getValue(API::XML_BCG_CLICK, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get config
     *
     * @return mixed
     */
    public function getAutoSign(): mixed
    {
        return $this->scopeConfig->getValue(API::XML_AUTO_SIGN_IN , ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get config
     *
     * @return mixed
     */
    public function getPosition(): mixed
    {
        return $this->scopeConfig->getValue(API::XML_POSITION, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get config
     *
     * @return bool
     */
    public function isEnable(): bool
    {
        return $this->scopeConfig->isSetFlag(API::XML_STATUS, ScopeInterface::SCOPE_STORE);
    }
}
