<?php

use App\Models\Payments\Paycom;
use App\Models\Payments\Click;

// Generate link for payment
$url = Paycom::checkout($order->id, $amount);
$url = Click::checkout($order->id, $amount);
