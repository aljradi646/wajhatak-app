<?php

namespace App\Enums;

enum TransactionType: string
{
    case Sale = 'sale';
    case Rent = 'rent';
}
