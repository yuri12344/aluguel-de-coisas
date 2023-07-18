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

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Prologue\Alerts\Facades\Alert;

class DemoRestriction
{
	/**
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		if (!isDemoDomain()) {
			return $next($request);
		}
		
		if (!$this->isRestricted()) {
			return $next($request);
		}
		
		$message = t('demo_mode_message');
		
		if (isFromApi()) {
			
			$result = [
				'success' => false,
				'message' => $message,
				'result'  => null,
			];
			
			return response()->json($result, 401, [], JSON_UNESCAPED_UNICODE);
			
		} else {
			if ($request->ajax()) {
				$result = [
					'success' => false,
					'msg'     => $message,
				];
				
				return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE);
			} else {
				if (isAdminPanel()) {
					Alert::info($message)->flash();
				} else {
					flash($message)->info();
				}
				
				return redirect()->back();
			}
		}
	}
	
	/**
	 * @return bool
	 */
	private function isRestricted(): bool
	{
		$isRestricted = false;
		
		$frontRoutesRestricted = $this->frontRoutesRestricted();
		foreach ($frontRoutesRestricted as $route) {
			if (str_contains(Route::currentRouteAction(), $route)) {
				$isRestricted = true;
				break;
			}
		}
		
		if (auth()->check()) {
			if (
				auth()->user()->can(Permission::getStaffPermissions())
				&& md5(auth()->user()->id) == 'c4ca4238a0b923820dcc509a6f75849b'
			) {
				return false;
			}
			
			$adminRoutesRestricted = $this->adminRoutesRestricted();
			foreach ($adminRoutesRestricted as $route) {
				if (
					(
						str_starts_with($route, '@')
						&& str_contains(Route::currentRouteAction(), 'Admin\\')
						&& str_contains(Route::currentRouteAction(), $route)
					)
					|| (
						!str_starts_with($route, '@')
						&& str_contains(Route::currentRouteAction(), $route)
					)
				) {
					$isRestricted = true;
					break;
				}
			}
			
			if (in_array(auth()->user()->id, [2, 3])) {
				$demoUsersRoutesRestricted = $this->demoUsersRoutesRestricted();
				foreach ($demoUsersRoutesRestricted as $route) {
					if (str_contains(Route::currentRouteAction(), $route)) {
						$isRestricted = true;
						break;
					}
				}
			}
		}
		
		return $isRestricted;
	}
	
	/**
	 * @return string[]
	 */
	private function frontRoutesRestricted(): array
	{
		return [
			// api
			'Api\ContactController@sendForm',
			'Api\ContactController@sendReport',
			//'Api\ThreadController@store',
			
			// web
			'Web\PageController@contactPost',
			'Web\Post\ReportController@contactPost',
			//'Web\Account\MessagesController@store',
		];
	}
	
	/**
	 * @return string[]
	 */
	private function adminRoutesRestricted(): array
	{
		return [
			// admin
			'@store',
			'@update',
			'@destroy',
			'@saveReorder',
			'@reSendEmailVerification',
			'@reSendPhoneVerification',
			'Admin\RoleController@store',
			'Admin\RoleController@update',
			'Admin\RoleController@destroy',
			'Admin\PermissionController@store',
			'Admin\PermissionController@update',
			'Admin\PermissionController@destroy',
			'Admin\ActionController',
			'Admin\BackupController@create',
			'Admin\BackupController@download',
			'Admin\BackupController@delete',
			'Admin\BlacklistController@banUserByEmail',
			'Admin\HomeSectionController@reset',
			'Admin\InlineRequestController',
			'Admin\LanguageController@syncFilesLines',
			'Admin\LanguageController@update',
			'Admin\LanguageController@updateTexts',
			'Admin\PluginController@install',
			'Admin\PluginController@uninstall',
			'Admin\PluginController@delete',
			
			// impersonate
			'Larapen\Impersonate\Controllers\ImpersonateController',
			
			// plugins:domainmapping
			'domainmapping\app\Http\Controllers\Admin\DomainController@createBulkCountriesSubDomain',
			'domainmapping\app\Http\Controllers\Admin\DomainHomeSectionController@generate',
			'domainmapping\app\Http\Controllers\Admin\DomainHomeSectionController@reset',
			'domainmapping\app\Http\Controllers\Admin\DomainMetaTagController@generate',
			'domainmapping\app\Http\Controllers\Admin\DomainMetaTagController@reset',
			'domainmapping\app\Http\Controllers\Admin\DomainSettingController@generate',
			'domainmapping\app\Http\Controllers\Admin\DomainSettingController@reset',
		];
	}
	
	/**
	 * @return string[]
	 */
	private function demoUsersRoutesRestricted(): array
	{
		return [
			// api
			'Api\UserController@update',
			'Api\UserController@destroy',
			'Api\PostController@destroy',
			
			// web
			'Web\Account\EditController@updateDetails',
			'Web\Account\EditController@updatePhoto',
			'Web\Account\CloseController@submit',
			'Web\Account\PostsController@destroy',
		];
	}
}
