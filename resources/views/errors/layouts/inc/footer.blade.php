<?php
$socialLinksAreEnabled = (
	config('settings.social_link.facebook_page_url')
	|| config('settings.social_link.twitter_url')
	|| config('settings.social_link.tiktok_url')
	|| config('settings.social_link.linkedin_url')
	|| config('settings.social_link.pinterest_url')
	|| config('settings.social_link.instagram_url')
);
$appsLinksAreEnabled = (
	config('settings.other.ios_app_url')
	|| config('settings.other.android_app_url')
);
$socialAndAppsLinksAreEnabled = ($socialLinksAreEnabled || $appsLinksAreEnabled);
?>
<footer class="main-footer">
	<?php
	$rowColsLg = $socialAndAppsLinksAreEnabled ? 'row-cols-lg-3' : 'row-cols-lg-2';
	$rowColsMd = $rowColsLg;
	
	$ptFooterContent = '';
	$mbCopy = ' mb-3';
	if (config('settings.footer.hide_links')) {
		$ptFooterContent = ' pt-sm-5 pt-5';
		$mbCopy = ' mb-4';
	}
	?>
	<div class="footer-content{{ $ptFooterContent }}">
		<div class="container">
			<div class="row {{ $rowColsLg }} {{ $rowColsMd }} row-cols-sm-2 row-cols-2 g-3">
				
				@if (!config('settings.footer.hide_links'))
					<div class="col">
						<div class="footer-col">
							<h4 class="footer-title">{{ t('Contact and Sitemap') }}</h4>
							<ul class="list-unstyled footer-nav">
								<li><a href="{{ \App\Helpers\UrlGen::contact() }}"> {{ t('Contact') }} </a></li>
								@if (!empty(config('lang.abbr')) && !empty(config('country.icode')))
									<li><a href="{{ \App\Helpers\UrlGen::sitemap() }}"> {{ t('sitemap') }} </a></li>
								@endif
								<li><a href="{{ \App\Helpers\UrlGen::countries() }}"> {{ t('countries') }} </a></li>
							</ul>
						</div>
					</div>
					
					<div class="col">
						<div class="footer-col">
							<h4 class="footer-title">{{ t('My Account') }}</h4>
							<ul class="list-unstyled footer-nav">
								@if (!auth()->user())
									<li><a href="{{ \App\Helpers\UrlGen::login() }}"> {{ t('log_in') }} </a></li>
									<li><a href="{{ \App\Helpers\UrlGen::register() }}"> {{ t('register') }} </a></li>
								@else
									<li><a href="{{ url('account') }}"> {{ t('My Account') }} </a></li>
									<li><a href="{{ url('account/posts/list') }}"> {{ t('my_listings') }} </a></li>
									<li><a href="{{ url('account/posts/favourite') }}"> {{ t('favourite_listings') }} </a></li>
								@endif
							</ul>
						</div>
					</div>
					
					@if ($socialAndAppsLinksAreEnabled)
						<div class="col">
							<div class="footer-col row">
								<?php
								$footerSocialClass = '';
								$footerSocialTitleClass = '';
								?>
								@if ($appsLinksAreEnabled)
									<div class="col-sm-12 col-12 p-lg-0">
										<div class="mobile-app-content">
											<h4 class="footer-title">{{ t('Mobile Apps') }}</h4>
											<div class="row">
												@if (config('settings.other.ios_app_url'))
													<div class="col-12 col-sm-6">
														<a class="app-icon" target="_blank" href="{{ config('settings.other.ios_app_url') }}">
															<span class="hide-visually">{{ t('iOS app') }}</span>
															<img src="{{ url('images/site/app-store-badge.svg') }}" alt="{{ t('Available on the App Store') }}">
														</a>
													</div>
												@endif
												@if (config('settings.other.android_app_url'))
													<div class="col-12 col-sm-6">
														<a class="app-icon" target="_blank" href="{{ config('settings.other.android_app_url') }}">
															<span class="hide-visually">{{ t('Android App') }}</span>
															<img src="{{ url('images/site/google-play-badge.svg') }}" alt="{{ t('Available on Google Play') }}">
														</a>
													</div>
												@endif
											</div>
										</div>
									</div>
									<?php
									$footerSocialClass = 'hero-subscribe';
									$footerSocialTitleClass = 'm-0';
									?>
								@endif
								
								@if ($socialLinksAreEnabled)
									<div class="col-sm-12 col-12 p-lg-0">
										<div class="{!! $footerSocialClass !!}">
											<h4 class="footer-title {!! $footerSocialTitleClass !!}">{{ t('Follow us on') }}</h4>
											<ul class="list-unstyled list-inline mx-0 footer-nav social-list-footer social-list-color footer-nav-inline">
												@if (config('settings.social_link.facebook_page_url'))
													<li>
														<a class="icon-color fb"
														   data-bs-placement="top"
														   data-bs-toggle="tooltip"
														   href="{{ config('settings.social_link.facebook_page_url') }}"
														   title="Facebook"
														>
															<i class="fab fa-facebook"></i>
														</a>
													</li>
												@endif
												@if (config('settings.social_link.twitter_url'))
													<li>
														<a class="icon-color tw"
														   data-bs-placement="top"
														   data-bs-toggle="tooltip"
														   href="{{ config('settings.social_link.twitter_url') }}"
														   title="Twitter"
														>
															<i class="fab fa-twitter"></i>
														</a>
													</li>
												@endif
												@if (config('settings.social_link.instagram_url'))
													<li>
														<a class="icon-color pin"
														   data-bs-placement="top"
														   data-bs-toggle="tooltip"
														   href="{{ config('settings.social_link.instagram_url') }}"
														   title="Instagram"
														>
															<i class="fab fa-instagram"></i>
														</a>
													</li>
												@endif
												@if (config('settings.social_link.linkedin_url'))
													<li>
														<a class="icon-color lin"
														   data-bs-placement="top"
														   data-bs-toggle="tooltip"
														   href="{{ config('settings.social_link.linkedin_url') }}"
														   title="LinkedIn"
														>
															<i class="fab fa-linkedin"></i>
														</a>
													</li>
												@endif
												@if (config('settings.social_link.pinterest_url'))
													<li>
														<a class="icon-color pin"
														   data-bs-placement="top"
														   data-bs-toggle="tooltip"
														   href="{{ config('settings.social_link.pinterest_url') }}"
														   title="Pinterest"
														>
															<i class="fab fa-pinterest-p"></i>
														</a>
													</li>
												@endif
												@if (config('settings.social_link.tiktok_url'))
													<li>
														<a class="icon-color tt"
														   data-bs-placement="top"
														   data-bs-toggle="tooltip"
														   href="{{ config('settings.social_link.tiktok_url') }}"
														   title="Tiktok"
														>
															<i class="fab fa-tiktok"></i>
														</a>
													</li>
												@endif
											</ul>
										</div>
									</div>
								@endif
							</div>
						</div>
					@endif
					
					<div style="clear: both"></div>
				@endif
			
			</div>
			<div class="row">
				
				<?php
					$mtPay = '';
					$mtCopy = ' mt-md-4 mt-3 pt-2';
				?>
				<div class="col-12">
					@if (!config('settings.footer.hide_payment_plugins_logos') && isset($paymentMethods) && $paymentMethods->count() > 0)
						@if (config('settings.footer.hide_links'))
							<?php $mtPay = ' mt-0'; ?>
						@endif
						<div class="text-center payment-method-logo{{ $mtPay }}">
							{{-- Payment Plugins --}}
							@foreach($paymentMethods as $paymentMethod)
								@if (file_exists(plugin_path($paymentMethod->name, 'public/images/payment.png')))
									<img src="{{ url('images/' . $paymentMethod->name . '/payment.png') }}" alt="{{ $paymentMethod->display_name }}" title="{{ $paymentMethod->display_name }}">
								@endif
							@endforeach
						</div>
					@else
						<?php $mtCopy = ' mt-0'; ?>
						@if (!config('settings.footer.hide_links'))
							<?php $mtCopy = ' mt-md-4 mt-3 pt-2'; ?>
							<hr class="border-0 bg-secondary">
						@endif
					@endif
					
					<div class="copy-info text-center mb-md-0{{ $mbCopy }}{{ $mtCopy }}">
						© {{ date('Y') }} {{ config('settings.app.name') }}. {{ t('all_rights_reserved') }}.
						@if (!config('settings.footer.hide_powered_by'))
							@if (config('settings.footer.powered_by_info'))
								{{ t('Powered by') }} {!! config('settings.footer.powered_by_info') !!}
							@else
								{{ t('Powered by') }} <a href="https://laraclassifier.com" title="LaraClassifier">LaraClassifier</a>.
							@endif
						@endif
					</div>
				</div>
			
			</div>
		</div>
	</div>
</footer>
