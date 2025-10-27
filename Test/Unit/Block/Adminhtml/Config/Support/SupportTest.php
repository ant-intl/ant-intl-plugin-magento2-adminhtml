<?php

declare(strict_types=1);

namespace Antom\Adminhtml\Test\Unit\Block\Adminhtml\Config\Support;

use Antom\Adminhtml\Block\Adminhtml\Config\Support\Support;
use Antom\Core\Util\VersionUtil;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Support block
 */
class SupportTest extends TestCase
{
    /**
     * @var Support
     */
    private $support;

    /**
     * @var VersionUtil|MockObject
     */
    private $versionUtilMock;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

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

        $this->versionUtilMock = $this->getMockBuilder(VersionUtil::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->escaperMock = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->elementMock = $this->getMockBuilder(AbstractElement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->support = new Support(
            $this->versionUtilMock,
            $this->contextMock,
            $this->escaperMock
        );
    }

    /**
     * Test render method
     *
     * @return void
     */
    public function testRender(): void
    {
        $expectedHtml = 'mocked html';

        $this->support = $this->getMockBuilder(Support::class)
            ->setConstructorArgs([$this->versionUtilMock, $this->contextMock, $this->escaperMock])
            ->onlyMethods(['toHtml']) // <-- only mock this method
            ->getMock();

        $this->support->method('toHtml')->willReturn('mocked html');

        $result = $this->support->render($this->elementMock);
        $this->assertEquals($expectedHtml, $result);
    }

    /**
     * Test getEscaper method
     *
     * @return void
     */
    public function testGetEscaper(): void
    {
        $result = $this->support->getEscaper();

        $this->assertSame($this->escaperMock, $result);
    }

    /**
     * Test getModuleVersion method
     *
     * @return void
     */
    public function testGetModuleVersion(): void
    {
        $expectedVersion = '1.2.3';

        $this->versionUtilMock->expects($this->once())
            ->method('getPluginVersion')
            ->willReturn($expectedVersion);

        $result = $this->support->getModuleVersion();

        $this->assertEquals($expectedVersion, $result);
    }

    /**
     * Test getModuleVersion with empty version
     *
     * @return void
     */
    public function testGetModuleVersionWithEmptyVersion(): void
    {
        $this->versionUtilMock->expects($this->once())
            ->method('getPluginVersion')
            ->willReturn('');

        $result = $this->support->getModuleVersion();

        $this->assertEquals('', $result);
    }

    /**
     * Test isNewVersionAvailable method with new version available
     *
     * @return void
     */
    public function testIsNewVersionAvailableWithNewVersion(): void
    {
        $expectedData = [
            'current' => '1.2.3',
            'latest' => '1.3.0',
            'update_available' => true
        ];

        $this->versionUtilMock->expects($this->once())
            ->method('getNewVersionsDataIfExist')
            ->willReturn($expectedData);

        $result = $this->support->isNewVersionAvailable();

        $this->assertEquals($expectedData, $result);
    }

    /**
     * Test isNewVersionAvailable method with no new version
     *
     * @return void
     */
    public function testIsNewVersionAvailableWithNoNewVersion(): void
    {
        $expectedData = [];

        $this->versionUtilMock->expects($this->once())
            ->method('getNewVersionsDataIfExist')
            ->willReturn($expectedData);

        $result = $this->support->isNewVersionAvailable();

        $this->assertEquals($expectedData, $result);
    }

    /**
     * Test getUpdateDocsLink method
     *
     * @return void
     */
    public function testGetUpdateDocsLink(): void
    {
        $expectedLink = 'https://docs.antom.com/';

        $result = $this->support->getUpdateDocsLink();

        $this->assertEquals($expectedLink, $result);
    }

    /**
     * Test template constant
     *
     * @return void
     */
    public function testTemplateConstant(): void
    {
        $expectedTemplate = 'Antom_Adminhtml::config/general/support.phtml';

        $this->assertEquals($expectedTemplate, $this->support->getTemplate());
    }
}
