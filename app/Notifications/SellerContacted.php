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

use App\Helpers\Files\Storage\StorageDisk;
use App\Helpers\UrlGen;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Post;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class SellerContacted extends Notification implements ShouldQueue
{
	use Queueable;
	
	protected Post $post;
	
	// CAUTION: Conflict between the Model Message $message and the Laravel Mail Message (Mailable) objects.
	// NOTE: No problem with Laravel Notification.
	protected array $messageArray;
	
	public function __construct(Post $post, array $messageArray)
	{
		$this->post = $post;
		$this->messageArray = $messageArray;
	}
	
	public function via($notifiable)
	{
		// Is email can be sent?
		$emailNotificationCanBeSent = (
			!empty($this->post->email)
			&& !empty($this->messageArray['email'])
			&& !isDemoDomain()
		);
		
		// Is SMS can be sent in addition?
		$smsNotificationCanBeSent = (
			config('settings.sms.enable_phone_as_auth_field') == '1'
			&& config('settings.sms.messenger_notifications') == '1'
			&& $this->post->auth_field == 'phone'
			&& !empty($this->post->phone)
			&& !isDemoDomain()
		);
		
		/*
		if ($emailNotificationCanBeSent && $smsNotificationCanBeSent) {
			if (config('settings.sms.driver') == 'twilio') {
				return ['mail', TwilioChannel::class];
			}
			
			return ['mail', 'vonage'];
		}
		*/
		
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
		$postUrl = UrlGen::post($this->post);
		
		$mailMessage = (new MailMessage)
			->replyTo($this->messageArray['email'], $this->messageArray['name'])
			->subject(trans('mail.post_seller_contacted_title', [
				'title'   => $this->post->title,
				'appName' => config('app.name'),
			]))
			->line(nl2br($this->messageArray['body']))
			->line(trans('mail.post_seller_contacted_content_1', [
				'name'  => $this->messageArray['name'],
				'email' => $this->messageArray['email'] ?? '',
				'phone' => $this->messageArray['phone'] ?? '',
			]))
			->line(trans('mail.post_seller_contacted_content_2', [
				'title'   => $this->post->title,
				'postUrl' => $postUrl,
				'appUrl'  => url('/'),
				'appName' => config('app.name'),
			]))
			->line('<br>')
			->line(trans('mail.post_seller_contacted_content_3'))
			->line(trans('mail.post_seller_contacted_content_4'))
			->line(trans('mail.post_seller_contacted_content_5'))
			->line(trans('mail.post_seller_contacted_content_6'))
			->line('<br>')
			->line(trans('mail.post_seller_contacted_content_7'))
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
		
		// Check & get attached file
		if (isset($this->messageArray['filename']) && !empty($this->messageArray['filename'])) {
			if (isset($this->messageArray['fileData']) && !empty($this->messageArray['fileData'])) {
				// Get file's content (from uploaded file)
				$fileData = base64_decode($this->messageArray['fileData']);
				$filename = $this->messageArray['filename'];
			} else {
				// Get file's content (from DB column)
				$disk = StorageDisk::getDisk();
				if ($disk->exists($this->messageArray['filename'])) {
					$fileData = $disk->get($this->messageArray['filename']);
				}
				
				// Get file's short name
				$filename = basename($this->messageArray['filename']);
			}
		}
		
		// Attachment
		if (isset($fileData, $filename) && !empty($fileData) && !empty($filename)) {
			$mailMessage->attachData($fileData, $filename);
		}
		
		return $mailMessage;
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
		return trans('sms.post_seller_contacted_content', [
			'appName' => config('app.name'),
			'title'   => $this->post->title,
			'message' => str(strip_tags($this->messageArray['body']))->limit(20),
		]);
	}
}
