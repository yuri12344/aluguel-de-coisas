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

namespace App\Observers;

use App\Models\MetaTag;

class MetaTagObserver
{
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param MetaTag $metaTag
	 * @return void
	 */
	public function saved(MetaTag $metaTag)
	{
		// Removing Entries from the Cache
		$this->clearCache($metaTag);
	}
	
	/**
	 * Listen to the Entry deleted event.
	 *
	 * @param MetaTag $metaTag
	 * @return void
	 */
	public function deleted(MetaTag $metaTag)
	{
		// Removing Entries from the Cache
		$this->clearCache($metaTag);
	}
	
	/**
	 * Removing the Entity's Entries from the Cache
	 *
	 * @param $metaTag
	 * @return void
	 */
	private function clearCache($metaTag)
	{
		try {
			cache()->flush();
		} catch (\Exception $e) {}
	}
}
