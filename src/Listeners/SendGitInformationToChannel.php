<?php

namespace Spork\Development\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spork\Development\Events\GitInformationRetrievedBroadcast;
use Spork\Development\Events\PublishGitInformationRequested;
use Symfony\Component\Process\Process;

class SendGitInformationToChannel implements ShouldQueue
{
    public function handle(PublishGitInformationRequested $event)
    {
        $branchProcess = new Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $event->featureList->settings['path']);
        $logProcess = new Process(['git', 'status', '--porcelain'], $event->featureList->settings['path']);

        $branchProcess->run();
        $logProcess->run();

        broadcast(new GitInformationRetrievedBroadcast(
            branch: $branchProcess->getOutput(),
            is_dirty: empty($logProcess->getOutput()),
            featureList: $event->featureList
        ));
    }
}