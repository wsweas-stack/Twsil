<?php

namespace App\Enums;

enum UserRoles: string
{
    case Admin = 'admin';
    case Driver = 'driver';
    case Employee = 'employee';
    case Customer = 'customer';
}
