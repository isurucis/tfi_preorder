{**
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License version 3.0
* that is bundled with this package in the file LICENSE.txt
* It is also available through the world-wide-web at this URL:
* https://opensource.org/licenses/AFL-3.0
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to a newer
* versions in the future. If you wish to customize this module for your
* needs please refer to CustomizationPolicy.txt file inside our module for more information.
*
* @author Webkul IN
* @copyright Since 2010 Webkul
* @license https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
*}

{if isset($geoIpAvailable) && !$geoIpAvailable}
    <div class="alert alert-warning" role="alert">
        <span class="alert-text">
            {l s='Since December 30, 2019, you need to register for a' mod='preorder'} <a href="{$geoip_database_url|escape:'html':'UTF-8'}" target="_blank">{l s='MaxMind account' mod='preorder'}</a> {l s='to get a license key to be able to download the geolocation data. Please download "GeoLite2 City" database, Once downloaded, extract the data using Winrar or Gzip into the /app/Resources/geoip/ directory.' mod='preorder'}
        </span>
    </div>
{/if}