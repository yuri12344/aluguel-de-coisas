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

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Admin\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;

class LoginController extends Controller
{
	// If not logged in redirect to
	protected mixed $loginPath;
	
	// After you've logged in redirect to
	protected $redirectTo;
	
	// After you've logged out redirect to
	protected $redirectAfterLogout;
	
	// The maximum number of attempts to allow
	protected $maxAttempts = 5;
	
	// The number of minutes to throttle for
	protected $decayMinutes = 15;
	
	/**
	 * LoginController constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->middleware('guest')->except(['except' => 'logout']);
		
		// Set default URLs
		$this->loginPath = admin_uri('login');
		$this->redirectTo = admin_uri();
		$this->redirectAfterLogout = admin_uri('login');
		
		// Get values from Config
		$this->maxAttempts = (int)config('settings.security.login_max_attempts', $this->maxAttempts);
		$this->decayMinutes = (int)config('settings.security.login_decay_minutes', $this->decayMinutes);
	}
	
	// -------------------------------------------------------
	// Laravel overwrites for loading admin views
	// -------------------------------------------------------
	
	/**
	 * Show the application login form.
	 *
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function showLoginForm()
	{
		// Remembering Login
		if (auth()->viaRemember()) {
			return redirect()->intended($this->redirectTo);
		}
		
		return view('admin.auth.login', ['title' => trans('admin.login')]);
	}
	
	/**
	 * @param \App\Http\Requests\LoginRequest $request
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public function login(LoginRequest $request)
	{
		// Call API endpoint
		$endpoint = '/auth/login';
		$data = makeApiRequest('post', $endpoint, $request->all());
		
		// Parsing the API response
		if (
			data_get($data, 'isSuccessful')
			&& data_get($data, 'success')
			&& !empty(data_get($data, 'extra.authToken'))
			&& !empty(data_get($data, 'result.id'))
		) {
			auth()->loginUsingId(data_get($data, 'result.id'));
			session()->put('authToken', data_get($data, 'extra.authToken'));
			
			if (data_get($data, 'extra.isAdmin')) {
				return redirect(admin_uri());
			}
			
			return redirect()->intended($this->redirectTo);
		}
		
		// Check and retrieve previous URL to show the login error on it.
		if (session()->has('url.intended')) {
			$this->loginPath = session()->get('url.intended');
		}
		
		$message = data_get($data, 'message', trans('auth.failed'));
		
		return redirect($this->loginPath)->withErrors(['error' => $message])->withInput();
	}
	
	/**
	 * @param Request $request
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	public function logout(Request $request)
	{
		$userId = auth()->check() ? auth()->user()->id : null;
		
		// Call API endpoint
		$endpoint = '/auth/logout/' . $userId;
		$data = makeApiRequest('get', $endpoint);
		
		// Parsing the API response
		$message = !empty(data_get($data, 'message')) ? data_get($data, 'message') : 'Unknown Error.';
		
		if (data_get($data, 'isSuccessful')) {
			// Get the current Country
			if (session()->has('countryCode')) {
				$countryCode = session('countryCode');
			}
			if (session()->has('allowMeFromReferrer')) {
				$allowMeFromReferrer = session('allowMeFromReferrer');
			}
			
			// Remove all session vars
			auth()->logout();
			$request->session()->flush();
			$request->session()->regenerate();
			
			// Retrieve the current Country
			if (isset($countryCode) && !empty($countryCode)) {
				session()->put('countryCode', $countryCode);
			}
			if (isset($allowMeFromReferrer) && !empty($allowMeFromReferrer)) {
				session()->put('allowMeFromReferrer', $allowMeFromReferrer);
			}
			
			flash($message)->success();
		} else {
			flash($message)->error();
		}
		
		return redirect(property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : '/');
	}
}
