<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Spork\Core\Models\FeatureList;
use Spork\Development\Events\FileSavedEvent;
use Spork\Development\Events\RedeployRequested;

Route::post('files/{featureList:id}', function (FeatureList $featureList) {
    abort_unless(request()->has('path'), 404, 'No file found');

    abort_unless(request()->user()->tokenCan('create-file'), 403, 'You do not have permission to upload files');

    $path = request()->get('path', $featureList->settings['path']);

    if (is_dir($path)) {
        $dirs = collect(scandir($path))->filter(function ($file) {
            return ! in_array($file, ['.', '..']);
        })->map(fn ($file) => [
            'is_directory' => $isDirectory = is_dir($path.'/'.$file),
            'absolute' => md5($path.'/'.$file),
            'md5_content' => $isDirectory ? null : md5_file($path.'/'.$file),
            'name' => $file,
            'file_path' => $path.'/'.$file,
            'feature_id' => $featureList->id,
        ])
        ->groupBy('is_directory');

        return collect([
            //Directories should asthetically be first
            isset($dirs[1]) ? $dirs[1]?->sortBy('name') : [],
            // Then directories
            isset($dirs[0]) ? $dirs[0]?->sortBy('name') : [],
        ])->filter()->values()->flatten(1)->toArray();
    }

    abort_unless(file_exists($path), 404, 'File not found');

    return response(file_get_contents($path), 200, [
        'Content-Type' => 'text/plain',
    ]);
});

Route::put('files/{featureList}', function (FeatureList $featureList) {
    abort_unless(request()->has('path'), 404, 'No file found');

    $exists = file_exists($path = request()->get('path'));

    if (! file_put_contents($path, request()->get('data'))) {
        return response('Could not create file', 500);
    }

    broadcast(new FileSavedEvent($featureList, auth()->user(), $path));

    return response()->json([
        'message' => $exists ? 'File updated' : 'File created',
    ]);
});

Route::post('files/{featureList}/create-directory', function (FeatureList $featureList, Filesystem $filesystem) {
    abort_if(file_exists($filePath = $featureList->settings['path'].'/'.request()->get('name')), 404, 'File found when we didn\'t expect it');

    abort_unless(request()->user()->tokenCan('create-directory'), 403, 'You do not have permission to create directories');

    $parentDirectory = pathinfo($filePath, PATHINFO_DIRNAME);
    if (! file_exists($parentDirectory)) {
        $filesystem->makeDirectory($parentDirectory, 0755, true);
    }
    $filesystem->makeDirectory($filePath, 0755, true);

    return response('', 204);
});
Route::post('files/{featureList}/create-file', function (FeatureList $featureList, Filesystem $filesystem) {
    abort_if(file_exists($filePath = $featureList->settings['path'].'/'.request()->get('name')), 404, 'File found when we didn\'t expect it');

    abort_unless(request()->user()->tokenCan('create-file'), 403, 'You do not have permission to create files');

    $parentDirectory = pathinfo($filePath, PATHINFO_DIRNAME);

    if (! file_exists($parentDirectory)) {
        $filesystem->makeDirectory($parentDirectory, 0755, true);
    }

    file_put_contents($filePath, '');

    return response('', 204);
});
Route::post('files/{featureList}/destroy', function (FeatureList $featureList, Filesystem $filesystem) {
    $filePath = request()->get('name');

    abort_unless(file_exists($filePath), 404, 'File not found');

    abort_unless(request()->user()->tokenCan('destroy-file'), 403, 'You do not have permission to delete files');

    if (is_dir($filePath)) {
        abort_unless(rmdir($filePath), 500, 'Could not delete directory');
    } else {
        abort_unless(unlink($filePath), 500, 'Could not delete file');
    }

    return response('', 204);
});
Route::post('feature-list/{featureList}/redeploy', function (FeatureList $featureList, Filesystem $filesystem) {
    abort_if(empty($featureList->settings['template']), 404, 'No template found on the feature.');

    event(new RedeployRequested($featureList));

    return response('', 204);
});
