<?php

declare(strict_types=1);

namespace App\Domain\Company;

enum JobBoardProvider: string
{
    case Workable = 'workable';
    case Lever = 'lever';
    case Teamtailor = 'teamtailor';
    case Factorial = 'factorial';
    case Ashby = 'ashby';
    case Greenhouse = 'greenhouse';
}
