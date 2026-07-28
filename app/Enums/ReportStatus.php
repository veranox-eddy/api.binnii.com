<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ReportStatus: string
{
    use HasValues;

    case Open = 'open';
    case Sent = 'sent';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
