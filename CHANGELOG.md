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