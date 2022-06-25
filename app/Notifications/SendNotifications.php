<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendNotifications extends Notification
{
    use Queueable;

    /**
     * @var string
     */
    public $subject;
    public $pathTemplate;

    /**
     * @var array
     */
    public $assocKeyData;
    public $pathAttachmentFileArray;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($subject, $pathTemplate, $assocKeyData = [], $pathAttachmentFileArray = [])
    {
        $this->subject = $subject;
        $this->pathTemplate = $pathTemplate;
        $this->assocKeyData = $assocKeyData;
        $this->pathAttachmentFileArray = $pathAttachmentFileArray;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = (new MailMessage)
            ->subject($this->subject)
            ->markdown($this->pathTemplate, $this->assocKeyData);

        foreach ($this->pathAttachmentFileArray as $pathAttachmentFile) {
            $message->attach($pathAttachmentFile); // attach each file
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //

        ];
    }
}
