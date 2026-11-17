<?php

namespace ItmoSoft\SocialPublishing\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use ItmoSoft\SocialPublishing\Contracts\SocialPlatformPublisher;
use ItmoSoft\SocialPublishing\Enums\SocialPostStatus;
use ItmoSoft\SocialPublishing\Enums\SocialPublicationStatus;
use ItmoSoft\SocialPublishing\Exceptions\SocialPublishingException;
use ItmoSoft\SocialPublishing\Models\SocialAccount;
use ItmoSoft\SocialPublishing\Models\SocialPost;
use ItmoSoft\SocialPublishing\SocialPublishingManager;
use ItmoSoft\SocialPublishing\Tests\TestCase;

class User extends Model
{
    protected $guarded = [];
}

class FakePublisher implements SocialPlatformPublisher
{
    public function publish(SocialAccount $account, SocialPost $post): array
    {
        return ['id' => 'provider-post-123', 'platform' => $account->platform];
    }
}

class FailingPublisher implements SocialPlatformPublisher
{
    public function publish(SocialAccount $account, SocialPost $post): array
    {
        throw new \RuntimeException('Provider unavailable');
    }
}

class SocialPublishingManagerTest extends TestCase
{
    private SocialPublishingManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(SocialPublishingManager::class);
    }

    private function user(string $name = 'Owner'): User
    {
        return User::query()->create(['name' => $name]);
    }

    public function test_it_creates_a_social_account(): void
    {
        $owner = $this->user();

        $account = $this->manager->createAccount(
            $owner,
            'Instagram',
            'business-account-1',
            ['access_token' => 'secret-token']
        );

        $this->assertSame('instagram', $account->platform);
        $this->assertTrue($account->is_active);
        $this->assertSame('secret-token', $account->credentials['access_token']);
    }

    public function test_it_creates_a_draft_post(): void
    {
        $post = $this->manager->createPost(
            $this->user(),
            'Hello social world',
            ['https://example.com/image.jpg']
        );

        $this->assertSame(SocialPostStatus::Draft, $post->status);
        $this->assertSame('Hello social world', $post->content);
        $this->assertCount(1, $post->media);
    }

    public function test_it_schedules_a_draft_post_for_a_future_time(): void
    {
        $post = $this->manager->createPost($this->user(), 'Scheduled content');

        $scheduled = $this->manager->schedule($post, now()->addDay());

        $this->assertSame(SocialPostStatus::Scheduled, $scheduled->status);
        $this->assertNotNull($scheduled->scheduled_at);
    }

    public function test_it_rejects_scheduling_in_the_past(): void
    {
        $post = $this->manager->createPost($this->user(), 'Past content');

        $this->expectException(SocialPublishingException::class);
        $this->manager->schedule($post, now()->subMinute());
    }

    public function test_it_cancels_a_scheduled_post(): void
    {
        $post = $this->manager->createPost($this->user(), 'Cancel me');
        $post = $this->manager->schedule($post, now()->addHour());

        $cancelled = $this->manager->cancel($post);

        $this->assertSame(SocialPostStatus::Cancelled, $cancelled->status);
    }

    public function test_it_publishes_a_post_and_records_provider_response(): void
    {
        $owner = $this->user();
        $account = $this->manager->createAccount($owner, 'x', 'account-1');
        $post = $this->manager->createPost($owner, 'Publish this');

        $publication = $this->manager->publish($post, $account, new FakePublisher());

        $this->assertSame(SocialPublicationStatus::Published, $publication->status);
        $this->assertSame('provider-post-123', $publication->response['id']);
        $this->assertSame(SocialPostStatus::Published, $post->refresh()->status);
        $this->assertNotNull($post->published_at);
    }

    public function test_it_records_provider_failure_and_marks_post_failed(): void
    {
        $owner = $this->user();
        $account = $this->manager->createAccount($owner, 'linkedin', 'account-2');
        $post = $this->manager->createPost($owner, 'Will fail');

        try {
            $this->manager->publish($post, $account, new FailingPublisher());
            $this->fail('Expected publisher failure.');
        } catch (SocialPublishingException $exception) {
            $this->assertStringContainsString('Provider unavailable', $exception->getMessage());
        }

        $this->assertSame(SocialPostStatus::Failed, $post->refresh()->status);
        $this->assertSame(
            SocialPublicationStatus::Failed,
            $post->publications()->firstOrFail()->status
        );
    }

    public function test_it_can_retry_a_failed_publication_for_the_same_account(): void
    {
        $owner = $this->user();
        $account = $this->manager->createAccount($owner, 'instagram', 'retry-account');
        $post = $this->manager->createPost($owner, 'Retry this');

        try {
            $this->manager->publish($post, $account, new FailingPublisher());
        } catch (SocialPublishingException) {
            // The failed attempt is expected and will be retried below.
        }

        $publication = $this->manager->publish($post->refresh(), $account, new FakePublisher());

        $this->assertSame(SocialPublicationStatus::Published, $publication->status);
        $this->assertSame(1, $post->publications()->count());
        $this->assertSame('provider-post-123', $publication->external_post_id);
    }

    public function test_it_rejects_publishing_to_an_inactive_account(): void
    {
        $owner = $this->user();
        $account = $this->manager->createAccount($owner, 'facebook', 'inactive');
        $account->forceFill(['is_active' => false])->save();
        $post = $this->manager->createPost($owner, 'Content');

        $this->expectException(SocialPublishingException::class);
        $this->manager->publish($post, $account, new FakePublisher());
    }

    public function test_it_rejects_publishing_when_post_and_account_owners_differ(): void
    {
        $account = $this->manager->createAccount($this->user('First'), 'x', 'account');
        $post = $this->manager->createPost($this->user('Second'), 'Content');

        $this->expectException(SocialPublishingException::class);
        $this->manager->publish($post, $account, new FakePublisher());
    }
}
