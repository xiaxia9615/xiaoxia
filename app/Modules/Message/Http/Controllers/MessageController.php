<?php

declare(strict_types=1);

namespace App\Modules\Message\Http\Controllers;

use App\Modules\Message\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 消息管理
 */
final class MessageController
{
    /**
     * 当前用户的消息列表。
     */
    public function index(Request $request): JsonResponse
    {
        $messages = Message::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $items = collect($messages->items())->map(fn (Message $message): array => [
            'id' => $message->id,
            'title' => $message->title,
            'content' => $message->content,
            'is_read' => $message->is_read,
            'created_at' => $message->created_at?->toDateTimeString(),
        ])->all();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'list' => $items,
                'total' => $messages->total(),
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
            ],
        ]);
    }

    /**
     * 当前用户的未读消息数量。
     */
    public function unread(Request $request): JsonResponse
    {
        $count = Message::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => ['count' => $count],
        ]);
    }

    /**
     * 标记消息已读（校验 user_id 防越权）。
     */
    public function read(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $updated = Message::query()
            ->where('user_id', $request->user()->id)
            ->where('id', (int) $validated['id'])
            ->update(['is_read' => true]);

        if ($updated === 0) {
            return response()->json(['code' => 1, 'msg' => '消息不存在', 'data' => []], 404);
        }

        return response()->json(['code' => 0, 'msg' => 'ok', 'data' => []]);
    }
}
