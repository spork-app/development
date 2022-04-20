## Development

Simply add to your spork app through composer!

```
composer require spork/development
```
And register the Service Provider in your Spork App's `config/app.php` file. That will automatically add the Development entry to the menu.

----

If you're trying to use this outside of spork, you'll want the following in your EventServiceProvider

```
PublishGitInformationRequested::class => [
    SendGitInformationToChannel::class
],

RedeployRequested::class => [
    DeleteDevelopmentFiles::class,
    CopyTemplateIfApplicableListener::class,
],
```