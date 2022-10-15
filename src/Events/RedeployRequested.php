<?php

namespace Spork\Development\Events;

use Spork\Core\Models\FeatureList;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RedeployRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public FeatureList $featureList)
    {
    }
    
    public function broadcastOn()
    {
        return new PrivateChannel('user.'.auth()->id());
    }

    public function broadcastAs()
    {
        return '.redeploy';
    }
}
