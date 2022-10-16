<?php

namespace Spork\Development\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spork\Core\Models\FeatureList;

class PublishGitInformationRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public FeatureList $featureList)
    {
    }
}
