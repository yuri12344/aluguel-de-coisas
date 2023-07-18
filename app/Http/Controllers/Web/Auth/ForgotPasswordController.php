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

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Web\Auth\Traits\VerificationTrait;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Controllers\Web\FrontController;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class ForgotPasswordController extends FrontController
{
	use VerificationTrait;
	
	protected $redirectTo = '/account';
	
	/**
	 * PasswordController constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->middleware('guest');
	}
	
	// -------------------------------------------------------
	// Laravel overwrites for loading LaraClassifier views
	// -------------------------------------------------------
	
	/**
	 * Display the form to request a password reset link.
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function showLinkRequestForm()
	{
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('password');
		MetaTag::set('title', $title);
		MetaTag::set('description', strip_tags($description));
		MetaTag::set('keywords', $keywords);
		
		return appView('auth.passwords.email');
	}
	
	/**
	 * Send a reset link to the given user.
	 *
	 * @param ForgotPasswordRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function sendResetLink(ForgotPasswordRequest $request)
	{
		// Call API endpoint
		$endpoint = '/auth/password/email';
		$data = makeApiRequest('post', $endpoint, $request->all());
		
		// Parsing the API response
		$message = !empty(data_get($data, 'message')) ? data_get($data, 'message') : 'Unknown Error.';
		
		// Error Found
		if (!data_get($data, 'isSuccessful') || !data_get($data, 'success')) {
			redirect()->back()
				->withInput($request->only('email'))
				->withErrors(['email' => $message]);
		}
		
		// phone
		if (data_get($data, 'extra.codeSentTo') == 'phone') {
			// Save the password reset link (in session)
			$resetPwdUrl = url('password/reset/' . data_get($data, 'extra.code'));
			session()->put('passwordNextUrl', $resetPwdUrl);
			
			// Go to the code (received by SMS) verification page
			return redirect('password/verify/phone/');
		}
		
		// email
		return redirect()->back()->with(['status' => $message]);
	}
}
