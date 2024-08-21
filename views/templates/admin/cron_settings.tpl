{*
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
* versions in the future. If you wish to customize this module for your needs
* please refer to CustomizationPolicy.txt file inside our module for more information.
*
* @author Webkul IN
* @copyright Since 2010 Webkul
* @license https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
*}

<div class="panel">
	<div class="panel-heading">
		<i class="icon-book"></i>
		{l s='Cron settings' mod='preorder'}
	</div>
	<div class="form-wrapper">
		<div class="alert alert-info">
			<p>{l s='First of all, make sure the curl library is installed on your server to execute your cron tasks.' mod='preorder'}</p>
		</div>
		<div class="alert alert-info">
			<p>{l s='For updating preorder products, please insert the following line in your cron tasks manager for everyday :' mod='preorder'}</p>
			<br>
			<p>
				<ul class="list-unstyled">
					<li><code>0 0 * * * curl {$updateProductsAfterCron|escape:'html':'UTF-8'}</code></li>
				</ul>
			</p>
		</div>
	</div>
</div>
