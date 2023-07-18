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

namespace App\Http\Controllers\Api\HomeSection;

use App\Helpers\UrlGen;
use App\Http\Resources\EntityCollection;
use App\Models\Advertising;
use App\Models\Category;
use App\Models\City;
use App\Models\Post;
use App\Models\User;

trait SectionDataTrait
{
	private array $embed = ['user', 'category', 'parent', 'postType', 'city', 'savedByLoggedUser', 'pictures', 'latestPayment', 'package'];
	
	/**
	 * Get search form (Always in Top)
	 *
	 * @param array|null $value
	 * @return array
	 */
	protected function getSearchForm(?array $value = []): array
	{
		return [];
	}
	
	/**
	 * Get locations & SVG map
	 *
	 * @param array|null $value
	 * @return array
	 */
	protected function getLocations(?array $value = []): array
	{
		$data = [];
		
		$cacheExpiration = (int)($value['cache_expiration'] ?? 0);
		$maxItems = (int)($value['max_items'] ?? 14);
		
		// Get cities
		if (config('settings.list.count_cities_listings')) {
			$cacheId = config('country.code') . 'home.getLocations.cities.withCountPosts';
			$cities = cache()->remember($cacheId, $cacheExpiration, function () use ($maxItems) {
				return City::currentCountry()->withCount('posts')->take($maxItems)->orderByDesc('population')->orderBy('name')->get();
			});
		} else {
			$cacheId = config('country.code') . 'home.getLocations.cities';
			$cities = cache()->remember($cacheId, $cacheExpiration, function () use ($maxItems) {
				return City::currentCountry()->take($maxItems)->orderByDesc('population')->orderBy('name')->get();
			});
		}
		$cities = $cities->toArray();
		
		// Add "More Cities" link
		$adminType = config('country.admin_type', 0);
		$adminCodeCol = 'subadmin' . $adminType . '_code';
		$moreCities = [
			'id'          => 0,
			'name'        => t('More cities') . ' &raquo;',
			$adminCodeCol => 0,
		];
		$cities = collect($cities)->push($moreCities);
		
		// Get cities number of columns
		$numberOfCols = 4;
		if (data_get($value, 'show_map') == '1') {
			if (file_exists(config('larapen.core.maps.path') . strtolower(config('country.code')) . '.svg')) {
				$numberOfCols = !empty(data_get($value, 'items_cols')) ? (int)data_get($value, 'items_cols') : 3;
			}
		}
		
		// Chunk
		$maxRowsPerCol = round($cities->count() / $numberOfCols, 0); // PHP_ROUND_HALF_EVEN
		$maxRowsPerCol = ($maxRowsPerCol > 0) ? $maxRowsPerCol : 1;  // Fix array_chunk with 0
		$cities = $cities->chunk($maxRowsPerCol);
		
		$data['cities'] = $cities->toArray();
		
		return $data;
	}
	
	/**
	 * Get sponsored listings
	 *
	 * @param array|null $value
	 * @return array
	 */
	protected function getSponsoredPosts(?array $value = []): array
	{
		$data = [];
		
		$type = 'sponsored';
		$cacheExpiration = (int)($value['cache_expiration'] ?? 0);
		$maxItems = (int)($value['max_items'] ?? 20);
		$orderBy = $value['order_by'] ?? 'random';
		
		// Get featured listings
		$cacheId = config('country.code') . '.home.getPosts.' . $type;
		$posts = cache()->remember($cacheId, $cacheExpiration, function () use ($maxItems, $type, $orderBy) {
			return Post::getLatestOrSponsored($maxItems, $type, $orderBy);
		});
		
		$sponsored = null;
		if ($posts->count() > 0) {
			$savedQueries = request()->all();
			request()->query->add(['embed' => implode(',', $this->embed)]);
			
			$postsCollection = new EntityCollection('PostController', $posts);
			$postsResult = $postsCollection->toResponse(request())->getData();
			
			request()->replace($savedQueries);
			
			$sponsored = [
				'title'      => t('Home - Sponsored Listings'),
				'link'       => UrlGen::searchWithoutQuery(),
				'posts'      => $postsResult->data ?? [],
				'totalPosts' => $postsResult->meta->total ?? 0,
			];
		}
		
		$data['featured'] = $sponsored;
		
		return $data;
	}
	
	/**
	 * Get latest listings
	 *
	 * @param array|null $value
	 * @return array
	 */
	protected function getLatestPosts(?array $value = []): array
	{
		$data = [];
		
		$type = 'latest';
		$cacheExpiration = (int)($value['cache_expiration'] ?? 0);
		$maxItems = (int)($value['max_items'] ?? 12);
		$orderBy = $value['order_by'] ?? 'date';
		
		// Get latest listings
		$cacheId = config('country.code') . '.home.getPosts.' . $type;
		$posts = cache()->remember($cacheId, $cacheExpiration, function () use ($maxItems, $type, $orderBy) {
			return Post::getLatestOrSponsored($maxItems, $type, $orderBy);
		});
		
		$latest = null;
		if (!empty($posts)) {
			$savedQueries = request()->all();
			request()->query->add(['embed' => implode(',', $this->embed)]);
			
			$postsCollection = new EntityCollection('PostController', $posts);
			$postsResult = $postsCollection->toResponse(request())->getData();
			
			request()->replace($savedQueries);
			
			$latest = [
				'title'      => ($orderBy == 'random') ? t('Home - Random Listings') : t('Home - Latest Listings'),
				'link'       => UrlGen::searchWithoutQuery(),
				'posts'      => $postsResult->data ?? [],
				'totalPosts' => $postsResult->meta->total ?? 0,
			];
		}
		
		$data['latest'] = $latest;
		
		return $data;
	}
	
