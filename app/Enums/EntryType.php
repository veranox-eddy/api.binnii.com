<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * The 15 care-log entry types (schema doc G — exactly these values, matching
 * the Add Entry tiles). Each type declares its payload fields here, making
 * this enum the single source for form rendering AND validation: fields()
 * feeds the generic field renderer in the Add Entry view, and rules() feeds
 * StoreEntryRequest's payload.* validation.
 *
 * Field descriptor shape:
 *   name    payload key
 *   label   form label
 *   input   text | textarea | select | time
 *   options select choices (list of strings), or the literal string
 *           'classrooms' resolved at render/validation time
 *   rules   Laravel validation rules for payload.<name>
 */
enum EntryType: string
{
    use HasValues;

    case CheckIn = 'check_in';
    case CheckOut = 'check_out';
    case Activity = 'activity';
    case Food = 'food';
    case Fluids = 'fluids';
    case Sleep = 'sleep';
    case Toilet = 'toilet';
    case Mood = 'mood';
    case Health = 'health';
    case Temperature = 'temperature';
    case Incident = 'incident';
    case Supplies = 'supplies';
    case Notes = 'notes';
    case MoveRooms = 'move_rooms';
    case NameToFace = 'name_to_face';

    public const array MEALS = ['Breakfast', 'Morning snack', 'Lunch', 'Afternoon snack', 'Dinner'];

    public const array AMOUNTS = ['All', 'Most', 'Some', 'None'];

    public const array MOODS = ['Adventurous', 'Happy', 'Calm', 'Playful', 'Sad', 'Tired'];

    public const array MOOD_LEVELS = ['Slightly', 'Somewhat', 'Very', 'Extremely', 'N/A'];

    public const array NOTE_TYPES = ['General', 'Notice', 'Medication Administered', 'Incident'];

    public function label(): string
    {
        return match ($this) {
            self::CheckIn => 'Check in',
            self::CheckOut => 'Check out',
            self::Activity => 'Activity',
            self::Food => 'Food',
            self::Fluids => 'Fluids',
            self::Sleep => 'Sleep',
            self::Toilet => 'Toilet',
            self::Mood => 'Mood',
            self::Health => 'Health',
            self::Temperature => 'Temperature',
            self::Incident => 'Incident',
            self::Supplies => 'Supplies',
            self::Notes => 'Notes',
            self::MoveRooms => 'Move rooms',
            self::NameToFace => 'Name to Face',
        };
    }

    /** Add Entry tile icon. */
    public function icon(): string
    {
        return match ($this) {
            self::CheckIn => 'fa-right-to-bracket',
            self::CheckOut => 'fa-right-from-bracket',
            self::Activity => 'fa-puzzle-piece',
            self::Food => 'fa-utensils',
            self::Fluids => 'fa-glass-water',
            self::Sleep => 'fa-bed',
            self::Toilet => 'fa-toilet',
            self::Mood => 'fa-face-smile',
            self::Health => 'fa-heart-pulse',
            self::Temperature => 'fa-temperature-half',
            self::Incident => 'fa-triangle-exclamation',
            self::Supplies => 'fa-box-open',
            self::Notes => 'fa-note-sticky',
            self::MoveRooms => 'fa-right-left',
            self::NameToFace => 'fa-eye',
        };
    }

    /**
     * Payload field descriptors for this type (PRD 8.1 form table).
     *
     * @return array<int, array<string, mixed>>
     */
    public function fields(): array
    {
        $notes = ['name' => 'notes', 'label' => 'Notes', 'input' => 'textarea', 'rules' => ['nullable', 'string', 'max:1000']];

        return match ($this) {
            // No form — the tile creates the entry directly.
            self::CheckIn, self::CheckOut, self::NameToFace => [],

            self::Activity, self::Fluids, self::Health, self::Supplies, self::Toilet => [$notes],

            // Incident entries link to the incident report form in a later
            // slice (payload may then carry incident_id).
            self::Incident => [$notes],

            self::Food => [
                ['name' => 'meal', 'label' => 'Meal', 'input' => 'select', 'options' => self::MEALS, 'rules' => ['required', 'in:'.implode(',', self::MEALS)]],
                ['name' => 'amount', 'label' => 'Amount', 'input' => 'select', 'options' => self::AMOUNTS, 'rules' => ['required', 'in:'.implode(',', self::AMOUNTS)]],
                $notes,
            ],

            self::Sleep => [
                ['name' => 'start_time', 'label' => 'Start', 'input' => 'time', 'rules' => ['required', 'date_format:H:i']],
                ['name' => 'end_time', 'label' => 'End', 'input' => 'time', 'rules' => ['nullable', 'date_format:H:i', 'after:payload.start_time']],
                $notes,
            ],

            self::Mood => [
                ['name' => 'mood', 'label' => 'Mood', 'input' => 'select', 'options' => self::MOODS, 'rules' => ['required', 'in:'.implode(',', self::MOODS)]],
                ['name' => 'level', 'label' => 'Level', 'input' => 'select', 'options' => self::MOOD_LEVELS, 'rules' => ['nullable', 'in:'.implode(',', self::MOOD_LEVELS)]],
                $notes,
            ],

            self::Temperature => [
                ['name' => 'value', 'label' => 'Temperature', 'input' => 'text', 'rules' => ['required', 'string', 'max:20']],
                $notes,
            ],

            self::Notes => [
                ['name' => 'note_type', 'label' => 'Type', 'input' => 'select', 'options' => self::NOTE_TYPES, 'rules' => ['required', 'in:'.implode(',', self::NOTE_TYPES)]],
                $notes,
            ],

            self::MoveRooms => [
                ['name' => 'to_classroom_id', 'label' => 'Move to', 'input' => 'select', 'options' => 'classrooms', 'rules' => ['required', 'integer']],
                $notes,
            ],
        };
    }

    /**
     * payload.* validation rules for StoreEntryRequest.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return collect($this->fields())
            ->mapWithKeys(fn (array $field) => ["payload.{$field['name']}" => $field['rules']])
            ->all();
    }

    /**
     * One-line summary used by the report preview/edit tables.
     *
     * @param  array<string, mixed>  $payload
     */
    public function summarize(array $payload): string
    {
        $parts = match ($this) {
            self::Food => [$payload['meal'] ?? null, isset($payload['amount']) ? "ate {$payload['amount']}" : null],
            self::Sleep => [isset($payload['start_time']) ? ($payload['start_time'].' – '.($payload['end_time'] ?? 'sleeping')) : null],
            self::Mood => [$payload['mood'] ?? null, $payload['level'] ?? null],
            self::Temperature => [$payload['value'] ?? null],
            self::Notes => [$payload['note_type'] ?? null],
            default => [],
        };

        return collect([...$parts, $payload['notes'] ?? null])->filter()->implode(' · ');
    }

    /**
     * The value shown in the teacher-facing "Qty" column (me-editreport.html).
     * Only Food (amount eaten) and Temperature (the reading) carry a distinct
     * quantity; every other type leaves it blank. Deliberately separate from
     * summarize(), which keeps folding these into the parent-facing narrative
     * in daily-reports/show.blade.php unchanged.
     *
     * @param  array<string, mixed>  $payload
     */
    public function qty(array $payload): ?string
    {
        return match ($this) {
            self::Food => $payload['amount'] ?? null,
            self::Temperature => $payload['value'] ?? null,
            default => null,
        };
    }
}
