<?php

namespace ItmoSoft\SocialPublishing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ItmoSoft\SocialPublishing\Contracts\SocialPlatformPublisher;
use ItmoSoft\SocialPublishing\Enums\SocialPostStatus;
use ItmoSoft\SocialPublishing\Enums\SocialPublicationStatus;
use ItmoSoft\SocialPublishing\Exceptions\SocialPublishingException;
use ItmoSoft\SocialPublishing\Models\SocialAccount;
use ItmoSoft\SocialPublishing\Models\SocialPost;
use ItmoSoft\SocialPublishing\Models\SocialPublication;

class SocialPublishingManager
{
    public function createAccount(
        Model $owner,
        string $platform,
        string $accountIdentifier,
        array $credentials = [],
        array $metadata = []
    ): SocialAccount {
        $model = config('itmosoft-social-publishing.models.account', SocialAccount::class);

        return $model::query()->create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'platform' => strtolower(trim($platform)),
            'account_identifier' => trim($accountIdentifier),
            'credentials' => $credentials,
            'metadata' => $metadata,
            'is_active' => true,
        ]);
    }

    public function createPost(
        Model $owner,
        string $content,
        array $media = [],
        array $metadata = []
    ): SocialPost {
        $model = config('itmosoft-social-publishing.models.post', SocialPost::class);

        return $model::query()->create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'content' => $content,
            'media' => $media,
            'metadata' => $metadata,
            'status' => SocialPostStatus::Draft,
        ]);
    }

    public function schedule(SocialPost $post, \DateTimeInterface $scheduledAt): SocialPost
    {
        if (! in_array($post->status, [SocialPostStatus::Draft, SocialPostStatus::Failed], true)) {
            throw new SocialPublishingException('Only draft or failed posts can be scheduled.');
        }

        if ($scheduledAt <= now()) {
            throw new SocialPublishingException('Scheduled time must be in the future.');
        }

        $post->forceFill([
            'status' => SocialPostStatus::Scheduled,
            'scheduled_at' => $scheduledAt,
        ])->save();

        return $post->refresh();
    }

    public function cancel(SocialPost $post): SocialPost
    {
        if ($post->status !== SocialPostStatus::Scheduled) {
            throw new SocialPublishingException('Only scheduled posts can be cancelled.');
        }

        $post->forceFill(['status' => SocialPostStatus::Cancelled])->save();

        return $post->refresh();
    }

    public function publish(
        SocialPost $post,
        SocialAccount $account,
        SocialPlatformPublisher $publisher
    ): SocialPublication {
        if (! $account->is_active) {
            throw new SocialPublishingException('The social account is inactive.');
        }

        if ($post->status === SocialPostStatus::Cancelled) {
            throw new SocialPublishingException('A cancelled post cannot be published.');
        }

        if ($post->owner_type !== $account->owner_type
            || (string) $post->owner_id !== (string) $account->owner_id) {
            throw new SocialPublishingException('The post and social account must have the same owner.');
        }

        $publicationModel = config(
            'itmosoft-social-publishing.models.publication',
            SocialPublication::class
        );

        $publication = DB::transaction(function () use ($post, $account, $publicationModel): SocialPublication {
            $existing = $publicationModel::query()
                ->where('social_post_id', $post->getKey())
                ->where('social_account_id', $account->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing && in_array(
                $existing->status,
                [SocialPublicationStatus::Published, SocialPublicationStatus::Publishing],
                true
            )) {
                throw new SocialPublishingException(
                    'This post already has a published or in-progress publication for the selected account.'
                );
            }

            $post->forceFill(['status' => SocialPostStatus::Publishing])->save();

            if ($existing) {
                $existing->forceFill([
                    'status' => SocialPublicationStatus::Publishing,
                    'response' => null,
                    'error_message' => null,
                    'published_at' => null,
                ])->save();

                return $existing->refresh();
            }

            return $publicationModel::query()->create([
                'social_post_id' => $post->getKey(),
                'social_account_id' => $account->getKey(),
                'status' => SocialPublicationStatus::Publishing,
            ]);
        });

        try {
            $response = $publisher->publish($account, $post);

            return DB::transaction(function () use ($publication, $post, $response): SocialPublication {
                $publication->forceFill([
                    'status' => SocialPublicationStatus::Published,
                    'response' => $response,
                    'external_post_id' => isset($response['id']) ? (string) $response['id'] : null,
                    'published_at' => now(),
                    'error_message' => null,
                ])->save();

                $post->forceFill([
                    'status' => SocialPostStatus::Published,
                    'published_at' => now(),
                ])->save();

                return $publication->refresh();
            });
        } catch (\Throwable $exception) {
            DB::transaction(function () use ($publication, $post, $exception): void {
                $publication->forceFill([
                    'status' => SocialPublicationStatus::Failed,
                    'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                ])->save();

                $post->forceFill(['status' => SocialPostStatus::Failed])->save();
            });

            throw new SocialPublishingException(
                'The social platform publisher failed: '.$exception->getMessage(),
                previous: $exception
            );
        }
    }
}
