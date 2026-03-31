<?php

declare(strict_types=1);

namespace App\Domain\Company;

enum JobBoardProvider: string
{
    case Workable = 'workable';
}
