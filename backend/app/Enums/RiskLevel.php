<?php

namespace App\Enums;

enum RiskLevel: string
{
    case Critical = 'CRITICAL';
    case High = 'HIGH';
    case Medium = 'MEDIUM';
    case Low = 'LOW';
}
