<?php
/*
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

use App\Http\Resources\EntityCollection;
use App\Http\Resources\LanguageResource;
use App\Models\Language;

/**
 * @group Languages
 */
class LanguageController extends BaseController
{
	/**
	 * List languages
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): \Illuminate\Http\JsonResponse
	{
		$languages = Language::query()->get();
		
		$resourceCollection = new EntityCollection(class_basename($this), $languages);
		
		$message = ($languages->count() <= 0) ? t('no_languages_found') : null;
		
		return $this->respondWithCollection($resourceCollection, $message);
	}
	
	/**
	 * Get language
	 *
	 * @urlParam code string required The language's code. Example: en
	 *
	 * @param $code
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($code): \Illuminate\Http\JsonResponse
	{
		$language = Language::query()->where('abbr', $code);
		
		$language = $language->first();
		
		abort_if(empty($language), 404, t('language_not_found'));
		
		$resource = new LanguageResource($language);
		
		return $this->respondWithResource($resource);
	}
}
