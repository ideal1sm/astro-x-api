<?php

namespace App\Mail;

use App\Filament\Resources\ShopOrders\ShopOrderResource;
use App\Models\ShopOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShopOrderCreatedManagerMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly ShopOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Новый заказ Мёд #{$this->order->id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.shop-order-created-manager',
            with: [
                'order' => $this->order,
                'adminOrderUrl' => ShopOrderResource::getUrl('edit', ['record' => $this->order], true, 'admin'),
            ],
        );
    }
}
