{{--
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - http://codecanyon.net/licenses/standard
--}}
@extends('layouts.master')

<?php
	$addListingUrl = (isset($addListingUrl)) ? $addListingUrl : \App\Helpers\UrlGen::addPost();
	$addListingAttr = '';
	if (!auth()->check()) {
		if (config('settings.single.guests_can_post_listings') != '1') {
			$addListingUrl = '#quickLogin';
			$addListingAttr = ' data-bs-toggle="modal"';
		}
	}
?>
@section('content')
	@includeFirst([config('larapen.core.customizedViewPath') . 'common.spacer', 'common.spacer'])
	<div class="main-container inner-page">
		<div class="container" id="pricing">
			
			<h1 class="text-center title-1" style="text-transform: none;">
				<strong>{{ t('Pricing') }}</strong>
			</h1>
			<hr class="center-block small mt-0">
			
			<p class="text-center">
				{{ t('premium_plans_hint') }}
			</p>
			
			<div class="row mt-5 mb-md-5 justify-content-center">
				@if (is_array($packages) && count($packages) > 0)
					@foreach($packages as $package)
						<?php
							$boxClass = (data_get($package, 'recommended') == 1) ? ' border-color-primary' : '';
							$boxHeaderClass = (data_get($package, 'recommended') == 1) ? ' bg-primary border-color-primary text-white' : '';
							$boxBtnClass = (data_get($package, 'recommended') == 1) ? ' btn-primary' : ' btn-outline-primary';
						?>
						<div class="col-md-4">
							<div class="card mb-4 box-shadow{{ $boxClass }}">
								<div class="card-header text-center{{ $boxHeaderClass }}">
									<h4 class="my-0 fw-normal pb-0 h4">{{ data_get($package, 'short_name') }}</h4>
								</div>
								<div class="card-body">
									<h1 class="text-center">
										<span class="fw-bold">
											@if (data_get($package, 'currency.in_left') == 1)
												{!! data_get($package, 'currency.symbol') !!}
											@endif
											{{ \App\Helpers\Number::format(data_get($package, 'price')) }}
											@if (data_get($package, 'currency.in_left') == 0)
												{!! data_get($package, 'currency.symbol') !!}
											@endif
										</span>
										<small class="text-muted">/ {{ t('package_entity') }}</small>
									</h1>
									<ul class="list list-border text-center mt-3 mb-4">
										@if (is_array(data_get($package, 'description_array')) && count(data_get($package, 'description_array')) > 0)
											@foreach(data_get($package, 'description_array') as $option)
												<li>{!! $option !!}</li>
											@endforeach
										@else
											<li> *** </li>
										@endif
									</ul>
									<?php
									$pricingUrl = '';
									if (str_starts_with($addListingUrl, '#')) {
										$pricingUrl = '' . $addListingUrl;
									} else {
										$pricingUrl = $addListingUrl . '?package=' . data_get($package, 'id');
									}
									?>
									<a href="{{ $pricingUrl }}"
									   class="btn btn-lg btn-block{{ $boxBtnClass }}"{!! $addListingAttr !!}
									>
										{{ t('get_started') }}
									</a>
								</div>
							</div>
						</div>
					@endforeach
				@else
					<div class="col-md-6 col-sm-12 text-center">
						<div class="card bg-light">
							<div class="card-body">
								{{ $message ?? null }}
							</div>
						</div>
					</div>
				@endif
			</div>
			
		</div>
	</div>
@endsection

@section('after_styles')
@endsection

@section('after_scripts')
@endsection