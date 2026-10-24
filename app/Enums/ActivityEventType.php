<?php

namespace App\Enums;

enum ActivityEventType: string
{
    case Authentication = 'authentication';
    case ViewPage = 'view-page';
    case ButtonClick = 'button-click';
}
