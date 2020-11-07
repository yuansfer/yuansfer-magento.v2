# Yuansfer plugin for Magento 2.X

`master` branch using the latest v3 API, see [2.x](https://github.com/yuansfer/yuansfer-magento.v2/tree/2.x) branch for v2 API. 

## Installation

* Upload the `app` and `lib` directories to the root directory of magento2.
* Login to your admin panal, select menu `Stores` -> `Configration` -> `Sales` -> `Payment Methods` -> `Yuansfer Module`
* After setting, click `Save Config`

## Note

If you can not find `Yuansfer Module` option in `Payment Methods`, Please confirm interface program file is in the root directory of the website, then refresh the site cache(Select menu: `System` -> `Cache Management`).

Run the command `php bin/magento setup:upgrade` from your root path of your website, so enable the module.
