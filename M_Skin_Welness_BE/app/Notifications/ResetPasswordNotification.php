<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);

        $minutes = (int) config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Recupera tu contraseña en MSkinWellness')
            ->greeting('Hola '.$notifiable->name.',')
            ->line('Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en MSkinWellness.')
            ->action('Establecer una contraseña nueva', $url)
            ->line('Este enlace caduca en '.$minutes.' minutos.')
            ->line('Si no has solicitado este cambio, puedes ignorar este correo.')
            ->salutation('Un saludo, el equipo de MSkinWellness');
    }
}
