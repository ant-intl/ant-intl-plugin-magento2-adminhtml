<?php

declare(strict_types=1);

namespace Antom\Adminhtml\Test\Unit\Block\Adminhtml\Config;

use Antom\Adminhtml\Block\Adminhtml\Config\ValidateApiKeyButton;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


class ValidateApiKeyButtonTest extends TestCase
{
    /**
     * @var ValidateApiKeyButton
     */
    private $validateApiKeyButton;

    /**
     * @var MockObject|Context
     */
    private $contextMock;

    /**
     * @var MockObject|StoreManagerInterface
     */
    private $storeManagerMock;

    /**
     * @var MockObject
     */
    private $layoutMock;

    /**
     * @var MockObject
     */
    private $urlBuilderMock;

    /**
     * @var MockObject
     */
    private $requestMock;


    /**
     * @var JsonHelper
     */
    private $jsonHelperMock;

    /**
     * @var DirectoryHelper
     */
    private $directoryHelperMock;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManagerMock;


    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->layoutMock = $this->createMock(\Magento\Framework\View\Layout::class);
        $this->urlBuilderMock = $this->createMock(\Magento\Framework\UrlInterface::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);

        $this->contextMock->method('getStoreManager')->willReturn($this->storeManagerMock);
        $this->contextMock->method('getUrlBuilder')->willReturn($this->urlBuilderMock);
        $this->contextMock->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->method('getLayout')->willReturn($this->layoutMock);


        $this->jsonHelperMock = $this->createMock(JsonHelper::class);
        $this->directoryHelperMock = $this->createMock(DirectoryHelper::class);
        $data = [
            'jsonHelper' => $this->jsonHelperMock,
            'directoryHelper' => $this->directoryHelperMock
        ];

        $this->objectManagerMock = $this->createMock(\Magento\Framework\App\ObjectManager::class);
        $this->objectManagerMock->method('get')->willReturnMap([
            [\Magento\Framework\Json\Helper\Data::class, $this->jsonHelperMock],
            [\Magento\Directory\Helper\Data::class, $this->directoryHelperMock],
        ]);
        \Magento\Framework\App\ObjectManager::setInstance($this->objectManagerMock);

