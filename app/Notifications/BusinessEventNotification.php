<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $businessId,
        public string $businessName,
        public string $eventKey,
        public string $title,
        public string $message,
        public ?string $actionUrl = null,
        public ?string $branchId = null,
        public ?string $branchName = null,
        public bool $sendEmail = false,
        public bool $sendDatabase = true,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($this->sendDatabase) {
            $channels[] = 'database';
        }

        if ($this->sendEmail && ! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject("[SABAYRA] {$this->businessName}: {$this->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->message);

        if (! empty($this->branchName)) {
            $mail->line("Branch: {$this->branchName}");
        }

        if (! empty($this->actionUrl)) {
            $mail->action('View in SABAYRA', $this->actionUrl);
        }

        return $mail->line('You received this alert based on your SABAYRA notification preferences.');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array{
     *     business_id: string,
     *     business_name: string,
     *     branch_id: ?string,
     *     branch_name: ?string,
     *     event_key: string,
     *     title: string,
     *     message: string,
     *     action_url: ?string
     * }
     */
    public function toArray(object $notifiable): array
    {
        return [
            'business_id' => $this->businessId,
            'business_name' => $this->businessName,
            'branch_id' => $this->branchId,
            'branch_name' => $this->branchName,
            'event_key' => $this->eventKey,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
        ];
    }
}
