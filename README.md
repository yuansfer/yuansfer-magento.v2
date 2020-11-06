# Yuansfer plugin for Magento 2.X

## Installation

* Upload the `app` and `lib` directories to the root directory of magento2.
* Login to your admin panal, select menu `Stores` -> `Configration` -> `Sales` -> `Payment Methods` -> `Yuansfer Module`
* After setting, click `Save Config`

## Note

If you can not find `Yuansfer Module` option in `Payment Methods`, Please confirm interface program file is in the root directory of the website, then refresh the site cache(Select menu: `System` -> `Cache Management`).

Run the command `php bin/magento setup:upgrade` from your root path of your website, so enable the module.
