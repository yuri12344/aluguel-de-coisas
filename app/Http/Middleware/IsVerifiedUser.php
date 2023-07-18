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

use App\Http\Controllers\Api\Auth\Traits\CheckIfAuthFieldIsVerified;
use App\Helpers\UrlGen;
use App\Http\Controllers\Api\Base\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;

class IsVerifiedUser
{
	use ApiResponseTrait, CheckIfAuthFieldIsVerified;
	
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		$guard = isFromApi() ? 'sanctum' : null;
		
		if (!auth($guard)->check()) {
			return $next($request);
		}
		
		// Is user has verified login?
		$tmpData = $this->userHasVerifiedLogin(auth($guard)->user());
		$isSuccess = array_key_exists('success', $tmpData) && $tmpData['success'];
		
		// User has verified login, then skip error displaying
		if ($isSuccess) {
			return $next($request);
		}
		
		// User has not verified login, then get the right error message
		$errorMessage = $tmpData['message'] ?? 'Unauthorized';
		
		// Display an (unauthorized) error message
		if (isFromApi()) {
			$data = [
				'success' => false,
				'message' => $errorMessage,
				'extra'   => $tmpData['extra'] ?? [],
			];
			
			return $this->apiResponse($data, 403);
		} else {
			if ($request->expectsJson()) {
				abort(403, $errorMessage);
			} else {
				$isForAuthenticate = ($request->url() == UrlGen::login());
				$isForPhoneVerification = str_contains($request->url(), '/verify/phone');
				
				if ($isForPhoneVerification) {
					flash($errorMessage)->warning();
				} else {
					flash($errorMessage)->error();
				}
				
				if (!$isForAuthenticate && !$isForPhoneVerification) {
					return redirect(UrlGen::login());
				}
			}
		}
		
		return $next($request);
	}
}
