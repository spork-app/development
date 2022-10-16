<?php

namespace Spork\Development\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spork\Core\Models\FeatureList;

class GitInformationRetrievedBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public FeatureList $featureList, public string $branch, public bool $is_dirty)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('user.'.auth()->id());
    }
}
