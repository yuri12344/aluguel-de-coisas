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

namespace App\Notifications;

use App\Helpers\Date;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Post;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class PostWilBeDeleted extends Notification implements ShouldQueue
{
	use Queueable;
	
	protected $post;
	protected $days;
	
	public function __construct(Post $post, $days)
	{
		$this->post = $post;
		$this->days = $days;
	}
	
	public function via($notifiable)
	{
		if ($this->days <= 0) {
			return [];
		}
		
		// Is email can be sent?
		$emailNotificationCanBeSent = (config('settings.mail.confirmation') == '1' && !empty($this->post->email));
		
		// Is SMS can be sent in addition?
		$smsNotificationCanBeSent = (
			config('settings.sms.enable_phone_as_auth_field') == '1'
			&& config('settings.sms.confirmation') == '1'
			&& $this->post->auth_field == 'phone'
			&& !empty($this->post->phone)
			&& !isDemoDomain()
		);
		
		if ($emailNotificationCanBeSent) {
			return ['mail'];
		}
		
		if ($smsNotificationCanBeSent) {
			if (config('settings.sms.driver') == 'twilio') {
				return [TwilioChannel::class];
			}
			
			return ['vonage'];
		}
		
		return [];
	}
	
	public function toMail($notifiable)
	{
		$path = 'account/posts/archived/' . $this->post->id . '/repost';
		$repostUrl = (config('plugins.domainmapping.installed'))
			? dmUrl($this->post->country_code, $path)
			: url($path);
		
		return (new MailMessage)
			->subject(trans('mail.post_will_be_deleted_title', [
				'title' => $this->post->title,
				'days'  => $this->days,
			]))
			->greeting(trans('mail.post_will_be_deleted_content_1'))
			->line(trans('mail.post_will_be_deleted_content_2', [
				'title'   => $this->post->title,
				'days'    => $this->days,
				'appName' => config('app.name'),
			]))
			->line(trans('mail.post_will_be_deleted_content_3', ['repostUrl' => $repostUrl]))
			->line(trans('mail.post_will_be_deleted_content_4', [
				'dateDel' => Date::format($this->post->archived_at->addDays($this->days)),
			]))
			->line(trans('mail.post_will_be_deleted_content_5'))
			->line('<br>')
			->line(trans('mail.post_will_be_deleted_content_6'))
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
	}
	
	public function toVonage($notifiable)
	{
		return (new VonageMessage())->content($this->smsMessage())->unicode();
	}
	
	public function toTwilio($notifiable)
	{
		return (new TwilioSmsMessage())->content($this->smsMessage());
	}
	
	protected function smsMessage()
	{
		return trans('sms.post_will_be_deleted_content', ['appName' => config('app.name'), 'title' => $this->post->title, 'days' => $this->days]);
	}
}
