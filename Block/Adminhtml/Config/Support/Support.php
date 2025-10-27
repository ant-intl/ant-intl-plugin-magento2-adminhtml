<?php
declare(strict_types=1);

namespace Antom\Adminhtml\Block\Adminhtml\Config\Support;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template;
use Antom\Core\Util\VersionUtil;

class Support extends Template implements RendererInterface
{
    private const ANTOM_DOCS_UPGRADE_LINK
        = 'https://docs.antom.com/'; // TODO: FIX IT

    /**
     * @var string
     * @codingStandardsIgnoreLine
     */
    protected $_template = 'Antom_Adminhtml::config/general/support.phtml';

    /**
     * @var VersionUtil
     */
    private $versionUtil;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * Support constructor.
     *
     * @param VersionUtil $versionUtil
     * @param Template\Context $context
     * @param Escaper $escaper
     * @param array $data
     */
    public function __construct(
        VersionUtil $versionUtil,
        Template\Context $context,
        Escaper $escaper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->versionUtil = $versionUtil;
        $this->escaper = $escaper;
    }

    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element): string
    {
        // TODO: do we need this setElement method
        /**
         * @noinspection PhpUndefinedMethodInspection
         */
        $this->setElement($element);

        return $this->toHtml();
    }

    /**
     * @return Escaper
     */
    public function getEscaper(): Escaper
    {
        return $this->escaper;
    }

    /**
     * @return string
     */
    public function getModuleVersion(): string
    {
        return $this->versionUtil->getPluginVersion();
    }

    /**
     * @return array
     */
    public function isNewVersionAvailable(): array
    {
        return $this->versionUtil->getNewVersionsDataIfExist();
    }

    /**
     * @return string
     */
    public function getUpdateDocsLink(): string
    {
        return self::ANTOM_DOCS_UPGRADE_LINK;
    }
}
