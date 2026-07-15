<?php
declare(strict_types=1);

namespace MageSuite\FreeGift\Model;

use Magento\Framework\Module\Manager;

class ConfigProvider
{
    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * ConfigProvider constructor.
     *
     * @param Manager $moduleManager
     */
    public function __construct(
        Manager $moduleManager
    ) {
        $this->moduleManager = $moduleManager;
    }

    /**
     * Check if magento inventory module is enabled
     *
     * @return bool
     */
    public function isMsiEnabled()
    {
        return $this->moduleManager->isEnabled('Magento_Inventory');
    }
}
