<?php

// Mirrors app.binnii.com/app/Enums/PlanKey.php — schema owner is app.binnii.com. Keep in sync.

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Stable plan IDs shared across every Market (Platform PRD F-52).
 * Transactions always use the key, never the market-specific display name.
 */
enum PlanKey: string
{
    use HasValues;

    case Go = 'go';
    case Plus = 'plus';
    case Pro = 'pro';
}
