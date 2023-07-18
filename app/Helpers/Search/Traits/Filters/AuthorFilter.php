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

namespace App\Helpers\Search\Traits\Filters;

trait AuthorFilter
{
	protected function applyAuthorFilter()
	{
		if (!isset($this->posts)) {
			return;
		}
		
		$userId = null;
		$username = null;
		if (request()->filled('userId')) {
			$userId = request()->get('userId');
		}
		if (request()->filled('username')) {
			$username = request()->get('username');
		}
		
		$userId = (is_numeric($userId)) ? $userId : null;
		$username = (is_string($username)) ? $username : null;
		
		if (empty($userId) && empty($username)) {
			return;
		}
		
		if (!empty($userId)) {
			$this->posts->where('user_id', $userId);
		}
		
		if (!empty($username)) {
			$this->posts->whereHas('user', function ($query) use($username) {
				$query->where('username', $username);
			});
		}
	}
}
