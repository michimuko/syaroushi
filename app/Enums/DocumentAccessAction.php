<?php

namespace App\Enums;

enum DocumentAccessAction: string
{
    case View = 'view';
    case Download = 'download';
}
