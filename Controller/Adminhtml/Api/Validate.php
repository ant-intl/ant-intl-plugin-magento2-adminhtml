<?php
declare(strict_types=1);

namespace Antom\Adminhtml\Controller\Adminhtml\Api;

use Antom\Core\Config\AntomConfig;
use Antom\Core\Helper\RequestHelper;
use Antom\Core\Logger\AntomLogger;
use Antom\Core\Util\JsonHandler;
use Client\DefaultAlipayClient;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Model\Amount;
use Model\PaymentFactor;
use Model\PresentmentMode;
use Model\ProductCodeType;
use Model\SettlementStrategy;
use Request\pay\AlipayPayConsultRequest;

class Validate extends Action {
    private const ENVIRONMENT_PARAM_KEY_NAME = 'environment';
    private const GATEWAY_URL_KEY_PARAM = "gatewayUrl";
    private const CLIENT_ID_KEY_NAME = 'clientId';
    private const MERCHANT_PRIVATE_KEY_PARAM_KEY_NAME = 'merchantPrivateKey';

    private const ANTOM_PUBLIC_KEY_PARAM_KEY_NAME = 'antomPublicKey';

    /**
     * @var JsonHandler
     */
    private $jsonHandler;

    /**
     * @var AntomConfig
     */
    private $config;

    /**
     * @var AntomLogger
     */
    private $logger;

    /**
     * @var RequestHelper
     */
    private $requestHelper;


    /**
     * Validate constructor.
     *
     * @param Context $context
     * @param JsonHandler $jsonHandler
     * @param AntomConfig $config
     */
    public function __construct(
        Context $context,
        AntomLogger $logger,
        JsonHandler $jsonHandler,
        AntomConfig $config,
        RequestHelper $requestHelper
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->jsonHandler = $jsonHandler;
        $this->config = $config;
        $this->requestHelper = $requestHelper;
    }

    /**
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        try {
            if (($data = $this->getRequest()->getParams())
                && isset($data[self::CLIENT_ID_KEY_NAME], $data[self::ENVIRONMENT_PARAM_KEY_NAME],
                    $data[self::MERCHANT_PRIVATE_KEY_PARAM_KEY_NAME], $data[self::ANTOM_PUBLIC_KEY_PARAM_KEY_NAME],
                    $data[self::GATEWAY_URL_KEY_PARAM])
            ) {
                // pre-validate if clientId and environment(liveMode) matches
                if ($data[self::ENVIRONMENT_PARAM_KEY_NAME] == 0 && !str_starts_with($data[self::CLIENT_ID_KEY_NAME], 'SANDBOX')) {
                    $result = [
                        'isValid' => false,
                        'message' => __('Client ID is not SANDBOX mode'),
                    ];
                    return $this->getResponse()->representJson($this->jsonHandler->convertToJSON($result));
                }

                if ($data[self::ENVIRONMENT_PARAM_KEY_NAME] == 1 && str_starts_with($data[self::CLIENT_ID_KEY_NAME], 'SANDBOX')) {
                    $result = [
                        'isValid' => false,
                        'message' => __('Client ID is not LIVE mode'),
                    ];

                    return $this->getResponse()->representJson($this->jsonHandler->convertToJSON($result));
                }

                $merchantPrivateKey = (string)$data[self::MERCHANT_PRIVATE_KEY_PARAM_KEY_NAME];
                if (strpos($merchantPrivateKey, '****') !== false) {
                    $merchantPrivateKey = $this->config->getMerchantPrivateKey((int)$data['storeId'],
                        $data[self::ENVIRONMENT_PARAM_KEY_NAME]);
                }
                if (empty($merchantPrivateKey)) {
                    $result = [
                        'isValid' => false,
                        'message' => __('Merchant private key is not valid'),
                    ];
                } else {
                    $antomPublicKey = (string)$data[self::ANTOM_PUBLIC_KEY_PARAM_KEY_NAME];
                    $clientId = (string)$data[self::CLIENT_ID_KEY_NAME];
                    $gatewayUrl = (string)$data[self::GATEWAY_URL_KEY_PARAM];
                    $aliPayConsultResponse = $this->consult($gatewayUrl, $clientId, $merchantPrivateKey, $antomPublicKey);
                    if ("S" === $aliPayConsultResponse->getResultStatus()) {
                        $result = [
                            'isValid' => true,
                            'message' => __('Configuration is valid.'),
                        ];
                    } else {
                        $this->logger->error($aliPayConsultResponse->getResultMessage());
                        $result = [
                            'isValid' => false,
                            'message' => __("Validation failed. Please check your configuration."),
                        ];
                    }
                }
            } else {
                $this->logger->error("Fields are not properly filled in");
                $result = [
                    'isValid' => false,
                    'message' => __("Validation failed. Please check your configuration."),
                ];
            }
        } catch (Exception $exception) {
            // Log full details for developers
            $this->logger->error("Unknown exception: " . $exception);
            // Throw a safe, limited exception (or add error message)
            $result = [
                'isValid' => false,
                'message' => __("Validation failed. Please check your configurations."),
            ];
        }

        $response = $this->getResponse();
        return $response->representJson($this->jsonHandler->convertToJSON($result));
    }

    protected function consult(string $gatewayUrl, string $clientId, string $merchantPrivateKey, string $alipayPublicKey) : AlipayPayConsultResponse {
        $request = new AlipayPayConsultRequest();
        $settlementStrategy = new SettlementStrategy();
        $settlementStrategy->setSettlementCurrency("USD");
        $request->setSettlementStrategy($settlementStrategy);
        $request->setProductCode(ProductCodeType::CASHIER_PAYMENT);
        $request->setUserRegion("US");
        $request->setAllowedPaymentMethodRegions([
            "US"
        ]);
        $env = $this->requestHelper->composeEnvInfo();
        $request->setEnv($env);
        $amount = new Amount();
        $amount->setCurrency("USD");
        $amount->setValue("1000");
        $request->setPaymentAmount($amount);
        $paymentFactor = new PaymentFactor();
        $paymentFactor->setPresentmentMode(PresentmentMode::BUNDLE);
        $request->setPaymentFactor($paymentFactor);
        $alipayClient = new DefaultAlipayClient($gatewayUrl, $merchantPrivateKey, $alipayPublicKey, $clientId);
        $response = $alipayClient->execute($request);
        $alipayConsultResponse = AlipayPayConsultResponse::fromResponse($response);
        return $alipayConsultResponse;
    }
}
