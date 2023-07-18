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

trait TagFilter
{
	protected function applyTagFilter()
	{
		if (!isset($this->posts)) {
			return;
		}
		
		$tag = null;
		if (request()->filled('tag')) {
			$tag = request()->get('tag');
		}
		
		$tag = (is_string($tag)) ? $tag : null;
		
		if (empty(trim($tag))) {
			return;
		}
		
		$tag = rawurldecode($tag);
		$tag = mb_strtolower($tag);
		
		$this->posts->whereRaw('FIND_IN_SET(?, LOWER(tags)) > 0', [$tag]);
	}
}
