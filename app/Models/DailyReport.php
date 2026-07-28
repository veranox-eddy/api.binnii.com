<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Database\Factories\DailyReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['child_id', 'report_date', 'status', 'sent_at'])]
class DailyReport extends Model
{
    /** @use HasFactory<DailyReportFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'status' => ReportStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DailyReportLog::class)->latest('created_at');
    }

    /**
     * The child's entries for this report's date.
     *
     * @return Builder<Entry>
     */
    public function entriesQuery(): Builder
    {
        return Entry::query()
            ->where('child_id', $this->child_id)
            ->whereDate('occurred_at', $this->report_date->toDateString())
            ->orderBy('occurred_at');
    }

    /**
     * Ensure the (child, date) report exists — called when entries are
     * created; logs 'created' exactly once.
     */
    public static function ensureFor(Child $child, string $date, ?int $actorId = null): self
    {
        $report = self::where('child_id', $child->id)->whereDate('report_date', $date)->first();

        if (! $report) {
            $report = self::create(['child_id' => $child->id, 'report_date' => $date]);
            $report->log('created', $actorId);
        }

        return $report;
    }

    public function send(?int $actorId = null): void
    {
        $this->update(['status' => ReportStatus::Sent, 'sent_at' => now()]);
        $this->log('sent', $actorId);
    }

    public function reopen(?int $actorId = null): void
    {
        $this->update(['status' => ReportStatus::Open, 'sent_at' => null]);
        $this->log('reopened', $actorId);
    }

    public function log(string $action, ?int $actorId = null): void
    {
        $this->logs()->create(['action' => $action, 'actor_id' => $actorId, 'created_at' => now()]);
    }
}
