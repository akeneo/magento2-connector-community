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

### Version 101.3.5 :
* Fix completeness in advanced filter mode for product model not being applied
* Add export Akeneo Connector configuration button in the admin configuration page
* Add advanced filter mode in configuration (https://help.akeneo.com/magento2-connector/articles/13-advanced-loging.html)
* Add Akeneo Growth Edition in Akeneo Edition selector configuration (https://help.akeneo.com/magento2-connector/articles/02-configure-PIM-API.html#configure-your-akeneo-edition)

### Version 101.3.6 :
* Optimize image import by storing result from API calls
* Fix error message on metric option creation when no value was found for an entire locale
* Fix export PDF generation for image configuration and strpos strict testing
* Update URL rewrite warning message color

### Version 101.5.0 :
* Update URL rewrite generation for products in order to either correctly assign by stores the values of an attribute from Akeneo or assign the SKU as default URL key

### Version 101.6.0 :
* Add configuration to set Akeneo attribute option code as Admin label for options in Magento 2 (https://help.akeneo.com/magento2-connector/articles/14-configure-attribute-options.html)
* Add "Since last X hours" product filter in order to filter Akeneo product updated date in hours (https://help.akeneo.com/magento2-connector/articles/03-products-filter-configuration.html#how-to-import-only-updated-products)

### Version 101.6.1 :
* Fix existing attribute option mapping from admin label with Akeneo attribute options

### Version 101.7.0 :
* Add content staging support for category import (https://help.akeneo.com/magento2-connector/articles/04-categories-configuration.html#does-akeneo-data-override-content-staging)

### Version 101.8.0 :
* Add configurations to choose which cache to flush after each job (https://help.akeneo.com/magento2-connector/articles/16-configure-cache-flush.html)
* Add configurations to choose which index to refresh after each job (https://help.akeneo.com/magento2-connector/articles/15-configure-index-refresh.html)

### Version 101.8.1 :
* Add index to "code" column in every job temporary tables
* Add index to "identifier" column in product job temporary table
* Add index to "attribute" column in option job temporary table

### Version 101.8.2 :
* Fix issue on temporary table indexes when a column is missing

### Version 102.0.0 :
* Add new asynchronous import system: (https://help.akeneo.com/magento2-connector/articles/trigger.html#how-to-trigger-import-jobs)
    * Remove old console import in "System > Akeneo Connector > Jobs"
    * Add new "akeneo_connector_job" table to manage job entity
    * Add new cron task "akeneo_connector_launch_scheduled_job" to run jobs in background
    * Add new job grid under "System > Akeneo Connector > Jobs" in order to manually schedule and run jobs
    * Prevent concurrent job trigger if a job is already scheduled or running

**Warning :** *In order to use the new import system, please make sure that Magento 2 CRON are correctly running (https://help.akeneo.com/magento2-connector/articles/all-pre-requisite.html#configure-your-magento-2)*

### Version 102.0.1 :
* Fix content staging scheduled update for a product, or a category without end date being updated wrongfully when "Does Akeneo data override content staging" configuration is set to "No"

### Version 102.0.2 :
* Fix website mapping not working when product job is scheduled and if "Set attribute option code as Admin label for attribute options" configuration is set to "Yes"

### Version 102.1.0 :
* Add "Since last successful import" filter for product job (https://help.akeneo.com/magento2-connector/articles/03-products-filter-configuration.html#how-to-import-only-updated-products)
* Add product type attribute mapping for virtual products (https://help.akeneo.com/magento2-connector/articles/05-configure-products.html#product-type-mapping)
* Add Akeneo Connector CRON group (https://help.akeneo.com/magento2-connector/articles/trigger.html#how-to-manually-schedule-and-trigger-each-job)
* Fix processing label display in log grid
* Update API Secret configuration to obscure type
* Update all connector configuration scopes to "Global"

### Version 102.1.1 :
* Fix Product job execution per family not continuing after a job error occurs in a specific family

### Version 102.1.2 :
* Fix URL rewrite generation when mapping a scopable attribute to the url_key attribute
* Fix Product job execution per family not continuing after a job error occurs in a specific family

### Version 102.1.4 :
* Add compatibility with the new Akeneo table attribute type (https://help.akeneo.com/magento2-connector/articles/what-data.html#attribute-types)

### Version 102.1.5 :
* Remove "NOT IN" family filter in API call during product job when using Standard filter

### Version 102.2.0 :
* Add new "Status mode" in order to assign simple product status from a completeness level (https://help.akeneo.com/magento2-connector/articles/05-configure-products.html#product-status-mode)
* Add job logs cleaning task (https://help.akeneo.com/magento2-connector/articles/17-configure-logs-cleaning.html)
* Add email reporting for job execution (https://help.akeneo.com/magento2-connector/articles/18-configure-job-email-reports.md.html)
* Use PHP short syntax and escape translations in templates
* Fix Magento 2 serializer usage to encode and decode JSON

### Version 102.2.1 :
* Fix website mapping with uppercase attribute code in Akeneo

### Version 102.3.0 :
* Fix "is_null()" and "empty()" usage
* Fix "akeneo_connector:import" command help usage
* Fix product job status to error and don't update import success date when one of the families in the job fails
* Fix localizable and scopable attributes being created with wrong scope
* Remove filters on the admin job grid
* Update success messages when scheduling a job from the admin grid
* Update connector tables definition to "db_schema.xml"
* Update setup scripts to patch format
* Fix message column format from "akeneo_connector_import_log_step" table to text in order to see full log messages

### Version 102.3.1 :
* Improve option job performance by optimizing existing option mapping requests and process

### Version 102.3.2 :
* Fix product job status still being "Processing" if the last family imported have no product to update

### Version 102.4.0 :
* Update temporary tables default column type from "text" to "mediumtext" in order to manage maximum field size for "textfield" attributes in Magento
* Fix "IN" family filter for advanced product filter mode importing every family instead of only one

### Version 102.5.0 :
* Add product status mode "Attribute mapping" in order to map a Yes/No attribute to the status attribute (https://help.akeneo.com/magento2-connector/articles/05-configure-products.html#attribute-mapping-for-status)
* Use "is_root" Akeneo API parameter for category endpoint in order to generate options for the category import configuration in the admin page

### Version 102.5.1 :
* Fix every family being fetched while using IN family search with advanced filter mode

### Version 102.6.0 :
* Add automatic 301 redirect when the url_key of a product is updated (https://help.akeneo.com/magento2-connector/articles/05-configure-products.html#regenerate-url-rewrites)
* Fix data patch to correctly encrypt API Client secret when upgrading from previous version

### Version 102.6.1 :
* Fix "Product Status Mode" not displaying if Akeneo version is different from "Serenity" or "Growth" edition
* Fix "Since last successful import" mode not working on first product job without successful date
* Fix relations from "catalog_product_relation" table for grouped and bundle products being deleted during the product job
* Fix job status to "Error" if missing or wrong API credentials are set in the connector configuration

### Version 103.0.0 :
* Add compatibility for Magento 2.4.4 and PHP 8.1: (https://help.akeneo.com/magento2-connector/articles/all-pre-requisite.html#compatibility)
  * Bump Akeneo API client to version 9
  * Replace Guzzle HTTP client by Symfony HTTP Client
  * Fix function usage for PHP 8
  * Update extension PHP compatibility to 7.4 and 8.1
* Fix "special_price" and "cost" attribute being set to 0 when empty on MariaDB

### Version 103.0.1 :
* Improve "setRelated" (Associations) and "setWebsites" (Website Attribute) steps performance for Product job
* Fix job status not displaying in mail report
* Fix metric unit being displayed twice if the same attribute is configured in "Metric Attributes"

### Version 103.0.2 :
* Fix "setRelated" temporary table name to MYSQL maximum table name length
* Fix "price", "special_price" and "cost" attribute import by setting default value for every temporary table columns to `NULL`

### Version 103.0.3 :
* Fix simple product association with configurable product when a variation from a two-level family variant is imported without the product model

### Version 103.0.4 :
* Fix "setRelated" temporary table name to unique value
* Fix product model variant change in `catalog_product_super_attribute` table
* Fix `Too few arguments to function Laminas\Diactoros\ResponseFactory::__construct()` issue by adding `nyholm/psr7` dependency
* Fix missing "$edition" variable in `Job/Product.php`

### Version 103.0.5 :
* Fix product relation deletion for simple products when using differential import
* Fix default status mode when scheduled changes are used for products
* Add new dispatch events at error and success in the job executor

### Version 103.0.6 :
* Fix manually added product videos being deleted by the image attribute import

### Version 103.0.7 :
* Fix Magento Framework dependency in composer.json
* Fix advanced filter error when left empty
* Improve URL generation requests for product job

### Version 103.0.8 :
* Fix URL rewrite generation when multiple url keys are duplicated

### Version 103.2.0 :
* Improve column sizes per attribute type inside product temporary table in order to reduce the MYSQL table volume

### Version 103.2.1 :
* Fix product model completeness filter channel value when multiple channels are mapped to different websites

### Version 103.3.0 :
* Add localisable and scopable image attribute import (https://help.akeneo.com/magento2-connector/articles/06-import-images-configuration.html#how-can-i-retrieve-images-from-the-image-attributes)
* Add job grid automatic refresh in order provide real time progress of jobs (https://help.akeneo.com/magento2-connector/articles/trigger.html#how-to-manually-schedule-and-trigger-each-job)
* Add attribute option mapping when a select / multi-select attribute is mapped with another one (https://help.akeneo.com/magento2-connector/articles/existing-magento.html#attribute-options)
* Manage empty attribute values returned by Akeneo API by creating missing columns inside product temporary table
* Improve extension coding standards
* Fix column size improvement to manage uppercase attribute codes

### Version 103.3.1 :
* Fix product job import for a family with only the SKU attribute inside it

### Version 103.4.0 :
* Fix error return value must be of type string
* New endpoint to link product with the UUID
* Last Successful updated import date by family
* Allow to import product visibility from Akeneo attribute
* Improve filter and options in the log grid on family
* Manage visual and text swatch attributes
* Export configuration improvement
* "Product Status Mode" parameter missing when selecting version 5.0 Akeneo edition
* Magento to Adobe Commerce rebranding
* Error while importing the attribute option

### Version 103.4.1 :
* Fix length for custom attribute types
* New error message when website mapping fail
* Fix error when swatch attribute option is empty

### Version 103.4.2 :
* Magento 2.4.6 / PHP 8.2 compatibility

### Version 103.4.3 :
* Fix PHP < 8.1 compatibility
* Rolled back previous modification to avoid Undefined array key line 3013
* Avoid to erase swatch configuration
* Fix image mapping for scope global

### Version 103.5.0 :
* Select category trees to import instead of trees to exclude
* Select families to import instead of families to exclude
* Assets compatibility warning removed
* Allow to override default attribute values (like tax_class_id)
* Allow attribute named visibility in Akeneo
* Fix import products without name grid error
* Fix status assignation with virtual products
* Fix visibility attribute error
* Fix association when category does not exist
* Prevent import from crashing if the tmp table is missing sort_order column
* Keep the old sort_order for options if the order from Akeneo is null
* setCategory step improvement

### Version 104.0.0 :
* Upgrade minimal PHP dependency to 8.0
* Upgrade Akeneo PHP client to 11.2.0
* Allow to disable updated mode filter

### Version 104.0.1 :
* PGTO-357: Force "selected" attribute on multiselect before print
* PGTO-366: Add security check to avoid undefined index
* PGTO-369: Fix virtual product status update

### Version 104.0.2 :
* PGTO-369: Fix product status assignment regression
* PGTO-363: Fix visibility update

### Version 104.0.3 :
* PGTO-378: Fix product without category attribution
* PGTO-380: Rebuild Visual Merchandiser Dynamic Categories

### Version 104.0.4 :
* PGTO-376: Fix grouped product association with uuid
