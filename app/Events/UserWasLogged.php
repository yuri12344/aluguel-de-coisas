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

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class UserWasLogged extends Event
{
    use SerializesModels;
    
    public $user;
    
    /**
     * Create a new event instance.
	 *
	 * @param User $user
	 */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
    
    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
