<?php

declare(strict_types=1);

namespace Antom\Adminhtml\Block\Adminhtml\Config;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Website;

class ValidateApiKeyButton extends Field
{
    private const TEMPLATE_PATH = 'Antom_Adminhtml::config/general/validate_api_key_button.phtml';
    private const CHECK_BUTTON_ID = 'antom_validate_button';

    /**
     * @param AbstractElement $element
     * @return string
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        /** @var Template $block */
        $block = $this->_layout->createBlock(Template::class);

        /** @var Button $button */
        $button = $this->getLayout()->createBlock(Button::class);

        $button->setData([
                'id' => self::CHECK_BUTTON_ID,
                'label' => __('Validate API Key'),
                'class' => 'primary',
            ]);

        // @phpstan-ignore-next-line
        $block->setTemplate(self::TEMPLATE_PATH)
            ->setData('send_button', $button->toHtml())
            ->setData('ajax_url', $this->getAjaxUrl())
            ->setData('store_id', $this->getStoreId())
            ->setData('button_id', self::CHECK_BUTTON_ID);

        return $block->toHtml();
    }

    /**
     * @param AbstractElement $element
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _renderScopeLabel(AbstractElement $element): string
    {
        return '';
    }

    /**
     * @return string
     */
    protected function getAjaxUrl(): string
    {
        return $this->getUrl('antom/api/validate', ['_secure' => true]);
    }

    /**
     * Get store identifier
     *
     * @return  int
     * @throws LocalizedException
     */
    public function getStoreId(): int
    {
        $storeId = null;
        $websiteId = $this->getRequest()->getParam('website');

        if ($websiteId) {
            /** @var Website $storeManagerWebsite */
            $storeManagerWebsite = $this->_storeManager->getWebsite((int)$websiteId);

            $storeIds = $storeManagerWebsite->getStoreIds();
            // due to magento framework historical reason, we only need to get one of the storeIds
            $storeId = array_pop($storeIds);
        }

        return (int)($storeId ?: $this->getRequest()->getParam('store', 0));
    }
}
