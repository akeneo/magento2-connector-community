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

### Version 100.4.5 :
* Remove URL key generation for non visible products
* Fix metric option creation when a value is set on a product model specific attribute
* Fix metric value set to "0" when empty on Akeneo
* Fix website association not deleted when website attribute value is empty for a product
* Optimize deletion of akeneo_connector_product_model columns during product model import job

### Version 100.4.6 :
* Add product import job batching family after family (https://help.akeneo.com/magento2-connector/v100/articles/overview.html#product-import-process)
* Optimize product model column number in temporary table by adding filter for API request on mapped channels and available locales

### Version 100.4.7 :
* Fix product default status behavior conflict with Magento 2 Enterprise content staging feature
* Optimize category association deletion request during product import
* Skip product URL rewrite generation for non associated websites

### Version 100.4.8 :
* Fix strict type for Akeneo PHP client pagination variable
* Fix product count during product model job
* Optimize product association deletion request

### Version 100.4.9 :
* Fix product filters not being reseted after each family during command line execution
* Fix create table function causing some products and product models not being imported in multi-website environment
* Fix misleading warning label during image import
* Fix Akeneo API connection being initialized in construct on the admin configuration page of the connector

### Version 100.4.10 :
* Add configuration check for empty mapping
* Fix category url_path generation
* Fix category and family source model return when API credentials are not configured
* Fix advanced filter issue with family filter variable

### Version 100.4.11 :
* Fix website mapping attribute code comparison to lowercase
* Fix configurable mapping attribute code comparison to lowercase

### Version 100.4.12 :
* Add credentials check before command line import jobs
* Add pagination to API calls in the admin configuration page
* Add security to prevent import of attributes starting with numbers (https://help.akeneo.com/magento2-connector/v100/articles/what-data.html#attributes)
* Add column filtering for job status in the admin connector log grid
* Fix custom options deletion after each product import

### Version 100.4.13 :
* Fix command constructor inverted comments causing compilation issue

### Version 100.4.14 :
* Fix product model temporary table data insertion when using multiple channels

### Version 100.4.15 :
* Fix image import with uppercase attribute codes

### Version 100.4.16 :
* Fix file import with uppercase attribute codes
* Fix auto_increment generation compliance with MYSQL 8

### Version 101.0.0 :
* Add product model import family by family: (https://help.akeneo.com/magento2-connector/v100/articles/overview.html)
    * Remove "Product Model" and "Family Variant" jobs (https://help.akeneo.com/magento2-connector/v100/articles/trigger.html)
    * Remove deprecated table "akeneo_connector_product_models" (https://help.akeneo.com/magento2-connector/v100/articles/upgrade-connector.html)
    * Remove deprecated configuration "Product Model Batch Size" and "Product Model Update Length" (https://help.akeneo.com/magento2-connector/v100/articles/05-configure-products.html)
    * Merge "Product Model" and "Family Variant" job into "Product" job (https://help.akeneo.com/magento2-connector/v100/articles/overview.html#product-import-process)
    * Add specific "Product Model Completeness Filter" configuration (https://help.akeneo.com/magento2-connector/v100/articles/03-products-filter-configuration.html)
    * Add specific "Product Model Advanced Filter" configuration (https://help.akeneo.com/magento2-connector/v100/articles/03-products-filter-configuration.html)
    * Apply "Standard Product Filters" to product models (https://help.akeneo.com/magento2-connector/v100/articles/03-products-filter-configuration.html)
* Add automatic mapping of product model specific attributes in the "Configurable" configuration of the connector (https://help.akeneo.com/magento2-connector/v100/articles/05-configure-products.html#configurable-product-attributes-and-default-values)
* Remove type "Product Model Value" from "Configurable" configuration (https://help.akeneo.com/magento2-connector/v100/articles/05-configure-products.html#configurable-product-attributes-and-default-values)

**Warning :** *After updating Akeneo Connector for Magento 2 to this version, make sure to check to following:*
* *Remove the previously declared CRON jobs for old "Product Model" and "Family Variant" jobs*
* *Audit and rework your previous customizations on the "Product Model", "Family Variant" and "Product" jobs, as this new version contains compatibility break changes*

### Version 101.0.2 :
* Add documentation link in the connector admin configuration page
* Fix metric attribute unit missing test case

### Version 101.1.0 :
* Add new Akeneo Edition selector configuration (https://help.akeneo.com/magento2-connector/v100/articles/02-configure-PIM-API.html#configure-your-akeneo-edition)

**Warning :** *After updating Akeneo Connector for Magento 2 to this version, make sure to configure the correct Akeneo Edition in your connector configuration*

* Add category tree filtering from API for Akeneo version 4.0.62 or greater and Akeneo Serenity
* Add family updated date filter in connector configuration for Akeneo version 4.0.62 or greater and Akeneo Serenity (https://help.akeneo.com/magento2-connector/v100/articles/10-configure-families.html#how-can-i-filter-my-families-during-import)

### Version 101.1.1 :
* Add attribute filtering by type from API in the admin configuration page for Akeneo version 4.0.62 or greater and Akeneo Serenity
* Add attribute updated date filter in connector configuration for Akeneo version 4.0.62 or greater and Akeneo Serenity (https://help.akeneo.com/magento2-connector/v100/articles/11-filter-attributes.html)
* Add attribute filter by code in connector configuration for Akeneo version 4.0.62 or greater and Akeneo Serenity (https://help.akeneo.com/magento2-connector/v100/articles/11-filter-attributes.html)
* Fix category tree filtering when no categories are excluded for Akeneo version 4.0.62 or greater and Akeneo Serenity
* Fix attribute job when no attribute is found with correct label
* Fix metric option import when no option is found

### Version 101.1.2 :
* Fix image attribute import to fill the catalog_product_entity_media_gallery_value table

### Version 101.2.0 :
* Add grouped product management with quantity association (https://help.akeneo.com/magento2-connector/v100/articles/12-configure-grouped-products.html)
* Add product association mapping in connector configuration (https://help.akeneo.com/magento2-connector/v100/articles/05-configure-products.html#configure-related-upsell-and-cross-sell-products)

### Version 101.3.0 :
* Upgrade Akeneo API PHP Client to version 6.0

**Warning :** *After updating Akeneo Connector for Magento 2 to this version, make sure to update your composer dependencies*

* Add Akeneo version 5.0 or greater in version selector

### Version 101.3.1 :
* Remove automatic scope filter that was added when using the product and product model Advanced Filter
* Add Family code in Magento 2 attribute set label to prevent SQL insertion error when multiple family have the same label
* Fix image attribute re-download check on wrong file path

### Version 101.3.2 :
* Change job error status to success when no products are imported for a family
* Update information warning messages color from red to orange

### Version 101.3.3 :
* Add compatibility for variation product parent change and variation product becoming a simple product in Akeneo
* Fix connector compatibility with Akeneo 3.1
* Fix category tree import with numeric code
* Fix category URL rewrite generation on useless stores causing "-X" added to category URLs
* Fix download of file attribute failing since last client upgrade

### Version 101.3.4 :
* Fix attribute requests condition on "entity_type_id" in order to prevent MYSQL errors during option import