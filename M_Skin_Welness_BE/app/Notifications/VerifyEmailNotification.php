<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirma tu correo en MSkinWellness')
            ->greeting('Hola '.$notifiable->name.',')
            ->line('Para activar tu cuenta en MSkinWellness y poder acceder al panel necesitamos verificar tu correo.')
            ->action('Confirmar mi correo', $url)
            ->line('Si no has creado ninguna cuenta puedes ignorar este mensaje.')
            ->salutation('Un saludo, el equipo de MSkinWellness');
    }
}
