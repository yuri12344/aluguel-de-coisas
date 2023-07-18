<?php
/**
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
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\HomeSection\SectionDataTrait;
use App\Http\Controllers\Api\HomeSection\SectionSettingTrait;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\HomeSectionResource;
use App\Models\HomeSection;

/**
 * @group Home
 */
class HomeSectionController extends BaseController
{
	use SectionDataTrait, SectionSettingTrait;
	
	protected $homeSectionClass = '\extras\plugins\domainmapping\app\Models\DomainHomeSection';
	
	/**
	 * List sections
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): \Illuminate\Http\JsonResponse
	{
		$countryCode = config('country.code');
		
		// Get all homepage sections
		$cacheId = $countryCode . '.homeSections';
		$sections = cache()->remember($cacheId, $this->cacheExpiration, function () use ($countryCode) {
			$sections = collect();
			
			// Check if the Domain Mapping plugin is available
			if (config('plugins.domainmapping.installed')) {
				try {
					$sections = $this->homeSectionClass::query()
						->where('country_code', $countryCode)
						->orderBy('lft')
						->get();
				} catch (\Throwable $e) {
				}
			}
			
			// Get the entry from the core
			if ($sections->count() <= 0) {
				$sections = HomeSection::query()->orderBy('lft')->get();
			}
			
			return $sections;
		});
		
		$homeSections = [];
		if ($sections->count() > 0) {
			$sections = $sections->keyBy('method');
			foreach ($sections as $key => $section) {
				// Clear method name
				$method = str_replace(strtolower($countryCode) . '_', '', $section->method);
				
				// Check if method exists
				if (!method_exists($this, $method)) {
					continue;
				}
				
				$settingMethod = $method . 'Settings';
				
				// Call the method
				try {
					$optionName = $key . 'Op';
					$homeSections[$key]['method'] = $key;
					$homeSections[$key]['data'] = $this->{$method}($section->value);
					$homeSections[$key]['view'] = $section->view;
					if (method_exists($this, $settingMethod)) {
						$homeSections[$key][$optionName] = $this->{$settingMethod}($section->value);
					} else {
						$homeSections[$key][$optionName] = $section->value;
					}
					$homeSections[$key]['lft'] = $section->lft;
				} catch (\Throwable $e) {
					return $this->respondError($e->getMessage());
				}
			}
		}
		
		$resourceCollection = new EntityCollection(class_basename($this), $homeSections);
		
		return $this->respondWithCollection($resourceCollection);
	}
	
	/**
	 * Get section
	 *
	 * Get category by its unique slug or ID.
	 *
	 * @queryParam parentCatSlug string The slug of the parent category to retrieve used when category's slug provided instead of ID. Example: automobiles
	 *
	 * @urlParam method string required The key/method of the section. Example: getCategories
	 *
	 * @param $method
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public function show($method): \Illuminate\Http\JsonResponse
	{
		$countryCode = config('country.code');
		
		// Get all homepage sections
		$cacheId = $countryCode . '.homeSections.' . $method;
		$section = cache()->remember($cacheId, $this->cacheExpiration, function () use ($countryCode, $method) {
			$section = null;
			
			// Check if the Domain Mapping plugin is available
			if (config('plugins.domainmapping.installed')) {
				try {
					$section = $this->homeSectionClass::query()
						->where('country_code', $countryCode)
						->where('method', $method)
						->first();
				} catch (\Throwable $e) {
				}
			}
			
			// Get the entry from the core
			if (empty($section)) {
				$section = HomeSection::query()->where('method', $method)->first();
			}
			
			return $section;
		});
		
		abort_if(empty($section), 404, t('home_section_not_found'));
		
		$homeSection = [];
		
		// Clear method name
		$method = str_replace(strtolower($countryCode) . '_', '', $section->method);
		
		// Check if method exists
		abort_if(!method_exists($this, $method), 404, t('home_section_not_found'));
		
		$dataCanBeFetched = (request()->filled('fetchData') && (int)request()->get('fetchData') == 1);
		$settingMethod = $method . 'Settings';
		
		// Call the method
		try {
			$optionName = $method . 'Op';
			$homeSection['method'] = $method;
			$homeSection['data'] = $dataCanBeFetched ? $this->{$method}($section->value) : null;
			$homeSection['view'] = $section->view;
			if (method_exists($this, $settingMethod)) {
				$homeSection[$optionName] = $this->{$settingMethod}($section->value);
			} else {
				$homeSection[$optionName] = $section->value;
			}
			$homeSection['lft'] = $section->lft;
		} catch (\Throwable $e) {
			return $this->respondError($e->getMessage());
		}
		
		$resource = new HomeSectionResource($homeSection);
		
		return $this->respondWithResource($resource);
	}
}
