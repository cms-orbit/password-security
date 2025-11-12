<?php

namespace CmsOrbit\PasswordSecurity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordExpirationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $daysRemaining;
    protected bool $isExpired;

    /**
     * Create a new notification instance.
     */
    public function __construct(int $daysRemaining, bool $isExpired = false)
    {
        $this->daysRemaining = $daysRemaining;
        $this->isExpired = $isExpired;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return config('password-security.notifications.channels', ['mail']);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = config('password-security.notifications.expiration.mail_subject', 'Password Expiration Notice');

        if ($this->isExpired) {
            return (new MailMessage)
                ->subject($subject)
                ->line(__('password-security::notifications.password_expired_line1'))
                ->line(__('password-security::notifications.password_expired_line2'))
                ->action(__('password-security::notifications.change_password'), $this->getPasswordChangeUrl())
                ->line(__('password-security::notifications.password_expired_line3'));
        }

        return (new MailMessage)
            ->subject($subject)
            ->line(__('password-security::notifications.password_expiring_line1', ['days' => $this->daysRemaining]))
            ->line(__('password-security::notifications.password_expiring_line2'))
            ->action(__('password-security::notifications.change_password'), $this->getPasswordChangeUrl())
            ->line(__('password-security::notifications.password_expiring_line3'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'days_remaining' => $this->daysRemaining,
            'is_expired' => $this->isExpired,
            'message' => $this->isExpired 
                ? __('password-security::notifications.password_expired_line1')
                : __('password-security::notifications.password_expiring_line1', ['days' => $this->daysRemaining]),
        ];
    }

    /**
     * 패스워드 변경 URL 가져오기
     */
    protected function getPasswordChangeUrl(): string
    {
        $route = config('password-security.expiration.force_change_route');
        if ($route && \Route::has($route)) {
            return route($route);
        }

        return url(config('password-security.expiration.force_change_url', '/password/change'));
    }
}

