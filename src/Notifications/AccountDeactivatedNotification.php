<?php

namespace CmsOrbit\PasswordSecurity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeactivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $daysRemaining;
    protected bool $isDeactivated;
    protected int $inactiveDays;

    /**
     * Create a new notification instance.
     */
    public function __construct(int $daysRemaining, bool $isDeactivated = false, int $inactiveDays = 90)
    {
        $this->daysRemaining = $daysRemaining;
        $this->isDeactivated = $isDeactivated;
        $this->inactiveDays = $inactiveDays;
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
        $subject = config('password-security.notifications.inactive_account.mail_subject', 'Account Inactivity Notice');

        if ($this->isDeactivated) {
            return (new MailMessage)
                ->subject($subject)
                ->line(__('password-security::notifications.account_deactivated_line1'))
                ->line(__('password-security::notifications.account_deactivated_line2', ['days' => $this->inactiveDays]))
                ->line(__('password-security::notifications.account_deactivated_line3'))
                ->action(__('password-security::notifications.contact_admin'), url('/'));
        }

        return (new MailMessage)
            ->subject($subject)
            ->line(__('password-security::notifications.account_warning_line1', ['days' => $this->daysRemaining]))
            ->line(__('password-security::notifications.account_warning_line2', ['total_days' => $this->inactiveDays]))
            ->line(__('password-security::notifications.account_warning_line3'))
            ->action(__('password-security::notifications.login_now'), route('login'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'days_remaining' => $this->daysRemaining,
            'is_deactivated' => $this->isDeactivated,
            'inactive_days' => $this->inactiveDays,
            'message' => $this->isDeactivated 
                ? __('password-security::notifications.account_deactivated_line1')
                : __('password-security::notifications.account_warning_line1', ['days' => $this->daysRemaining]),
        ];
    }
}

