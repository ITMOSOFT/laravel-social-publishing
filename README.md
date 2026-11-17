# ITMOSOFT Laravel Social Publishing

Social account management, content scheduling, publication tracking, and platform adapter contracts for Laravel applications.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require itmosoft/laravel-social-publishing
php artisan vendor:publish --tag=itmosoft-social-publishing-config
php artisan vendor:publish --tag=itmosoft-social-publishing-migrations
php artisan migrate
```

Laravel package auto-discovery registers the service provider.

## Create an account and post

```php
use ItmoSoft\SocialPublishing\SocialPublishingManager;

$manager = app(SocialPublishingManager::class);

$account = $manager->createAccount(
    owner: $user,
    platform: 'instagram',
    accountIdentifier: 'your-provider-account-id',
    credentials: ['access_token' => $accessToken],
);

$post = $manager->createPost(
    owner: $user,
    content: 'Hello from our Laravel application',
    media: ['https://example.com/image.jpg'],
);
```

## Schedule a post

```php
$post = $manager->schedule($post, now()->addDay());
```

The package stores the scheduled timestamp. Your application must run a scheduler/worker that selects due posts and calls the publishing manager. This initial release does not register an autonomous scheduler or queue worker.

## Implement a platform adapter

Implement `SocialPlatformPublisher` for each provider:

```php
namespace App\Social;

use ItmoSoft\SocialPublishing\Contracts\SocialPlatformPublisher;
use ItmoSoft\SocialPublishing\Models\SocialAccount;
use ItmoSoft\SocialPublishing\Models\SocialPost;

class ExamplePlatformPublisher implements SocialPlatformPublisher
{
    public function publish(SocialAccount $account, SocialPost $post): array
    {
        // Call the provider SDK/API here.
        // Return provider response data, including its external post ID.
        return ['id' => 'provider-post-id'];
    }
}
```

Publish:

```php
use ItmoSoft\SocialPublishing\Contracts\SocialPlatformPublisher;

$publication = $manager->publish(
    $post,
    $account,
    app(SocialPlatformPublisher::class),
);
```

The manager records success/failure and the provider response. Failed attempts can be retried for the same account. Bind your provider adapter in a service provider.

## Lifecycle

Post statuses:
- `draft`
- `scheduled`
- `publishing`
- `published`
- `failed`
- `cancelled`

Publication statuses:
- `pending`
- `publishing`
- `published`
- `failed`

## Security

Account credentials use Laravel's encrypted array cast. Set a stable `APP_KEY`, protect backups, and never log access tokens. The provider adapter is responsible for token refresh, scopes, API rate limits, and provider-specific validation.

## Scope boundaries

This package does not ship real provider API integrations, OAuth flows, token refresh, media upload pipelines, analytics, or an autonomous scheduler/queue worker. Implement and test provider adapters for the API versions and permissions your application uses.

## Tests

```bash
composer install
composer test
```

## License

MIT. See [LICENSE](LICENSE).
