<?php

namespace App\Enums;

enum ActivityEventAction: string
{
    case Login = 'login';
    case Logout = 'logout';
    case Registration = 'registration';
    case PageA = 'page-a';
    case PageB = 'page-b';
    case PageStats = 'page-stats';
    case PageReports = 'page-reports';
    case BuyCow = 'buy-cow';
    case Download = 'download';
}
