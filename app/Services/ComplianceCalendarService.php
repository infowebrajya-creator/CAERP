<?php

namespace App\Services;

use App\Models\ComplianceDeadline;
use App\Models\ComplianceRule;
use Carbon\Carbon;

/**
 * Generates configurable compliance deadlines from ComplianceRule templates without hardcoded statutory dates.
 */
class ComplianceCalendarService
{
    public function generateFromRule(ComplianceRule $rule, int $year, ?int $month = null, ?int $clientId = null, ?int $assignedTo = null): ComplianceDeadline
    {
        $baseMonth = $rule->due_month ?? $month ?? now()->month;
        $targetDate = Carbon::create($year, $baseMonth, 1)->addMonths($rule->month_offset);
        $day = min($rule->due_day, $targetDate->daysInMonth);
        $dueDate = $targetDate->copy()->day($day);
        $reminderDate = $dueDate->copy()->subDays($rule->reminder_days_before);

        return ComplianceDeadline::create([
            'title' => $rule->name.' ('.$dueDate->format('M Y').')',
            'compliance_type' => $rule->compliance_type,
            'client_id' => $clientId,
            'assigned_to' => $assignedTo,
            'compliance_rule_id' => $rule->id,
            'due_date' => $dueDate,
            'reminder_date' => $reminderDate,
            'status' => $dueDate->isPast() ? 'overdue' : 'upcoming',
            'priority' => 'medium',
            'description' => $rule->description,
        ]);
    }
}