        $this->validateApiKeyButton = new ValidateApiKeyButton($this->contextMock, $data);
        $this->validateApiKeyButton->setLayout($this->layoutMock);

    }



    public function testGetElementHtmlReturnsExpectedHtml(): void
    {
        // 调用 protected 方法
        $reflection = new \ReflectionClass(ValidateApiKeyButton::class);
        $method = $reflection->getMethod('_getElementHtml');
        $method->setAccessible(true);


        $layoutMock = $this->createMock(\Magento\Framework\View\LayoutInterface::class);

        $this->validateApiKeyButton = $this->getMockBuilder(ValidateApiKeyButton::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLayout', 'getAjaxUrl', 'getStoreId'])->getMock();
        $this->validateApiKeyButton->method('getLayout')->willReturn($layoutMock);
        $this->validateApiKeyButton->method('getAjaxUrl')
            ->willReturn('https://example.com/admin/antom/api/validate');
        $this->validateApiKeyButton->method('getStoreId')->willReturn(1);

        $elementMock = $this->createMock(AbstractElement::class);
        // Mock button block
        $buttonMock = $this->createMock(Button::class);
        $buttonMock->expects($this->once())
            ->method('setData')
            ->with($this->callback(function ($data) {
                return isset($data['id'], $data['label'], $data['class']);
            }))
            ->willReturnSelf();
        $buttonMock->method('toHtml')->willReturn('<button>Validate API Key</button>');

        // Mock template block
        $templateMock = $this->createMock(Template::class);
        $templateMock->method('setTemplate')
            ->with('Antom_Adminhtml::config/general/validate_api_key_button.phtml')
            ->willReturnSelf();


        $templateMock->expects($this->exactly(4))
            ->method('setData')
            ->with($this->callback(function ($key) {
                $keys = ['send_button', 'ajax_url', 'store_id', 'button_id'];
                return in_array($key, $keys, true);
            }))
            ->willReturnSelf();

        $templateMock->method('toHtml')->willReturn('<div>Test HTML</div>');

        $refLayout = new \ReflectionProperty($this->validateApiKeyButton, '_layout');
        $refLayout->setAccessible(true);
        $refLayout->setValue($this->validateApiKeyButton, $layoutMock);

        $layoutMock->method('createBlock')
            ->willReturnCallback(function ($class) use ($templateMock, $buttonMock) {
                if ($class === \Magento\Backend\Block\Template::class) {
                    return $templateMock;
                }
                if ($class === \Magento\Backend\Block\Widget\Button::class) {
                    return $buttonMock;
                }
                return null;
            });
        $result = $method->invoke($this->validateApiKeyButton, $elementMock);
        $this->assertEquals('<div>Test HTML</div>', $result);
    }

    public function testRenderScopeLabelReturnsEmptyString(): void
    {
        $reflection = new \ReflectionClass(ValidateApiKeyButton::class);
        $method = $reflection->getMethod('_renderScopeLabel');
        $method->setAccessible(true);
        $elementMock = $this->createMock(AbstractElement::class);
        $result = $method->invoke($this->validateApiKeyButton, $elementMock);
        $this->assertEquals('', $result);
    }

    public function testGetAjaxUrlReturnsExpectedUrl(): void
    {
        $reflection = new \ReflectionClass(ValidateApiKeyButton::class);
        $method = $reflection->getMethod('getAjaxUrl');
        $method->setAccessible(true);


        $expectedUrl = 'https://example.com/admin/antom/api/validate';
        $this->urlBuilderMock->method('getUrl')
            ->with('antom/api/validate', ['_secure' => true])
            ->willReturn($expectedUrl);

        $result = $method->invoke($this->validateApiKeyButton);
        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetStoreIdReturnsStoreFromRequestWhenNoWebsite(): void
    {
        $reflection = new \ReflectionClass($this->validateApiKeyButton);
        $method = $reflection->getMethod('getStoreId');
        $method->setAccessible(true);

        $this->requestMock->method('getParam')
            ->willReturnMap([
                ['website', null, '2'],
                ['store', null, 5]
            ]);

        $websiteMock = $this->createMock(Website::class);
        $websiteMock->method('getStoreIds')->willReturn([1, 2, 3]);

        $this->storeManagerMock->method('getWebsite')
            ->with(2)
            ->willReturn($websiteMock);

        $result = $method->invoke($this->validateApiKeyButton);
        $this->assertEquals(3, $result);
    }

    public function testGetStoreIdReturnsStoreFromWebsiteWhenWebsiteProvided(): void
    {
        $websiteMock = $this->createMock(Website::class);
        $websiteMock->method('getStoreIds')->willReturn([1, 2, 3]);

        $this->requestMock->method('getParam')
            ->willReturnMap([
                ['website', null, '2'],
                ['store', null, 0]
            ]);

        $this->storeManagerMock->method('getWebsite')
            ->with(2)
            ->willReturn($websiteMock);

        $result = $this->validateApiKeyButton->getStoreId();

        $this->assertEquals(3, $result);
    }

    public function testGetStoreIdReturnsDefaultStoreWhenNoParams(): void
    {
        $this->requestMock->method('getParam')
            ->willReturnMap([
                ['website', null, null],
                ['store', null, 0]
            ]);

        $result = $this->validateApiKeyButton->getStoreId();

        $this->assertEquals(0, $result);
    }

    public function testGetStoreIdWithWebsiteHavingNoStores(): void
    {
        $websiteMock = $this->createMock(Website::class);
        $websiteMock->method('getStoreIds')->willReturn([]);

        $this->requestMock->method('getParam')
            ->willReturnMap([
                ['website', null, '3'],
                ['store', null, 0]
            ]);

        $this->storeManagerMock->method('getWebsite')
            ->with(3)
            ->willReturn($websiteMock);

        $result = $this->validateApiKeyButton->getStoreId();

        $this->assertEquals(0, $result);
    }

    public function testGetElementHtmlThrowsLocalizedException(): void
    {
        $this->expectException(LocalizedException::class);

        $elementMock = $this->createMock(AbstractElement::class);

        // Mock layout to throw exception
        $this->layoutMock->method('createBlock')
            ->willThrowException(new LocalizedException(__('Test exception')));

        $this->validateApiKeyButton->_getElementHtml($elementMock);
    }
}
