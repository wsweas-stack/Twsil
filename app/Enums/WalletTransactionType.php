<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case Commission = 'commission';
    case Payout = 'payout';
}
