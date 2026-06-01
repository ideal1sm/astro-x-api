<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Новый заказ Мёд #{{ $order->id }}</title>
</head>
<body style="margin:0;padding:24px;background:#f8fafc;color:#0f172a;font-family:Arial,sans-serif;">
<div style="max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
    <div style="padding:24px 24px 16px;border-bottom:1px solid #e2e8f0;background:#fff7ed;">
        <h1 style="margin:0 0 8px;font-size:24px;line-height:1.2;">Новый заказ Мёд #{{ $order->id }}</h1>
        <p style="margin:0;font-size:14px;color:#475569;">
            Оформлен {{ $order->created_at?->format('d.m.Y H:i') }} |
            Статус заказа: {{ $order->status?->label() ?? $order->status }} |
            Статус оплаты: {{ $order->payment_status?->label() ?? '—' }}
        </p>
    </div>

    <div style="padding:24px;">
        <p style="margin:0 0 16px;">
            <a href="{{ $adminOrderUrl }}" style="display:inline-block;padding:12px 16px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:10px;">
                Открыть заказ в админке
            </a>
        </p>

        <h2 style="margin:24px 0 12px;font-size:18px;">Покупатель</h2>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <tr><td style="padding:6px 0;width:220px;color:#475569;">Имя</td><td style="padding:6px 0;">{{ $order->customer_name }}</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">Телефон</td><td style="padding:6px 0;">{{ $order->customer_phone }}</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">Email</td><td style="padding:6px 0;">{{ $order->customer_email }}</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">Тип заказа</td><td style="padding:6px 0;">{{ $order->user_id ? 'Пользователь с аккаунтом' : 'Гостевой заказ' }}</td></tr>
        </table>

        <h2 style="margin:24px 0 12px;font-size:18px;">Оплата</h2>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <tr><td style="padding:6px 0;width:220px;color:#475569;">Способ оплаты</td><td style="padding:6px 0;">{{ $order->payment_method?->label() ?? '—' }}</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">Статус оплаты</td><td style="padding:6px 0;">{{ $order->payment_status?->label() ?? '—' }}</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">Сумма оплаты</td><td style="padding:6px 0;">{{ number_format((float) ($order->payment_amount ?? $order->total), 2, '.', ' ') }} ₽</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">ID платежа</td><td style="padding:6px 0;">{{ $order->yookassa_payment_id ?: '—' }}</td></tr>
        </table>

        <h2 style="margin:24px 0 12px;font-size:18px;">Доставка</h2>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <tr><td style="padding:6px 0;width:220px;color:#475569;">Способ доставки</td><td style="padding:6px 0;">{{ $order->delivery_method }}</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">Стоимость доставки</td><td style="padding:6px 0;">{{ number_format((float) $order->delivery_price, 2, '.', ' ') }} ₽</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">Город</td><td style="padding:6px 0;">{{ $order->delivery_city }}</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">Получатель</td><td style="padding:6px 0;">{{ $order->recipient_name }}</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">Телефон получателя</td><td style="padding:6px 0;">{{ $order->recipient_phone }}</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">Адрес</td><td style="padding:6px 0;">{{ $order->delivery_address ?: '—' }}</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">ПВЗ</td><td style="padding:6px 0;">{{ $order->delivery_pickup_point ?: '—' }}</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">Комментарий по доставке</td><td style="padding:6px 0;">{{ $order->delivery_comment ?: '—' }}</td></tr>
        </table>

        <h2 style="margin:24px 0 12px;font-size:18px;">Состав заказа</h2>
        @foreach ($order->items as $item)
            @php($imageUrl = $item->product?->primary_image_url)
            <div style="padding:16px 0;border-top:1px solid #e2e8f0;">
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="width:180px;vertical-align:top;padding-right:16px;">
                            @if ($imageUrl)
                                <img src="{{ $imageUrl }}" alt="{{ $item->product?->name }}" style="display:block;width:160px;max-width:100%;border-radius:12px;border:1px solid #e2e8f0;">
                            @else
                                <div style="width:160px;height:160px;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc;color:#64748b;font-size:13px;display:flex;align-items:center;justify-content:center;text-align:center;padding:12px;box-sizing:border-box;">
                                    Изображение не загружено
                                </div>
                            @endif
                        </td>
                        <td style="vertical-align:top;font-size:14px;">
                            <div style="font-size:17px;font-weight:700;margin-bottom:8px;">{{ $item->product?->name ?? 'Товар удалён' }}</div>
                            <div style="margin-bottom:6px;color:#475569;">Категория: {{ $item->product?->category?->name ?? '—' }}</div>
                            <div style="margin-bottom:6px;color:#475569;">Количество: {{ $item->quantity }}</div>
                            <div style="margin-bottom:6px;color:#475569;">Цена: {{ number_format((float) $item->price, 2, '.', ' ') }} ₽</div>
                            <div style="font-weight:700;">Итого: {{ number_format((float) $item->total, 2, '.', ' ') }} ₽</div>
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach

        <h2 style="margin:24px 0 12px;font-size:18px;">Суммы и комментарий</h2>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <tr><td style="padding:6px 0;width:220px;color:#475569;">Сумма товаров</td><td style="padding:6px 0;">{{ number_format((float) $order->items_total, 2, '.', ' ') }} ₽</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">Доставка</td><td style="padding:6px 0;">{{ number_format((float) $order->delivery_price, 2, '.', ' ') }} ₽</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">Итог</td><td style="padding:6px 0;font-weight:700;">{{ number_format((float) $order->total, 2, '.', ' ') }} ₽</td></tr>
            <tr><td style="padding:6px 0;color:#475569;">Комментарий покупателя</td><td style="padding:6px 0;">{{ $order->notes ?: '—' }}</td></tr>
        </table>
    </div>
</div>
</body>
</html>
