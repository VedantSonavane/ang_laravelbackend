<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'TODO';
    case InProgress = 'IN_PROGRESS';
    case Done = 'DONE';
    case Overdue = 'OVERDUE';

    /** Statuses a person may choose. OVERDUE is only ever set by the Django service. */
    public static function assignable(): array
    {
        return [self::Todo->value, self::InProgress->value, self::Done->value];
    }
}
