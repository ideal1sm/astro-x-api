<?php

namespace App\Events;

use App\Models\ShopOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShopOrderCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly ShopOrder $order) {}
}
