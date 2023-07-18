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

namespace App\Http\Controllers\Web\Account;

use App\Helpers\UrlGen;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class PostsController extends AccountBaseController
{
	/**
	 * @param $pagePath
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
	 */
	public function getPage($pagePath)
	{
		switch ($pagePath) {
			case 'list':
				return $this->index();
			case 'archived':
				return $this->archivedPosts();
			case 'pending-approval':
				return $this->pendingApprovalPosts();
			case 'favourite':
				return $this->savedPosts();
			default:
				abort(404);
		}
	}
	
	/**
	 * @param $postId
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function index($postId = null)
	{
		// If "offline" button is clicked
		if (
			is_numeric($postId)
			&& $postId > 0
			&& str_contains(url()->current(), $postId . '/offline')
		) {
			// Call API endpoint
			$endpoint = '/posts/' . $postId . '/offline';
			$data = makeApiRequest('put', $endpoint);
			
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
		
		// Call API endpoint
		$endpoint = '/posts';
		$queryParams = [
			'belongLoggedUser' => 1,
			'embed'            => 'category,postType,city,latestPayment,pictures',
			'sort'             => 'created_at',
		];
		$queryParams = array_merge(request()->all(), $queryParams);
		$data = makeApiRequest('get', $endpoint, $queryParams);
		
		$apiMessage = $this->handleHttpError($data);
		$apiResult = data_get($data, 'result');
		
		// Meta Tags
		MetaTag::set('title', t('my_listings'));
		MetaTag::set('description', t('my_listings_on', ['appName' => config('settings.app.name')]));
		
		return appView('account.posts', compact('apiResult'));
	}
	
	/**
	 * @param $postId
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	public function archivedPosts($postId = null)
	{
		// If "repost" button is clicked
		if (str_contains(url()->current(), $postId . '/repost')) {
			// Call API endpoint
			$endpoint = '/posts/' . $postId . '/repost';
			$data = makeApiRequest('put', $endpoint);
			
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
			
			// Get User Resource
			$post = data_get($data, 'result');
			$postUrl = UrlGen::post($post);
			
			return redirect($postUrl);
		}
		
		// Call API endpoint
		$endpoint = '/posts';
		$queryParams = [
			'belongLoggedUser' => 1,
			'archived'         => 1,
			'embed'            => 'category,postType,city,latestPayment,pictures',
			'sort'             => 'created_at',
		];
		$queryParams = array_merge(request()->all(), $queryParams);
		$data = makeApiRequest('get', $endpoint, $queryParams);
		
		$apiMessage = $this->handleHttpError($data);
		$apiResult = data_get($data, 'result');
		
		// Meta Tags
		MetaTag::set('title', t('my_archived_listings'));
		MetaTag::set('description', t('my_archived_listings_on', ['appName' => config('settings.app.name')]));
		
		return appView('account.posts', compact('apiResult'));
	}
	
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function pendingApprovalPosts()
	{
		// Call API endpoint
		$endpoint = '/posts';
		$queryParams = [
			'belongLoggedUser' => 1,
			'pendingApproval'  => 1,
			'embed'            => 'category,postType,city,latestPayment,pictures',
			'sort'             => 'created_at',
		];
		$queryParams = array_merge(request()->all(), $queryParams);
		$data = makeApiRequest('get', $endpoint, $queryParams);
		
		$apiMessage = $this->handleHttpError($data);
		$apiResult = data_get($data, 'result');
		
		// Meta Tags
		MetaTag::set('title', t('my_pending_approval_listings'));
		MetaTag::set('description', t('my_pending_approval_listings_on', ['appName' => config('settings.app.name')]));
		
		return appView('account.posts', compact('apiResult'));
	}
	
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function savedPosts()
	{
		// Call API endpoint
		$endpoint = '/savedPosts';
		$queryParams = [
			'embed' => 'post,city,pictures,user',
			'sort'  => 'created_at',
		];
		$queryParams = array_merge(request()->all(), $queryParams);
		$data = makeApiRequest('get', $endpoint, $queryParams);
		
		$apiMessage = $this->handleHttpError($data);
		$apiResult = data_get($data, 'result');
		
		// Transform the API result
		$apiResult = collect($apiResult)->mapWithKeys(function ($item, $key) {
			if ($key == 'data' && is_array($item)) {
				$newItem = [];
				foreach ($item as $idx => $savedPost) {
					$newItem[$savedPost['id']] = $savedPost['post'];
				}
				$item = $newItem;
			}
			
			return [$key => $item];
		})->toArray();
		
		// Meta Tags
		MetaTag::set('title', t('my_favourite_listings'));
		MetaTag::set('description', t('my_favourite_listings_on', ['appName' => config('settings.app.name')]));
		
		return appView('account.posts', compact('apiResult'));
	}
	
	/**
	 * @param $pagePath
	 * @param $id
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	public function destroy($pagePath, $id = null)
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
		$endpoint = '/posts/' . $ids;
		$otherEndpoints = [
			'favourite' => '/savedPosts/' . $ids,
		];
		$endpoint = $otherEndpoints[$pagePath] ?? $endpoint;
		
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
		
		return redirect('account/posts/' . $pagePath);
	}
}
