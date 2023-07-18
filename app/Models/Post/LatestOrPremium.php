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

use App\Models\Package;
use App\Models\Payment;
use App\Models\Post;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

trait LatestOrPremium
{
	/**
	 * Get Latest or Sponsored Posts
	 *
	 * @param int|null $limit
	 * @param string|null $type
	 * @param $defaultOrder
	 * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
	 */
	public static function getLatestOrSponsored(?int $limit = 20, ?string $type = 'latest', $defaultOrder = null)
	{
		$posts = Post::query();
		
		$tablesPrefix = DB::getTablePrefix();
		$postsTable = (new Post())->getTable();
		$paymentsTable = (new Payment())->getTable();
		$packagesTable = (new Package())->getTable();
		
		// Select fields
		$select = [
			$postsTable . '.id',
			'country_code',
			'category_id',
			'post_type_id',
			'title',
			$postsTable . '.price',
			'city_id',
			'featured',
			$postsTable . '.created_at',
			'email_verified_at',
			'phone_verified_at',
			'reviewed_at',
			'tPackage.lft',
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
		
		// GroupBy fields
		$groupBy = [
			$postsTable . '.id',
		];
		
		$orderBy = [
			$tablesPrefix . $postsTable . '.created_at DESC',
		];
		
		// If the MySQL strict mode is activated, ...
		// Append all the non-calculated fields available in the 'SELECT' in 'GROUP BY' to prevent error related to 'only_full_group_by'
		if (env('DB_MODE_STRICT')) {
			$groupBy = $select;
		}
		
		if (!empty($select)) {
			foreach ($select as $column) {
				$posts->addSelect($column);
			}
		}
		
		// Price conversion (For the Currency Exchange plugin)
		$posts->addSelect(DB::raw('(' . DB::getTablePrefix() . $postsTable . '.price * ?) AS calculatedPrice'));
		$posts->addBinding(config('selectedCurrency.rate', 1), 'select');
		
		// Default Filters
		$posts->currentCountry()->verified()->unarchived();
		if (config('settings.single.listings_review_activation')) {
			$posts->reviewed();
		}
		
		// Relations
		$posts->with('postType');
		$posts->with('category', fn($query) => $query->with('parent'))->has('category');
		$posts->with('latestPayment', fn($query) => $query->with('package'));
		$posts->with('savedByLoggedUser');
		$posts->with('user');
		$posts->with('user.permissions');
		
		// latestPayment (Can be used in orderBy)
		$tmpLatestPayment = DB::table($paymentsTable, 'lp')
			->select(DB::raw('MAX(' . $tablesPrefix . 'lp.id) as lpId'), 'lp.post_id')
			->where('lp.active', 1)
			->groupBy('lp.post_id');
		
		if ($type == 'sponsored') {
			$posts->joinSub($tmpLatestPayment, 'tmpLp', function ($join) use ($postsTable) {
				$join->on('tmpLp.post_id', '=', $postsTable . '.id')->where('featured', 1);
			});
			$posts->join($paymentsTable . ' as latestPayment', 'latestPayment.id', '=', 'tmpLp.lpId');
			$posts->join($packagesTable . ' as tPackage', 'tPackage.id', '=', 'latestPayment.package_id');
			
			// Priority to the Premium Ads
			// Push the Package Position order onto the beginning of an array
			$orderBy = Arr::prepend($orderBy, $tablesPrefix . 'tPackage.lft DESC');
		} else {
			$posts->leftJoinSub($tmpLatestPayment, 'tmpLp', function ($join) use ($postsTable) {
				$join->on('tmpLp.post_id', '=', $postsTable . '.id')->where('featured', 1);
			});
			$posts->leftJoin($paymentsTable . ' as latestPayment', 'latestPayment.id', '=', 'tmpLp.lpId');
			$posts->leftJoin($packagesTable . ' as tPackage', 'tPackage.id', '=', 'latestPayment.package_id');
		}
		$posts->with('city')->has('city');
		$posts->with('pictures');
		
		// Set GROUP BY
		if (!empty($groupBy)) {
			// Get valid columns name
			$groupBy = collect($groupBy)->map(function ($value, $key) use ($tablesPrefix) {
				if (str_contains($value, '.')) {
					$value = $tablesPrefix . $value;
				}
				
				return $value;
			})->toArray();
			
			$posts->groupByRaw(implode(', ', $groupBy));
		}
		
		// Set ORDER BY
		if ($defaultOrder == 'random') {
			$seed = rand(1, 9999);
			$posts->inRandomOrder($seed);
		} else {
			if (is_array($orderBy) && count($orderBy) > 0) {
				$posts->orderByRaw(implode(', ', $orderBy));
			}
		}
		
		// return $posts->take((int)$limit)->get();
		return $posts->paginate((int)$limit);
	}
}
