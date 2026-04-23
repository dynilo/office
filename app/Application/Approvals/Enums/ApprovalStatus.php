<?php

namespace App\Application\Approvals\Enums;

enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
