<?php

namespace Spork\Development\Listeners;

use App\Events\FeatureCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;

class CopyTemplateIfApplicableListener //implements ShouldQueue
{
    public function __invoke($event) 
    {
        $feature = $event->featureList;

        if ($feature->feature !== 'development') {
            info("feature isnt development", compact('feature'));
            return;
        }

        $path = $feature->settings['path'];

        if (file_exists($path)) {
            info('Template already exists at ' . $path);
            return;
        }

        $template = $feature->settings['template'];
        info("Building from template", compact('template'));

        $this->fetchTemplate($template, $path);
        $this->replaceTemplatePlaceholders($event->featureList, $template, $path);

        if ($feature->settings['use_git']) {
            $status = (new Process(['git', 'init'], $path))->run();
            info('Git init status', compact('status'));
        }
    }

    protected function fetchTemplate($template, $destinationPath) {
        throw_unless(str_ends_with($template['src'], '.zip'), ValidationException::withMessages([
            'src' => ['The src must end with .zip'],
        ]));

        if (str_starts_with($template['src'], 'http')) {
            $this->fetchRemoteTemplate($template, $destinationPath);
        } else {
            $this->fetchLocalTemplate($template, $destinationPath);
        }
    }

    protected function fetchRemoteTemplate($template, $destinationPath)
    {
        $destination = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789').'.zip';
        info('Fetching template '. $template['src']);
        file_put_contents($absolutePath = storage_path($destination), file_get_contents($template['src']));
        info('File saved '. $absolutePath);

        $this->unzip($absolutePath, $destinationPath);
    }

    protected function fetchLocalTemplate($template, $destinationPath)
    {
        $destination = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789').'.zip';
        copy($template['src'], $absolutePath = storage_path($destination));

        $this->unzip($absolutePath, $destinationPath);
    }

    protected function unzip($path, $unzipDir)
    {
        $zip = new \ZipArchive;
        $res = $zip->open($path);
        if ($res !== TRUE) {
            throw new \Exception('Unable to unzip template.');
        } 

        $errors = [];
        for ($i = 0; $i < $zip -> numFiles; $i++) {
            $RETVAL = false;
    
            $filename = $zip->getNameIndex($i);
    
            $RETVAL = $zip->extractTo($unzipDir, $filename);
    
            if (!$RETVAL) {
                $errors[] = "$filename: $RETVAL";
            }
        }
        $close = $zip->close();

        info(sprintf('Unzipped %s to %s', $path, $unzipDir), [
            'errors' => $errors,
        ]);
        if (!file_exists($unzipDir)) {
            throw new \Exception('Unable to unzip ' . $path);
        }

        $dirs = scandir($unzipDir);

        if (count($dirs) == 3) {
            $destination = array_slice($dirs, 2)[0];

            rename($unzipDir . '/' . $destination, $unzipDir.'/../temp-dir');
            rename($unzipDir . '/../' . 'temp-dir', $unzipDir);
        }

        unlink($path);
    }

    protected function replaceTemplatePlaceholders($feature, $template, $path)
    {
        $filesystem = new Filesystem;

        if (!file_exists($path.'/spork.json')) {
            info("This repo doesnt have a spork.json file");
            return;
        }

        $sporkFile = json_decode(file_get_contents($path.'/spork.json'), true);
        unlink($path.'/spork.json');
        $values = [
            "__PACKAGE_NAME__" => 'spork/'.Str::slug($feature->name),
            "__DESCRIPTION__" => "A Spork Plugin",
            "__NAMESPACE__" => 'Spork\\'.Str::studly($feature->name),
            "__NAMESPACE_ESCAPED__" => 'Spork\\\\'.Str::studly($feature->name),
            "__FEATURE_NAME__" =>  $feature->name,
            "__CAPITAL_FEATURE_NAME__" => Str::studly($feature->name),
            '__FEATURE_NAME_SLUG__' => Str::slug($feature->name),
        ];
        
        $allFiles = $filesystem->allFiles($path);

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($allFiles as $file) {
            $contents = file_get_contents($file->getPathname());

            foreach ($sporkFile as $key => $description) {
                if (empty($values[$key])) {
                    continue;
                }
                
                $replaceValue = ($values[$key] ?? '');

                $contents = str_replace($key, $replaceValue, $contents);
            }

            file_put_contents($file->getPathname(), $contents);
        }
        info('placeholders replaced');
    }
}

  