	/**
	 * Get list of categories
	 *
	 * @param array|null $value
	 * @return array
	 */
	protected function getCategories(?array $value = []): array
	{
		$data = [];
		
		$cacheExpiration = (int)($value['cache_expiration'] ?? 0);
		$maxItems = (int)($value['max_items'] ?? null);
		$catDisplayType = $value['cat_display_type'] ?? 'c_bigIcon_list';
		$numberOfCols = 3;
		
		$cacheId = 'categories.parents.' . config('app.locale') . '.' . $catDisplayType . '.take.' . $maxItems;
		
		if (in_array($catDisplayType, ['cc_normal_list', 'cc_normal_list_s'])) {
			
			$categories = cache()->remember($cacheId, $cacheExpiration, function () {
				return Category::query()->orderBy('lft')->get();
			});
			$categories = collect($categories)->keyBy('id');
			$categories = $subCategories = $categories->groupBy('parent_id');
			
			if ($categories->has(null)) {
				if (!empty($maxItems)) {
					$categories = $categories->get(null)->take($maxItems);
				} else {
					$categories = $categories->get(null);
				}
				$subCategories = $subCategories->forget(null);
				
				$maxRowsPerCol = round($categories->count() / $numberOfCols, 0, PHP_ROUND_HALF_EVEN);
				$maxRowsPerCol = ($maxRowsPerCol > 0) ? $maxRowsPerCol : 1;
				$categories = $categories->chunk($maxRowsPerCol);
			} else {
				$categories = collect();
				$subCategories = collect();
			}
			
			$data['categories'] = $categories;
			$data['subCategories'] = $subCategories;
			
		} else {
			
			$categories = cache()->remember($cacheId, $cacheExpiration, function () use ($maxItems) {
				$categories = Category::query()->root();
				if (!empty($maxItems)) {
					$categories = $categories->take($maxItems);
				}
				
				return $categories->orderBy('lft')->get();
			});
			
			if (in_array($catDisplayType, ['c_picture_list', 'c_bigIcon_list'])) {
				$categories = collect($categories)->keyBy('id');
			} else {
				$maxRowsPerCol = ceil($categories->count() / $numberOfCols);
				$maxRowsPerCol = ($maxRowsPerCol > 0) ? $maxRowsPerCol : 1; // Fix array_chunk with 0
				$categories = $categories->chunk($maxRowsPerCol);
			}
			
			$data['categories'] = $categories;
			
		}
		
		// Count Posts by category (if the option is enabled)
		$countPostsPerCat = [];
		if (config('settings.list.count_categories_listings')) {
			$cacheId = config('country.code') . '.count.posts.per.cat.' . config('app.locale');
			$countPostsPerCat = cache()->remember($cacheId, $cacheExpiration, function () {
				return Category::countPostsPerCategory();
			});
		}
		
		$data['countPostsPerCat'] = $countPostsPerCat;
		
		return $data;
	}
	
	/**
	 * Get mini stats data
	 *
	 * @param array|null $value
	 * @return array
	 */
	protected function getStats(?array $value = []): array
	{
		$cacheExpiration = (int)($value['cache_expiration'] ?? 0);
		
		// Count Posts
		$countPosts = ($value['custom_counts_listings'] ?? 0);
		if (empty($countPosts)) {
			$cacheId = config('country.code') . '.count.posts';
			$countPosts = cache()->remember($cacheId, $cacheExpiration, function () {
				return Post::currentCountry()->unarchived()->count();
			});
		}
		
		// Count Users
		$countUsers = ($value['custom_counts_users'] ?? 0);
		if (empty($countUsers)) {
			$cacheId = 'count.users';
			$countUsers = cache()->remember($cacheId, $cacheExpiration, function () {
				return User::query()->count();
			});
		}
		
		// Count Locations (Cities)
		$countLocations = ($value['custom_counts_locations'] ?? 0);
		if (empty($countLocations)) {
			$cacheId = config('country.code') . '.count.cities';
			$countLocations = cache()->remember($cacheId, $cacheExpiration, function () {
				return City::currentCountry()->count();
			});
		}
		
		return [
			'count' => [
				'posts'     => $countPosts,
				'users'     => $countUsers,
				'locations' => $countLocations,
			],
		];
	}
	
	/**
	 * Get the text area data
	 *
	 * @param array|null $value
	 * @return array
	 */
	protected function getTextArea(?array $value = []): array
	{
		return [];
	}
	
	/**
	 * @param array|null $value
	 * @return array
	 */
	protected function getTopAdvertising(?array $value = []): array
	{
		$cacheId = 'advertising.top';
		$topAdvertising = cache()->remember($cacheId, $this->cacheExpiration, function () {
			return Advertising::query()
				->where('integration', 'unitSlot')
				->where('slug', 'top')
				->first();
		});
		
		return [
			'topAdvertising' => $topAdvertising,
		];
	}
	
	/**
	 * @param array|null $value
	 * @return array
	 */
	protected function getBottomAdvertising(?array $value = []): array
	{
		$cacheId = 'advertising.bottom';
		$bottomAdvertising = cache()->remember($cacheId, $this->cacheExpiration, function () {
			return Advertising::query()
				->where('integration', 'unitSlot')
				->where('slug', 'bottom')
				->first();
		});
		
		return [
			'bottomAdvertising' => $bottomAdvertising,
		];
	}
}
