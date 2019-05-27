# FAQ:

#### Do imports override my data?
Yes. If you send a value for a field, it will override the previous value for this field. However, Akeneo data should always be accurate!

#### Is Akeneo connector compatible with...?
If you want to check the compatibility of the module you can now go to the Compatibility section.

#### When Akeneo connector will be compatible with...?
Ensuring compatibility from one version to another remains our priority. Thanks to Akeneo API, the Akeneo connector is natively compatible with new versions of Akeneo although it may not include all the latest features. Get in touch with Akeneo to know about the roadmap of evolutions.

#### Can Akeneo connector handle multiple websites?
Yes Akeneo connector can handle multiple websites and multiple stores.

#### How to contribute to Akeneo connector?
You can contribute to Akeneo connector by submitting PR on Github. However, you need to respect a few criteria:
* Respect Akeneo connector logic and architecture
* Be sure to not break other features
* Submit a clean code
* Always update the documentation if you submit a new feature

#### Can Akeneo connector update existing products or categories in Magento?
As Akeneo connector is using direct queries and mapping table to speed up and optimize import time, it is not able to recognize products and categories that have not been imported with the connector.

#### How to set product name as URL
Add Akeneo attribute mapping that points to the field "url_key":
* Go to Store > Configuration > Catalog > Akeneo connector
* Add attribute mapping
* Set Akeneo to "akeneo_product_name_field"
* Set Magento to "url_key"
* Save

##### [> Back to summary](../summary.md)
