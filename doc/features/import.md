# Import:

### About the import:

Akeneo connector inserts all the data into a temporary table. Then, data is manipulated (mapping,...) within this temporary table in SQL. Finally, the modified content is directly inserted in SQL in Magento 2 tables.

Even if raw SQL insertion is not the way you usually import data into a system, it is far faster than anything else, especially with the volume of data within a full Akeneo catalog. This results in a significant time saving for your imports.

### Import Order:

In order to prevent errors due to missing data in Magento 2, you need to launch the jobs in a specific order:
* Categories
* Families
* Attributes
* Options
* Product Models
* Family Variants
* Products

You can skip steps, but be careful! If, for example, you want to import attribute options, have newly created attributes, and don't import them before (even if you don't want to import this option for those missing attributes), it will result in an error. 

So check your data before importing it!

### Media import:

Media files are imported during the product import process.

You can configure the attributes to use in the Magento Catalog > Akeneo connector configuration section.

##### [> Back to summary](../summary.md)
