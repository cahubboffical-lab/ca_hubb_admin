<?php

namespace App\Jobs;

use App\Services\NotificationService;
use App\Models\UserFcmToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFcmBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $title;
    protected $message;
    protected $type;
    protected $customBodyFields;
    protected $sendToAll;
    protected $userIds;

    public function __construct($title, $message, $type = 'default', $customBodyFields = [], $sendToAll = false, $userIds = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->customBodyFields = $customBodyFields;
        $this->sendToAll = $sendToAll;
        $this->userIds = $userIds;
    }

    public function handle()
    {
        Log::info("🔔 SendFcmBatchJob started");

        // Send directly to every registered device. Relying on the allUsers topic
        // meant that registered tokens which had never subscribed to that topic
        // silently missed admin notifications.
        if ($this->sendToAll) {
            UserFcmToken::with('user')
                ->whereHas('user', fn($q) => $q->where('notification', 1))
                ->select(['id', 'fcm_token', 'platform_type', 'user_id'])
                ->chunkById(500, function ($tokens) {
                    NotificationService::sendFcmNotification(
                        $tokens->pluck('fcm_token')->filter()->unique()->values()->all(),
                        $this->title,
                        $this->message,
                        $this->type,
                        $this->customBodyFields,
                        false
                    );
                });

            Log::info("📱 Notification sent directly to all registered devices.");

        } else {
            // ✅ Send to specific selected users
            UserFcmToken::with('user')
                ->whereIn('user_id', $this->userIds)
                ->whereHas('user', fn($q) => $q->where('notification', 1))
                ->chunk(500, function ($tokens) {
                    $fcmTokens = $tokens->pluck('fcm_token')->toArray();
                    NotificationService::sendFcmNotification(
                        $fcmTokens, $this->title, $this->message, $this->type, $this->customBodyFields, false
                    );
                });

            Log::info("👥 Notifications sent to selected users.");
        }

        Log::info("✅ SendFcmBatchJob finished");
    }
}
