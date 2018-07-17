# FixProductBreadcrumbs
This is a Fix for Magento 2.2.4 and 2.2.5 where the Product Breadcrumbs are generated with JS normally and contain unwanted text

## Installation

Create a folder named **CompactCode** under your app/code folder within your Magento Root directory and place all provived files under that.

You need to perform the following commands after that *(within your magento root directory via ssh)*:

-php bin/magento cache:clear
-php bin/magento setup:upgrade
-php bin/magento setup:di:compile
-php bin/magento setup:static-content:deploy
