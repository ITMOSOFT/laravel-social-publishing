<?php

namespace ItmoSoft\SocialPublishing\Contracts;

use ItmoSoft\SocialPublishing\Models\SocialAccount;
use ItmoSoft\SocialPublishing\Models\SocialPost;

interface SocialPlatformPublisher
{
    /**
     * Publish a post to the connected platform and return provider response data.
     *
     * @return array<string, mixed>
     */
    public function publish(SocialAccount $account, SocialPost $post): array;
}
