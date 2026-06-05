<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionInvoiceNotification extends Notification
{
    public function __construct(
        private readonly string $pdf,
        private readonly string $invoiceNumber,
        private readonly string $issuedDate,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu factura de MSkinWellness')
            ->greeting('Hola '.$notifiable->name.',')
            ->line('Adjuntamos la factura de tu suscripción a MSkinWellness.')
            ->line('Número de factura: '.$this->invoiceNumber)
            ->line('Fecha de emisión: '.$this->issuedDate)
            ->line('Gracias por confiar en nosotros.')
            ->salutation('Un saludo, el equipo de MSkinWellness')
            ->attachData($this->pdf, 'factura-'.$this->invoiceNumber.'.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
