<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 *  Website: https://laraclassifier.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - http://codecanyon.net/licenses/standard
 */

namespace App\Http\Controllers\Web\Account;

use Larapen\LaravelMetaTags\Facades\MetaTag;

class SavedSearchesController extends AccountBaseController
{
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index()
	{
		// Call API endpoint
		$endpoint = '/savedSearches';
		$queryParams = [
			'embed' => 'user,country,pictures,postType,category,city',
			'sort'  => 'created_at',
		];
		$queryParams = array_merge(request()->all(), $queryParams);
		$headers = [
			'X-WEB-CONTROLLER' => class_basename(get_class($this)),
		];
		$data = makeApiRequest('get', $endpoint, $queryParams, $headers);
		
		$apiMessage = $this->handleHttpError($data);
		$apiResult = data_get($data, 'result');
		
		// Meta Tags
		MetaTag::set('title', t('my_saved_search'));
		MetaTag::set('description', t('my_saved_search_on', ['appName' => config('settings.app.name')]));
		
		return appView('account.saved-searches.index', compact('apiMessage', 'apiResult'));
	}
	
	public function show($id)
	{
		// Call API endpoint
		$endpoint = '/savedSearches/' . $id;
		$queryParams = [
			'embed' => 'user,country,pictures,postType,category,city',
			'sort'  => 'created_at',
		];
		$queryParams = array_merge(request()->all(), $queryParams);
		$headers = [
			'X-WEB-CONTROLLER' => class_basename(get_class($this)),
		];
		$data = makeApiRequest('get', $endpoint, $queryParams, $headers);
		
		$message = $this->handleHttpError($data);
		$savedSearch = data_get($data, 'result');
		$apiMessagePosts = data_get($savedSearch, 'posts.message');
		$apiResultPosts = data_get($savedSearch, 'posts.result');
		$apiExtraPosts = data_get($savedSearch, 'posts.extra');
		
		// Meta Tags
		MetaTag::set('title', t('my_saved_search'));
		MetaTag::set('description', t('my_saved_search_on', ['appName' => config('settings.app.name')]));
		
		return appView('account.saved-searches.show', compact('savedSearch', 'apiMessagePosts', 'apiResultPosts', 'apiExtraPosts'));
	}
	
	/**
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function destroy($id = null)
	{
		// Get Entries ID
		$ids = [];
		if (request()->filled('entries')) {
			$ids = request()->input('entries');
		} else {
			if ((is_numeric($id) && $id > 0) || (is_string($id) && !empty($id))) {
				$ids[] = $id;
			}
		}
		$ids = implode(',', $ids);
		
		// Get API endpoint
		$endpoint = '/savedSearches/' . $ids;
		
		// Call API endpoint
		$data = makeApiRequest('delete', $endpoint, request()->all());
		
		// Parsing the API response
		$message = !empty(data_get($data, 'message')) ? data_get($data, 'message') : 'Unknown Error.';
		
		// HTTP Error Found
		if (!data_get($data, 'isSuccessful')) {
			flash($message)->error();
			
			return redirect()->back();
		}
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			flash($message)->error();
		}
		
		return redirect()->back();
	}
}
