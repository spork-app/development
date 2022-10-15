<?php

namespace Spork\Development\Events;

use Spork\Core\Models\FeatureList;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PublishGitInformationRequested 
{
    use Dispatchable, SerializesModels;

    public function __construct(public FeatureList $featureList)
    {
    }
}