<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\IvrVoiceMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class IvrVoiceMessageController extends Controller
{
    private const LANGUAGES = ['bambara', 'peulh', 'soninke', 'francais'];
    private const TYPES = ['cpn_reminder', 'delivery_reminder', 'custom'];

    public function index(Request $request)
    {
        $validated = $request->validate([
            'language' => ['nullable', Rule::in(self::LANGUAGES)],
            'call_type' => ['nullable', Rule::in(self::TYPES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $messages = IvrVoiceMessage::query()
            ->when($validated['language'] ?? null, fn ($q, $v) => $q->where('language', $v))
            ->when($validated['call_type'] ?? null, fn ($q, $v) => $q->where('call_type', $v))
            ->orderByDesc('is_active')->orderBy('language')->orderBy('call_type')->orderByDesc('version')
            ->paginate($validated['per_page'] ?? 100);

        return response()->json([
            'data' => $messages->items(),
            'current_page' => $messages->currentPage(),
            'last_page' => $messages->lastPage(),
            'total' => $messages->total(),
            'languages' => self::LANGUAGES,
            'types' => self::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(true));
        $file = $validated['audio'];
        $path = $file->store('ivr/voice-messages', 'public');

        try {
            $message = DB::transaction(function () use ($validated, $file, $path) {
                if ($this->toBoolean($validated['is_active'] ?? false)) {
                    IvrVoiceMessage::where('language', $validated['language'])
                        ->where('call_type', $validated['call_type'])->update(['is_active' => false]);
                }

                return IvrVoiceMessage::create([
                    'title' => $validated['title'],
                    'language' => $validated['language'],
                    'call_type' => $validated['call_type'],
                    'description' => $validated['description'] ?? null,
                    'audio_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'is_active' => $this->toBoolean($validated['is_active'] ?? false),
                    'version' => 1,
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        $this->audit($request, 'ivr.voice_message.created', $message);
        return response()->json(['message' => 'Message vocal créé.', 'data' => $message], 201);
    }

    public function update(Request $request, IvrVoiceMessage $message)
    {
        $validated = $request->validate($this->rules(false));
        $oldPath = $message->audio_path;
        $newPath = isset($validated['audio']) ? $validated['audio']->store('ivr/voice-messages', 'public') : null;

        try {
            DB::transaction(function () use ($validated, $message, $newPath) {
                $language = $validated['language'] ?? $message->language;
                $callType = $validated['call_type'] ?? $message->call_type;
                $activate = array_key_exists('is_active', $validated)
                    ? $this->toBoolean($validated['is_active']) : $message->is_active;

                if ($activate) {
                    IvrVoiceMessage::where('language', $language)->where('call_type', $callType)
                        ->where('id', '<>', $message->id)->update(['is_active' => false]);
                }

                $message->fill([
                    'title' => $validated['title'] ?? $message->title,
                    'language' => $language,
                    'call_type' => $callType,
                    'description' => $validated['description'] ?? null,
                    'is_active' => $activate,
                ]);
                if ($newPath) {
                    $file = $validated['audio'];
                    $message->audio_path = $newPath;
                    $message->original_filename = $file->getClientOriginalName();
                    $message->mime_type = $file->getMimeType();
                    $message->file_size = $file->getSize();
                    $message->version = ((int) $message->version) + 1;
                }
                $message->save();
            });
        } catch (\Throwable $e) {
            if ($newPath) Storage::disk('public')->delete($newPath);
            throw $e;
        }

        if ($newPath && $oldPath && $oldPath !== $newPath) Storage::disk('public')->delete($oldPath);
        $this->audit($request, 'ivr.voice_message.updated', $message);
        return response()->json(['message' => 'Message vocal mis à jour.', 'data' => $message->fresh()]);
    }

    public function activate(Request $request, IvrVoiceMessage $message)
    {
        DB::transaction(function () use ($message) {
            IvrVoiceMessage::where('language', $message->language)
                ->where('call_type', $message->call_type)->update(['is_active' => false]);
            $message->update(['is_active' => true]);
        });
        $this->audit($request, 'ivr.voice_message.activated', $message);
        return response()->json(['message' => 'Message vocal activé.', 'data' => $message->fresh()]);
    }

    public function destroy(Request $request, IvrVoiceMessage $message)
    {
        $path = $message->audio_path;
        $message->delete();
        if ($path) Storage::disk('public')->delete($path);
        $this->audit($request, 'ivr.voice_message.deleted', $message);
        return response()->json(['message' => 'Message vocal supprimé.']);
    }

    private function rules(bool $required): array
    {
        $presence = $required ? 'required' : 'sometimes';
        return [
            'title' => [$presence, 'string', 'max:160'],
            'language' => [$presence, Rule::in(self::LANGUAGES)],
            'call_type' => [$presence, Rule::in(self::TYPES)],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
            'audio' => [$required ? 'required' : 'sometimes', 'file', 'mimes:mp3,wav,ogg,m4a,aac', 'max:51200'],
        ];
    }

    private function toBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function audit(Request $request, string $action, IvrVoiceMessage $message): void
    {
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'actor_name' => $request->user()?->name,
            'actor_email' => $request->user()?->email,
            'action' => $action,
            'auditable_type' => IvrVoiceMessage::class,
            'auditable_id' => $message->id,
            'new_values' => ['title' => $message->title, 'language' => $message->language, 'call_type' => $message->call_type, 'is_active' => $message->is_active],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
