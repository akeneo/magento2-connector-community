# Migrate from previous API version (Agence Dn’D PIMGento 2 API):

### First of, you will need to disable all PIMGento2 (API) modules:
```bash
php bin/magento module:disable Pimgento_Api
```

### Then, flush cache:
```bash
php bin/magento cache:flush
```

### Install new Akeneo connector extension:
```bash
composer require akeneo/magento2-connector-community

php bin/magento module:enable Akeneo_Connector
```

### Check and update database setup:
```bash
php bin/magento setup:db:status
php bin/magento setup:upgrade
```

### Flush cache again:
```bash
php bin/magento cache:flush
```

### Migrate data from pimgento tables to akeneo_connector tables:
Custom tables name have changed during connector migration.

You will have to copy content from pimgento tables to akeneo_connector table as it follows:

```sql
INSERT INTO akeneo_connector_entities SELECT * FROM pimgento_entities;
INSERT INTO akeneo_connector_family_attribute_relations SELECT * FROM pimgento_family_attribute_relations;
INSERT INTO akeneo_connector_import_log SELECT * FROM pimgento_import_log;
INSERT INTO akeneo_connector_import_log_step SELECT * FROM pimgento_import_log_step;
INSERT INTO akeneo_connector_product_model SELECT * FROM pimgento_product_model;
```

# Migrate from obsolete CSV version (Agence Dn’D PIMGento 2 CSV):

### First of, you will need to disable all PIMGento2 modules:
```bash
php bin/magento module:disable Pimgento_Category
php bin/magento module:disable Pimgento_Family
php bin/magento module:disable Pimgento_Attribute
php bin/magento module:disable Pimgento_Option
php bin/magento module:disable Pimgento_Variant
php bin/magento module:disable Pimgento_Product

php bin/magento module:disable Pimgento_Import
php bin/magento module:disable Pimgento_Entities

php bin/magento module:disable Pimgento_Log
```

### Then, flush cache:
```bash
php bin/magento cache:flush
```

### Install new Akeneo connector extension:
```bash
composer require akeneo/magento2-connector-community

php bin/magento module:enable Pimgento_Api
```

### Check and update database setup:
```bash
php bin/magento setup:db:status
php bin/magento setup:upgrade
```

### Flush cache again:
```bash
php bin/magento cache:flush
```

### Configure Akeneo connector in Magento 2 backend:
Please, follow our configuration guides: [Summary](../summary.md)

### Re-import all entities:
Please, follow our import guide: [Import guide](../features/import.md)

### Important notes:
It is important to know that the table "pimgento_variant" in the CSV version is now called "akeneo_connector_product_model". This is why you need to re-import all entities after the migration to be sure that all data is set correctly.

All custom rewrites of the previous PIMGento2 extension will be obsolete when migrating to Akeneo connector.

##### [> Back to summary](../summary.md)
