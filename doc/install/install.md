# Installation guide:

Install module by Composer as follows:

```bash
composer require akeneo/magento2-connector-community
```

Enable and install module in Magento:

```bash
php bin/magento module:enable Akeneo_Connector
```

Check and update database setup:
```bash
php bin/magento setup:db:status
php bin/magento setup:upgrade
```

Flush Magento caches
```bash
php bin/magento cache:flush
```

##### [> Back to summary](../summary.md)
