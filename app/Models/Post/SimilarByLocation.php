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

namespace App\Models\Post;

use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Larapen\LaravelDistance\Distance;

trait SimilarByLocation
{
	/**
	 * Get Posts in the same Location
	 *
	 * @param $distance
	 * @param int|null $limit
	 * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
	 */
	public function getSimilarByLocation($distance, ?int $limit = 20)
	{
		$posts = Post::query();
		
		$tablesPrefix = DB::getTablePrefix();
		$postsTable = (new Post())->getTable();
		
		if (!is_numeric($distance) || $distance < 0) {
			$distance = 0;
		}
		
		$select = [
			$postsTable . '.id',
			$postsTable . '.country_code',
			'category_id',
			'title',
			$postsTable . '.price',
			'city_id',
			'featured',
			'email_verified_at',
			'phone_verified_at',
			'reviewed_at',
			$postsTable . '.created_at',
			$postsTable . '.archived_at',
		];
		if (isFromApi() && !isFromTheAppsWebEnvironment()) {
			$select[] = $postsTable . '.description';
			$select[] = 'user_id';
			$select[] = 'contact_name';
			$select[] = $postsTable . '.auth_field';
			$select[] = $postsTable . '.phone';
			$select[] = $postsTable . '.email';
		}
		if (config('plugins.reviews.installed')) {
			$select[] = 'rating_cache';
			$select[] = 'rating_count';
		}
		
		$having = [];
		$orderBy = [];
		
		if (!empty($select)) {
			foreach ($select as $column) {
				$posts->addSelect($column);
			}
		}
		
		// Default Filters
		$posts->currentCountry()->verified()->unarchived();
		if (config('settings.single.listings_review_activation')) {
			$posts->reviewed();
		}
		
		// Use the Cities Extended Searches
		config()->set('distance.functions.default', config('settings.list.distance_calculation_formula'));
		config()->set('distance.countryCode', config('country.code'));
		
		if (isset($this->city) && !empty($this->city)) {
			if (config('settings.list.cities_extended_searches')) {
				
				// Use the Cities Extended Searches
				config()->set('distance.functions.default', config('settings.list.distance_calculation_formula'));
				config()->set('distance.countryCode', config('country.code'));
				
				$sql = Distance::select('lon', 'lat', $this->city->longitude, $this->city->latitude);
				if ($sql) {
					$posts->addSelect(DB::raw($sql));
					$having[] = Distance::having($distance);
					$orderBy[] = Distance::orderBy('ASC');
				} else {
					$posts->where('city_id', $this->city->id);
				}
				
			} else {
				
				// Use the Cities Standard Searches
				$posts->where('city_id', $this->city->id);
				
			}
		}
		
		// Relations
		$posts->with('postType');
		$posts->with('category', fn ($query) => $query->with('parent'))->has('category');
		$posts->with('pictures');
		$posts->with('city')->has('city');
		$posts->with('savedByLoggedUser');
		$posts->with('latestPayment', fn ($query) => $query->with('package'));
		$posts->with('user');
		$posts->with('user.permissions');
		
		if (isset($this->id)) {
			$posts->where($postsTable . '.id', '!=', $this->id);
		}
		
		// Set HAVING
		$havingStr = '';
		if (is_array($having) && count($having) > 0) {
			foreach ($having as $key => $value) {
				if (trim($value) == '') {
					continue;
				}
				if (str_contains($value, '.')) {
					$value = $tablesPrefix . $value;
				}
				
				if ($havingStr == '') {
					$havingStr .= $value;
				} else {
					$havingStr .= ' AND ' . $value;
				}
			}
			if (!empty($havingStr)) {
				$posts->havingRaw($havingStr);
			}
		}
		
		// Set ORDER BY
		// $orderBy[] = $tablesPrefix . $postsTable . '.created_at DESC';
		// $posts->orderByRaw(implode(', ', $orderBy));
		$seed = rand(1, 9999);
		$posts->inRandomOrder($seed);
		
		// return $posts->take((int)$limit)->get();
		return $posts->paginate((int)$limit);
	}
}
