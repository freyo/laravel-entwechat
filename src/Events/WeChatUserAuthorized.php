<?php

namespace Freyo\LaravelEntWechat\Events;

use Illuminate\Queue\SerializesModels;
use EntWeChat\Support\Collection;

class WeChatUserAuthorized
{
    use SerializesModels;

    public $user;
    public $isNewSession;

    /**
     * Create a new event instance.
     *
     * @param \EntWeChat\Support\Collection $user
     * @param bool                     $isNewSession
     *
     * @return void
     */
    public function __construct(Collection $user, $isNewSession = false)
    {
        $this->user = $user;
        $this->isNewSession = $isNewSession;
    }

    /**
     * Retrieve the authorized user.
     *
     * @return \EntWeChat\Support\Collection
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Check the user session is first created.
     *
     * @return bool
     */
    public function isNewSession()
    {
        return $this->isNewSession;
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
