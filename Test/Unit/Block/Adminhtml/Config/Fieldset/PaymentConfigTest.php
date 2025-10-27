<?php

declare(strict_types=1);

namespace Antom\Adminhtml\Test\Unit\Block\Adminhtml\Config\Fieldset;

use Antom\Adminhtml\Block\Adminhtml\Config\Fieldset\PaymentConfig;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Helper\Js;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for PaymentConfig block
 */
class PaymentConfigTest extends TestCase
{
    /**
     * @var PaymentConfig
     */
    private $paymentConfig;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var AuthSession|MockObject
     */
    private $authSessionMock;

    /**
     * @var Js|MockObject
     */
    private $jsHelperMock;

    /**
     * @var SecureHtmlRenderer|MockObject
     */
    private $secureRendererMock;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilderMock;

    /**
     * @var Escaper|MockObject
     */
    private $escaperMock;

    /**
     * @var AbstractElement|MockObject
     */
    private $elementMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->authSessionMock = $this->getMockBuilder(AuthSession::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsHelperMock = $this->getMockBuilder(Js::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->secureRendererMock = $this->getMockBuilder(SecureHtmlRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->escaperMock = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->elementMock = $this->getMockBuilder(AbstractElement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHtmlId', 'getData'])
            ->getMock();

        $this->contextMock->method('getUrlBuilder')->willReturn($this->urlBuilderMock);
        $this->contextMock->method('getEscaper')->willReturn($this->escaperMock);

        $this->paymentConfig = new PaymentConfig(
            $this->contextMock,
            $this->authSessionMock,
            $this->jsHelperMock,
            [],
            $this->secureRendererMock
        );
    }

    /**
     * Test _getFrontendClass method
     *
     * @return void
     */
    public function testGetFrontendClass(): void
    {
        // Create a partial mock to test the protected method
        $paymentConfig = $this->getMockBuilder(PaymentConfig::class)
            ->setConstructorArgs([$this->contextMock,
                $this->authSessionMock,
                $this->jsHelperMock,
                [],
                $this->secureRendererMock])
            ->onlyMethods(['_getFrontendClass'])
            ->getMock();

        // Use reflection to access protected method
        $method = new \ReflectionMethod(PaymentConfig::class, '_getFrontendClass');
        $method->setAccessible(true);

        $result = $method->invoke($paymentConfig, $this->elementMock);

        $this->assertStringContainsString('with-button', $result);
    }

    /**
     * Test _getHeaderTitleHtml method
     *
     * @return void
     */
    public function testGetHeaderTitleHtml(): void
    {
        $htmlId = 'test-html-id';
        $legend = 'Test Legend';
        $comment = 'Test Comment';
        $configureUrl = 'http://example.com/admin/system_config/edit/section/antom_general';

        $this->elementMock->method('getHtmlId')->willReturn($htmlId);
        $this->elementMock->method('getData')
            ->willReturnMap([
                ['legend', null, $legend],
                ['comment', null, $comment]
            ]);

        $this->urlBuilderMock->method('getUrl')
            ->with('adminhtml/system_config/edit/section/antom_general')
            ->willReturn($configureUrl);

        $this->escaperMock->method('escapeUrl')
            ->with($configureUrl)
            ->willReturn($configureUrl);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->paymentConfig);
        $method = $reflection->getMethod('_getHeaderTitleHtml');
        $method->setAccessible(true);

        $result = $method->invoke($this->paymentConfig, $this->elementMock);

        $this->assertStringContainsString('config-heading', $result);
        $this->assertStringContainsString('button-container', $result);
        $this->assertStringContainsString('action-configure', $result);
        $this->assertStringContainsString($htmlId . '-head', $result);
        $this->assertStringContainsString($configureUrl, $result);
        $this->assertStringContainsString('Configure', $result);
        $this->assertStringContainsString($legend, $result);
        $this->assertStringContainsString($comment, $result);
    }

    /**
     * Test _getHeaderTitleHtml method without comment
     *
     * @return void
     */
    public function testGetHeaderTitleHtmlWithoutComment(): void
    {
        $htmlId = 'test-html-id';
        $configureUrl = 'http://example.com/admin/system_config/edit/section/antom_general';

        $this->urlBuilderMock->method('getUrl')
            ->with('adminhtml/system_config/edit/section/antom_general')
            ->willReturn($configureUrl);

        $this->escaperMock->method('escapeUrl')
            ->with($configureUrl)
            ->willReturn($configureUrl);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->paymentConfig);
        $method = $reflection->getMethod('_getHeaderTitleHtml');
        $method->setAccessible(true);

        $result = $method->invoke($this->paymentConfig, $this->elementMock);

        $this->assertStringContainsString('config-heading', $result);
        $this->assertStringNotContainsString('heading-intro', $result);
    }

    /**
     * Test _getHeaderCommentHtml method
     *
     * @return void
     */
    public function testGetHeaderCommentHtml(): void
    {
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->paymentConfig);
        $method = $reflection->getMethod('_getHeaderCommentHtml');
        $method->setAccessible(true);

        $result = $method->invoke($this->paymentConfig, $this->elementMock);

        $this->assertEquals('', $result);
    }

    /**
     * Test _isCollapseState method
     *
     * @return void
     */
    public function testIsCollapseState(): void
    {
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->paymentConfig);
        $method = $reflection->getMethod('_isCollapseState');
        $method->setAccessible(true);

        $result = $method->invoke($this->paymentConfig, $this->elementMock);

        $this->assertFalse($result);
    }
}
