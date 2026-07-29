<?php

namespace App\Http\Controllers\Api;

use App\Enums\AccessLevel;
use App\Enums\ConversationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReplyConversationRequest;
use App\Http\Requests\Api\StoreConversationRequest;
use App\Http\Resources\ConversationListItemResource;
use App\Http\Resources\ConversationThreadResource;
use App\Http\Resources\MessageResource;
use App\Models\Center;
use App\Models\Child;
use App\Models\Conversation;
use App\Models\Guardian;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConversationController extends Controller
{
    /** The inbox (S15): the guardian's threads, newest activity first. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $guardian = $this->guardian();

        $query = Conversation::query()
            ->forGuardian($guardian)
            ->whereNull('archived_at')
            ->released()
            ->with(['participants.participant', 'center'])
            ->withMax('messages as last_message_at', 'created_at')
            ->withExists(['messages as unread' => fn ($q) => $q
                ->whereNull('read_at')
                ->whereNot(fn ($qq) => $qq
                    ->where('sender_type', 'guardian')
                    ->where('sender_id', $guardian->getKey()))])
            ->orderByDesc('last_message_at');

        // Conversations carry no child_id; the filter narrows to the named
        // child's center (a parent with children at two centers holds a
        // guardian row per center, so this is the axis that exists).
        if ($request->filled('child')) {
            $child = Child::findOrFail($request->integer('child'));
            $this->authorize('view', $child);
            $query->where('center_id', $child->center_id);
        }

        return ConversationListItemResource::collection($query->paginate(20));
    }

    /** Reading the thread is what marks it read. */
    public function show(Conversation $conversation): ConversationThreadResource
    {
        $this->authorize('view', $conversation);

        $conversation->messages()
            ->whereNull('read_at')
            ->whereNot(fn ($q) => $q
                ->where('sender_type', 'guardian')
                ->where('sender_id', $this->guardian()->getKey()))
            ->update(['read_at' => now()]);

        return new ConversationThreadResource(
            $conversation->load(['messages.sender', 'messages.attachments']),
        );
    }

    /** Compose (S16): a guardian writes to the center about one child. */
    public function store(StoreConversationRequest $request): ConversationThreadResource
    {
        $guardian = $this->guardian();
        $child = Child::findOrFail($request->integer('child_id'));
        $this->authorize('view', $child);

        $conversation = DB::transaction(function () use ($request, $guardian, $child) {
            $conversation = Conversation::create([
                'center_id' => $child->center_id,
                'subject' => $request->string('subject')->value(),
                'type' => ConversationType::Message,
                // created_by cannot be null and must be a staff user; the
                // guardian is the thread's author via participants + the
                // message sender (owner-approved reading of API_09's note).
                'created_by' => $this->centerContactId($child->center),
                'shared_with_teachers' => $request->string('send_to')->value() === 'director_teacher',
            ]);

            $conversation->participants()->create([
                'participant_type' => 'guardian',
                'participant_id' => $guardian->getKey(),
                'role' => 'sender',
            ]);
            $conversation->participants()->create([
                'participant_type' => 'user',
                'participant_id' => $conversation->created_by,
                'role' => 'recipient',
            ]);

            $this->appendMessage($conversation, $guardian, $request);

            return $conversation;
        });

        return new ConversationThreadResource(
            $conversation->load(['messages.sender', 'messages.attachments']),
        );
    }

    public function reply(ReplyConversationRequest $request, Conversation $conversation): MessageResource
    {
        $this->authorize('reply', $conversation);

        $message = $this->appendMessage($conversation, $this->guardian(), $request);

        return new MessageResource($message->load('sender', 'attachments'));
    }

    public function archive(Conversation $conversation): Response
    {
        $this->authorize('archive', $conversation);

        $conversation->update(['archived_at' => now()]);

        return response()->noContent();
    }

    private function appendMessage(Conversation $conversation, Guardian $guardian, Request $request): Message
    {
        $message = $conversation->messages()->create([
            'sender_type' => 'guardian',
            'sender_id' => $guardian->getKey(),
            'body' => $request->string('body')->value(),
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $message->attachments()->create([
                'file_path' => $file->store('message-attachments', 'public'),
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]);
        }

        return $message;
    }

    /**
     * The staff user a guardian thread hangs off: the first active user
     * assigned to the center, else an organization-level user of its org.
     */
    private function centerContactId(Center $center): int
    {
        $id = User::query()
            ->where('is_active', true)
            ->whereHas('centers', fn ($q) => $q->whereKey($center->getKey()))
            ->orderBy('id')
            ->value('id')
            ?? User::query()
                ->where('is_active', true)
                ->where('organization_id', $center->organization_id)
                ->where('access_level', AccessLevel::Organization)
                ->orderBy('id')
                ->value('id');

        if ($id === null) {
            throw ValidationException::withMessages([
                'send_to' => 'This center is not set up to receive messages yet.',
            ]);
        }

        return (int) $id;
    }

    private function guardian(): Guardian
    {
        return auth('guardian')->user();
    }
}
