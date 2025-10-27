# Antom Adminhtml Module

[![CI](https://github.com/ant-intl/ant-intl-plugin-magento2-adminhtml/workflows/CI/badge.svg)](https://github.com/ant-intl/ant-intl-plugin-magento2-adminhtml/actions)
[![codecov](https://codecov.io/github/ant-intl/ant-intl-plugin-magento2-adminhtml/branch/main/graph/badge.svg)](https://codecov.io/github/ant-intl/ant-intl-plugin-magento2-adminhtml)

Antom Payment Adminhtml Module for Magento 2. This module provides the admin functionality for integrating Antom payment with Magento 2.

## Features

- Admin interface to configure Antom Payment API credentials (API Key, API Secret)
- Enable or disable Antom Payment methods
- Manage environment mode: Sandbox or Production

## Installation

This module can be installed via composer:
```
composer require antom-magento/magento2-adminhtml
```
Next, enable the module:
```
php bin/magento module:enable Antom_Adminhtml
```

Next, run the following commands:
```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```
**Please keep in mind that after installing this module, you will only have backend and core functionalities.**

## Support
Contact us: tech.support.na@service.alipay.com