<?php

namespace App\Enums;

enum PortalStatus: string
{
    case Active = 'active';
    case Stabilized = 'stabilized';
    case Closed = 'closed';
    case UnderReview = 'under_review';
}
