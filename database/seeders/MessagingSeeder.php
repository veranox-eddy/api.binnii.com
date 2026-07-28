<?php

namespace Database\Seeders;

use App\Enums\CommentThreadSubject;
use App\Enums\ConversationType;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\Center;
use App\Models\Child;
use App\Models\Comment;
use App\Models\Guardian;
use App\Models\Like;
use App\Models\User;
use Illuminate\Database\Seeder;

class MessagingSeeder extends Seeder
{
    /** The canned Safety Check body the compose form also pre-fills. */
    private const string SAFETY_CHECK_BODY = <<<'TXT'
        Dear Parents/Guardians,
           Good morning. Hope you and your family are doing well.
        You are receiving this message because your child has not been dropped off or marked as absent for today and we would like to check on the status of your child with you. One of our greatest priorities is ensuring that all of the children in our care arrive safely each and every day.
        Please kindly update us as soon as possible if your child will be absent today or will be arriving late.

        Thank you and have a wonderful day.
        TXT;

    /**
     * Demo conversations, journal photos with tagged children, a comment
     * thread and some parent likes.
     */
    public function run(): void
    {
        $center = Center::where('name', 'Childcare Centre Inc.')->firstOrFail();
        $admin = User::where('email', 'admin@childcare.test')->firstOrFail();
        $yessica = Guardian::where('center_id', $center->id)->where('first_name', 'Yessica')->firstOrFail();
        $lucy = Guardian::where('center_id', $center->id)->where('first_name', 'Lucy Lee')->firstOrFail();
        $noah = Child::where('center_id', $center->id)->where('last_name', 'Sevilla')->firstOrFail();
        $karson = Child::where('center_id', $center->id)->where('last_name', 'Law')->where('first_name', 'Karson Houlam')->firstOrFail();
        $threeToFive = $center->classrooms()->where('name', 'Three to Five Room')->firstOrFail();

        // Compose → Template Options (db-compose.html). No brand names.
        foreach ([
            ['Attendance Safety Check', 'Attendance Safety Check', self::SAFETY_CHECK_BODY],
            ['Newsletter', 'Newsletter', "Dear families,\n\nHere is what we have been up to this month.\n\nThank you and have a wonderful day."],
            ['Reminder: Tuition is due on', 'Reminder: Tuition is due on', "Dear families,\n\nA friendly reminder that tuition is due at the start of the month.\n\nThank you."],
            ['Welcome to our Family', 'Welcome to our Family', "Dear families,\n\nWelcome to our center! We are delighted to have you with us.\n\nThank you and have a wonderful day."],
        ] as [$name, $subject, $body]) {
            $center->messageTemplates()->firstOrCreate(['name' => $name], ['subject' => $subject, 'body' => $body]);
        }

        foreach ([
            ['Updates about Noah today', $yessica, 'Noah had a great morning outside — see the photos in the journal!'],
            ['Pizza day consent', $lucy, 'Friday is pizza day. Please confirm Karson may take part.'],
        ] as [$subject, $guardian, $body]) {
            if ($center->conversations()->where('subject', $subject)->exists()) {
                continue;
            }

            $conversation = $center->conversations()->create([
                'subject' => $subject,
                'type' => ConversationType::Message,
                'created_by' => $admin->id,
                'shared_with_teachers' => true,
            ]);
            $conversation->participants()->create(['participant_type' => 'user', 'participant_id' => $admin->id, 'role' => 'sender']);
            $conversation->participants()->create(['participant_type' => 'guardian', 'participant_id' => $guardian->id, 'role' => 'recipient']);
            $conversation->messages()->create(['sender_type' => 'user', 'sender_id' => $admin->id, 'body' => $body]);
            $conversation->messages()->create(['sender_type' => 'guardian', 'sender_id' => $guardian->id, 'body' => 'Thank you for the update!']);
        }

        foreach ([
            ["Look what I'm doing today!", MediaStatus::Sent, [$noah]],
            ['Outdoor play', MediaStatus::Sent, [$noah]],
            ['Craft corner', MediaStatus::Draft, []],
        ] as $i => [$caption, $status, $tagged]) {
            $media = $center->media()->firstOrCreate(
                ['caption' => $caption],
                [
                    'classroom_id' => $threeToFive->id,
                    'media_type' => MediaType::Photo,
                    'file_path' => 'media/demo-'.($i + 1).'.jpg',
                    'status' => $status,
                    'sent_at' => $status === MediaStatus::Sent ? now() : null,
                    'occurred_at' => now()->subHours($i + 1),
                ],
            );
            $media->children()->syncWithoutDetaching(collect($tagged)->pluck('id'));
        }

        $media = $center->media()->where('caption', "Look what I'm doing today!")->first();

        $thread = Comment::firstOrCreate(
            ['body' => 'Look at that smile — thanks for sharing!', 'guardian_id' => $yessica->id],
            [
                'media_id' => $media->id,
                'child_id' => $noah->id,
                'thread_subject' => CommentThreadSubject::Post,
            ],
        );

        if ($thread->replies()->doesntExist()) {
            $thread->replies()->create([
                'media_id' => $media->id,
                'child_id' => $noah->id,
                'thread_subject' => CommentThreadSubject::Post,
                'body' => 'He had so much fun today!',
            ]);
        }

        if ($media->likes()->where('guardian_id', $yessica->id)->doesntExist()) {
            Like::toggle($yessica, $media);
        }
        if ($thread->likes()->where('guardian_id', $lucy->id)->doesntExist()) {
            Like::toggle($lucy, $thread);
        }
    }
}
