<?php

namespace ItmoSoft\SocialPublishing\Enums;

enum SocialPublicationStatus: string
{
    case Pending = 'pending';
    case Publishing = 'publishing';
    case Published = 'published';
    case Failed = 'failed';
}
