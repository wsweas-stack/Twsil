<?php

namespace App\Enums;

enum CommissionStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
}
