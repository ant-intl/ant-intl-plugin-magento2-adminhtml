<?php

declare(strict_types=1);

namespace Antom\Adminhtml\Test\Unit\Controller\Adminhtml\Api;

use Antom\Adminhtml\Controller\Adminhtml\Api\Validate;
use Antom\Core\Config\AntomConfig;
use Antom\Core\Helper\RequestHelper;
use Antom\Core\Logger\AntomLogger;
use Antom\Core\Util\JsonHandler;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Validate controller
 */
class ValidateTest extends TestCase
{
    /**
     * @var Validate
     */
    private $validateController;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var AntomLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var JsonHandler|MockObject
     */
    private $jsonHandlerMock;

    /**
     * @var AntomConfig|MockObject
     */
    private $configMock;

    /**
     * @var RequestHelper|MockObject
     */
    private $requestHelperMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var Response|MockObject
     */
    private $responseMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(AntomLogger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonHandlerMock = $this->getMockBuilder(JsonHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this->getMockBuilder(AntomConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestHelperMock = $this->getMockBuilder(RequestHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMock();
        $this->responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->addMethods(['representJson'])
            ->getMock();

        $this->contextMock->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->method('getResponse')->willReturn($this->responseMock);

        $this->validateController = new Validate(
            $this->contextMock,
            $this->loggerMock,
            $this->jsonHandlerMock,
            $this->configMock,
            $this->requestHelperMock
        );
    }

    /**
     * Test successful validation
     */
    public function testExecuteSuccess(): void
    {
        $params = [
            'clientId' => 'LIVE_CLIENT_ID',
            'environment' => '1',
            'merchantPrivateKey' => 'test_private_key',
            'antomPublicKey' => 'test_public_key',
            'gatewayUrl' => 'https://test-gateway.com',
            'storeId' => '1'
        ];

        $this->requestMock->method('getParams')->willReturn($params);

        // Mock AlipayPayConsultResponse
        $alipayResponseMock = $this->getMockBuilder(\Antom\Adminhtml\Controller\Adminhtml\Api\AlipayPayConsultResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResultStatus', 'getResultMessage'])
            ->getMock();
        $alipayResponseMock->method('getResultStatus')->willReturn('S');
        $alipayResponseMock->method('getResultMessage')->willReturn('Success');

        // Mock JsonHandler
        $expectedResult = ['isValid' => true, 'message' => __('Configuration is valid.')];
        $this->jsonHandlerMock->method('convertToJSON')
            ->with($expectedResult)
            ->willReturn('{"isValid":true,"message":"Configuration is valid."}');

        // Mock response
        $this->responseMock->method('representJson')
            ->with('{"isValid":true,"message":"Configuration is valid."}')
            ->willReturnSelf();

        // Use reflection to call the consult method
        $consultMethod = new \ReflectionMethod($this->validateController, 'consult');
        $consultMethod->setAccessible(true);

        // Create a partial mock to override the consult method
        $controller = $this->getMockBuilder(Validate::class)
            ->setConstructorArgs([
                $this->contextMock,
                $this->loggerMock,
                $this->jsonHandlerMock,
                $this->configMock,
                $this->requestHelperMock
            ])
            ->onlyMethods(['consult'])
            ->getMock();

        $controller->method('consult')->willReturn($alipayResponseMock);

        $result = $controller->execute();
        $this->assertSame($this->responseMock, $result);
    }

    /**
     * Test validation with missing required parameters
     */
    public function testExecuteMissingParameters(): void
    {
        $params = [
            'clientId' => 'TEST_CLIENT',
            // Missing environment, merchantPrivateKey, antomPublicKey, gatewayUrl
        ];

        $this->requestMock->method('getParams')->willReturn($params);

        $expectedResult = [
            'isValid' => false,
            'message' => __('Validation failed. Please check your configuration.')
        ];

        $this->jsonHandlerMock->method('convertToJSON')
            ->with($expectedResult)
            ->willReturn('{"isValid":false,"message":"Validation failed. Please check your configuration."}');

        $this->responseMock->method('representJson')
            ->with('{"isValid":false,"message":"Validation failed. Please check your configuration."}')
            ->willReturnSelf();

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Fields are not properly filled in');

        $result = $this->validateController->execute();
        $this->assertSame($this->responseMock, $result);
    }

    /**
     * Test sandbox mode validation failure
     */
    public function testExecuteSandboxModeValidationFailure(): void
    {
        $params = [
            'clientId' => 'LIVE_CLIENT_ID', // Not starting with SANDBOX
            'environment' => '0', // Sandbox mode
            'merchantPrivateKey' => 'test_private_key',
            'antomPublicKey' => 'test_public_key',
            'gatewayUrl' => 'https://test-gateway.com',
            'storeId' => '1'
        ];

        $this->requestMock->method('getParams')->willReturn($params);

        $expectedResult = [
            'isValid' => false,
            'message' => __('Client ID is not SANDBOX mode')
        ];

        $this->jsonHandlerMock->method('convertToJSON')
            ->with($expectedResult)
            ->willReturn('{"isValid":false,"message":"Client ID is not SANDBOX mode"}');

        $this->responseMock->method('representJson')
            ->with('{"isValid":false,"message":"Client ID is not SANDBOX mode"}')
            ->willReturnSelf();

        $result = $this->validateController->execute();
        $this->assertSame($this->responseMock, $result);
    }

    /**
     * Test live mode validation failure
     */
    public function testExecuteLiveModeValidationFailure(): void
    {
        $params = [
            'clientId' => 'SANDBOX_CLIENT_ID', // Starting with SANDBOX
            'environment' => '1', // Live mode
            'merchantPrivateKey' => 'test_private_key',
            'antomPublicKey' => 'test_public_key',
            'gatewayUrl' => 'https://test-gateway.com',
            'storeId' => '1'
        ];

        $this->requestMock->method('getParams')->willReturn($params);

        $expectedResult = [
            'isValid' => false,
            'message' => __('Client ID is not LIVE mode')
        ];

        $this->jsonHandlerMock->method('convertToJSON')
            ->with($expectedResult)
            ->willReturn('{"isValid":false,"message":"Client ID is not LIVE mode"}');

        $this->responseMock->method('representJson')
            ->with('{"isValid":false,"message":"Client ID is not LIVE mode"}')
            ->willReturnSelf();

        $result = $this->validateController->execute();
        $this->assertSame($this->responseMock, $result);
    }

    /**
     * Test validation with private key containing asterisks
     */
    public function testExecuteWithMaskedPrivateKey(): void
    {
        $params = [
            'clientId' => 'LIVE_CLIENT_ID',
            'environment' => '1',
            'merchantPrivateKey' => '****masked_key****',
            'antomPublicKey' => 'test_public_key',
            'gatewayUrl' => 'https://test-gateway.com',
            'storeId' => '1'
        ];

        $this->requestMock->method('getParams')->willReturn($params);

        $this->configMock->method('getMerchantPrivateKey')
            ->with(1, '1')
            ->willReturn('actual_private_key');

        // Mock AlipayPayConsultResponse
        $alipayResponseMock = $this->getMockBuilder(\Antom\Adminhtml\Controller\Adminhtml\Api\AlipayPayConsultResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResultStatus', 'getResultMessage'])
            ->getMock();
        $alipayResponseMock->method('getResultStatus')->willReturn('S');
        $alipayResponseMock->method('getResultMessage')->willReturn('Success');

        $expectedResult = ['isValid' => true, 'message' => __('Configuration is valid.')];
        $this->jsonHandlerMock->method('convertToJSON')
            ->with($expectedResult)
            ->willReturn('{"isValid":true,"message":"Configuration is valid."}');

        $this->responseMock->method('representJson')
            ->with('{"isValid":true,"message":"Configuration is valid."}')
            ->willReturnSelf();

        // Create a partial mock to override the consult method
        $controller = $this->getMockBuilder(Validate::class)
            ->setConstructorArgs([
                $this->contextMock,
                $this->loggerMock,
                $this->jsonHandlerMock,
                $this->configMock,
                $this->requestHelperMock
            ])
            ->onlyMethods(['consult'])
            ->getMock();

        $controller->method('consult')->willReturn($alipayResponseMock);

        $result = $controller->execute();
        $this->assertSame($this->responseMock, $result);
    }

    /**
     * Test validation with empty private key
     */
    public function testExecuteWithEmptyPrivateKey(): void
    {
        $params = [
            'clientId' => 'LIVE_CLIENT_ID',
            'environment' => '1',
            'merchantPrivateKey' => '****masked_key****',
            'antomPublicKey' => 'test_public_key',
            'gatewayUrl' => 'https://test-gateway.com',
            'storeId' => '1'
        ];

        $this->requestMock->method('getParams')->willReturn($params);

        $this->configMock->method('getMerchantPrivateKey')
            ->with(1, '1')
            ->willReturn('');

        $expectedResult = [
            'isValid' => false,
            'message' => __('Merchant private key is not valid')
        ];

        $this->jsonHandlerMock->method('convertToJSON')
            ->with($expectedResult)
            ->willReturn('{"isValid":false,"message":"Merchant private key is not valid"}');

        $this->responseMock->method('representJson')
            ->with('{"isValid":false,"message":"Merchant private key is not valid"}')
            ->willReturnSelf();

        $result = $this->validateController->execute();
        $this->assertSame($this->responseMock, $result);
    }

    /**
     * Test API validation failure
     */
    public function testExecuteApiValidationFailure(): void
    {
        $params = [
            'clientId' => 'LIVE_CLIENT_ID',
            'environment' => '1',
            'merchantPrivateKey' => 'test_private_key',
            'antomPublicKey' => 'test_public_key',
            'gatewayUrl' => 'https://test-gateway.com',
            'storeId' => '1'
        ];

        $this->requestMock->method('getParams')->willReturn($params);

        // Mock AlipayPayConsultResponse for failure
        $alipayResponseMock = $this->getMockBuilder(\Antom\Adminhtml\Controller\Adminhtml\Api\AlipayPayConsultResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResultStatus', 'getResultMessage'])
            ->getMock();
        $alipayResponseMock->method('getResultStatus')->willReturn('F');
        $alipayResponseMock->method('getResultMessage')->willReturn('API Error');

        $expectedResult = [
            'isValid' => false,
            'message' => __('Validation failed. Please check your configuration.')
        ];

        $this->jsonHandlerMock->method('convertToJSON')
            ->with($expectedResult)
            ->willReturn('{"isValid":false,"message":"Validation failed. Please check your configuration."}');

        $this->responseMock->method('representJson')
            ->with('{"isValid":false,"message":"Validation failed. Please check your configuration."}')
            ->willReturnSelf();

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('API Error');

        // Create a partial mock to override the consult method
        $controller = $this->getMockBuilder(Validate::class)
            ->setConstructorArgs([
                $this->contextMock,
                $this->loggerMock,
                $this->jsonHandlerMock,
                $this->configMock,
                $this->requestHelperMock
            ])
            ->onlyMethods(['consult'])
            ->getMock();

        $controller->method('consult')->willReturn($alipayResponseMock);

        $result = $controller->execute();
        $this->assertSame($this->responseMock, $result);
    }

    /**
     * Test exception handling
     */
    public function testExecuteExceptionHandling(): void
    {
        $params = [
            'clientId' => 'LIVE_CLIENT_ID',
            'environment' => '1',
            'merchantPrivateKey' => 'test_private_key',
            'antomPublicKey' => 'test_public_key',
            'gatewayUrl' => 'https://test-gateway.com',
            'storeId' => '1'
        ];

        $this->requestMock->method('getParams')->willReturn($params);

        // Create a partial mock to simulate exception
        $controller = $this->getMockBuilder(Validate::class)
            ->setConstructorArgs([
                $this->contextMock,
                $this->loggerMock,
                $this->jsonHandlerMock,
                $this->configMock,
                $this->requestHelperMock
            ])
            ->onlyMethods(['consult'])
            ->getMock();

        $controller->method('consult')->willThrowException(new \Exception('Test exception'));

        $expectedResult = [
            'isValid' => false,
            'message' => __('Validation failed. Please check your configurations.')
        ];

        $this->jsonHandlerMock->method('convertToJSON')
            ->with($expectedResult)
            ->willReturn('{"isValid":false,"message":"Validation failed. Please check your configurations."}');

        $this->responseMock->method('representJson')
            ->with('{"isValid":false,"message":"Validation failed. Please check your configurations."}')
            ->willReturnSelf();

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Unknown exception: Exception: Test exception'));

        $result = $controller->execute();
        $this->assertSame($this->responseMock, $result);
    }
}
