<?=$this->element('userBreadcrumbs')?>
<div class="uk-container dashpage">
	<div class="uk-grid">
		<?=$this->element('userSidebar')?>
		<div class="uk-width-3-4@m">
			<?=$this->Notice->show()?>
			<h2 class="uk-margin-top"><?=__('Featured Ads Panel')?></h2>
			<?=
				$this->element('adsPanelBoxes', array(
				'add' => array('controller' => 'featured_ads', 'action' => 'add'),
				'buy' => array('controller' => 'featured_ads', 'action' => 'buy'),
				'assign' => array('controller' => 'featured_ads', 'action' => 'assign'),
				))
				?>
			<?php if(empty($packages)): ?>
			<h5><?=__('Sorry, but currently there are no available advertisement exposures')?></h5>
			<?php else: ?>
			<h2 class="uk-margin-top"><?=__('Purchase advertisement exposures')?></h2>
			<div class="uk-form-horizontal">
				<?=$this->UserForm->create(false)?>
				<div class="uk-margin">
					<label class="uk-form-label"><?=__('Pack')?></label>
					<?=$this->UserForm->input('package_id', array('class' => 'uk-select', 'id' => 'packSelect'))?>
				</div>
				</fieldset>
				<div class="uk-margin">
					<label id="pricePerLabel" class="uk-form-label"><?=__('Price Per Click')?></label>
					<input type="text" readonly="" placeholder="$0.001" class="uk-input" id="pricePer">
				</div>
				<div class="uk-margin">
					<label class="uk-form-label"><?=__('Total Price')?></label>
					<input type="text" readonly="" placeholder="$10" class="uk-input" id="price">
				</div>
				<div class="uk-margin uk-text-center">
					<?=$this->UserForm->getGatewaysButtons($activeGateways, array('prices' => $packagesData[key($packagesData)]['FeaturedAdsPackage']['prices']))?>
				</div>
			</div>
			<?=$this->UserForm->end()?>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php
	$clicksLabel = __('Price Per Click');
	$daysLabel = __('Price Per Day');
	$impressionsLabel = __('Price Per Impression');
	$packagesData = json_encode($packagesData);
	
	$this->Js->buffer("
	var Data = $packagesData;
	
	function calcPrice() {
	var pack_id = $('#packSelect').val();
	
	switch(Data[pack_id]['FeaturedAdsPackage']['type']) {
	case 'Clicks':
	$('#pricePerLabel').html('$clicksLabel');
	break;
	
	case 'Days':
	$('#pricePerLabel').html('$daysLabel');
	break;
	
	case 'Impressions':
	$('#pricePerLabel').html('$impressionsLabel');
	break;
	}
	$('#price').attr('placeholder', formatCurrency(Data[pack_id]['FeaturedAdsPackage']['price']));
	$('#pricePer').attr('placeholder', formatCurrency(Data[pack_id]['FeaturedAdsPackage']['price_per']));
	
	$.each(Data[pack_id]['FeaturedAdsPackage']['prices'], function(key, value) {
	if(value == 'disabled') {
	$('.' + key + 'Price').parent().hide();
	} else {
	$('.' + key + 'Price').parent().show();
	$('.' + key + 'Price').html(formatCurrency(value));
	}
	});
	}
	
	$('#packSelect').change(calcPrice);
	calcPrice();
	");
	?>
