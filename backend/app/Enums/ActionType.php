<?php

namespace App\Enums;

enum ActionType: string
{
    case Stabilize = 'stabilize';
    case Close = 'close';
    case DispatchObserver = 'dispatch_observer';
    case MarkUnderReview = 'mark_under_review';
}
