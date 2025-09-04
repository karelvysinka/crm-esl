<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\AI\AgentService;

class ChatController extends Controller
{
    public function page()
    {
        return view('crm.chat');
    }

    public function createSession(Request $request)
    {
        $userId = Auth::id();
        $title = $request->string('title')->toString() ?: null;
        $contextType = $request->string('context_type')->toString() ?: null;
        $contextId = $request->input('context_id');

        $sessionId = DB::table('chat_sessions')->insertGetId([
            'user_id' => $userId,
            'title' => $title,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['session_id' => $sessionId]);
    }

    public function sessions(Request $request)
    {
        $userId = Auth::id();
        $q = DB::table('chat_sessions')
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20);
        return response()->json($q);
    }

    public function postMessage(Request $request)
    {
        $request->validate([
            'session_id' => 'required|integer|exists:chat_sessions,id',
            'content' => 'required|string|min:1',
        ]);

        $messageId = DB::table('chat_messages')->insertGetId([
            'session_id' => (int)$request->input('session_id'),
            'role' => 'user',
            'content' => $request->input('content'),
            'status' => 'queued',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message_id' => $messageId]);
    }

    public function history($id)
    {
        $messages = DB::table('chat_messages')
            ->where('session_id', (int)$id)
            ->orderBy('id')
            ->paginate(100);

        return response()->json($messages);
    }

    public function stream(Request $request, AgentService $agent)
    {
        $request->validate([
            'session_id' => 'required|integer|exists:chat_sessions,id',
            'message_id' => 'required|integer|exists:chat_messages,id',
        ]);

        $messageId = (int)$request->input('message_id');

        // Load user message and session
        $userMessage = DB::table('chat_messages')->where('id', $messageId)->first();
        abort_unless($userMessage && $userMessage->role === 'user', 404);
        $sessionId = (int)$request->input('session_id');

        // Create assistant message placeholder to persist the stream
        $assistantMessageId = DB::table('chat_messages')->insertGetId([
            'session_id' => $sessionId,
            'role' => 'assistant',
            'content' => '',
            'status' => 'streaming',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $agent->streamResponse($messageId, $sessionId, (string)$userMessage->content, $assistantMessageId);
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}
