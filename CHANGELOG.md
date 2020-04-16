# Akeneo Connector change log

### Version 100.1.0 :
* Initial Akeneo Connector Release

### Version 100.1.1 :
* Fix attribute mapping key

### Version 100.2.1 :
* Add website mapping from select or multiselect attribute in Akeneo
* Use native Magento serializer
* Fix proxy class injection in command construct
* Fix association import when result is empty
* Fix url_key mapping and generation

### Version 100.2.2 :
* Fix issue when importing associations
* Improve attribute option import

### Version 100.2.3 :
* Fix identifier column type in temporary product import table
* Fix missing where statement on delete in website association feature
* Fix product website request if attribute is not filled in Akeneo
* Fix duplicate node in config.xml file
* Add check on family label to prevent import error on duplicate labels in Akeneo

### Version 100.2.4 :
* Fix import command description
* Convert uppercase attribute mapping to lowercase
* Set import job response after step finish events

### Version 100.2.5 :
* Improve configurable attributes feature with specific types

**Warning :** *After updating connector to this version, please check the `Configurable` configuration under the `Products` section in the Akeneo Connector configuration and update the `Type` column of your mapping with the appropriate value if necessary.*

### Version 100.2.6 :
* Add check to prevent the creation of attributes and options with empty admin label
* Fix product association deletion with differential product import

### Version 100.3.0 :
* Remove Akeneo attribute group import from connector (https://help.akeneo.com/magento2-connector/v100/articles/where-attributes.html#where-to-find-my-attribute-groups-in-magento-2)
* Remove automatic mapping for attributes "price", "special_price" and "cost" (https://help.akeneo.com/magento2-connector/v100/articles/what-data.html#attribute-types)
* Add metric as product variant and unit concatenation feature (https://help.akeneo.com/magento2-connector/v100/articles/05-configure-products.html#metric-attributes)
* Update wording for configurable product attribute mapping

### Version 100.3.1 :
* Fix product image name that should not exceed 90 characters since Magento 2.3.3

**Warning :** *After updating connector to this version, all image names will be renamed. To know more, please consult documentation (https://help.akeneo.com/magento2-connector/v100/articles/06-import-images-configuration.html)*

* Remove unused "file" column on log grid
* Move API client call from construct
* Fix category URL issue adding -1, -2 to url-key when category had same name but not same parent category

### Version 100.3.2 :
* Fix Object Manager usage
* Fix category URL request missing "parent_id" select

### Version 100.3.3 :
* Fix error on price attribute import
* Fix category attribute set getter to prevent mixed id in case of data migration
* Fix metric import when metric attribute code contains uppercase characters
* Add product model batch size and request size to prevent MYSQL errors (https://help.akeneo.com/magento2-connector/v100/articles/05-configure-products.html#product-model-batch-size-and-product-model-update-length)

### Version 100.3.4 :
* Fix product URL rewrite generation to prevent duplicate entry errors
* Fix product URL generation for configurable product in case of mapping with url_key

### Version 100.4.0 :
* Add automatic mapping for existing attributes, attribute options and products in Magento (https://help.akeneo.com/magento2-connector/v100/articles/existing-magento.html)
* Add entities check in connector entities table before import

### Version 100.4.2 :
* Add support for file attributes import (https://help.akeneo.com/magento2-connector/v100/articles/05-configure-products.html#import-file-attributes)
* Add feature to apply default status to new products (https://help.akeneo.com/magento2-connector/v100/articles/05-configure-products.html#default-product-status)

### Version 100.4.3 :
* Fix attribute position inside attribute groups being set to 0 during attribute import job

### Version 100.4.4 :
* Global classes reformatting including: change private scope of variables and functions, remove AbstractHelper usage, fix usage of Akeneo\Connector\Job\Import class and change job types to object

**Warning :** *After updating connector to this version, make sure to recompile your code, flush Magento 2 cache and check your custom developments*

* Add support for Magento 2 Enterprise Content Staging feature (https://help.akeneo.com/magento2-connector/v100/articles/05-configure-products.html#is-akeneo-master-for-content-staging)
* Fix Magento 2 Enterprise is_returnable default value for products
* Fix multiselect attribute options assignation causing multiple options with similar code to be selected
* Fix attribute option code separator in akeneo_connector_entities table