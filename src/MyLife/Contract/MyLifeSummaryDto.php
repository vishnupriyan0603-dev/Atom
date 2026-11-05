<?php

declare(strict_types=1);

namespace Atom\MyLife\Contract;

/**
 * Consolidated high-level executive Data Transfer Object for all 14 life modules.
 */
class MyLifeSummaryDto
{
    public function __construct(
        public readonly string $ownerName,
        public readonly float $currentCashBalance,
        public readonly float $monthTotalExpenses,
        public readonly int $todayStepCount,
        public readonly int $todayWaterIntakeMl,
        public readonly string $currentMood,
        public readonly int $pendingTasksCount,
        public readonly int $activeHabitsStreakMax,
        public readonly float $gateStudyHoursToday,
        public readonly int $screenTimeMinutesToday,
        public readonly int $batteryLevelPercent,
        public readonly bool $isDeviceCharging,
        public readonly bool $isPhoneOnline,
        public readonly string $lastSyncedAt
    ) {}

    public static function fromArray(array $raw): self
    {
        return new self(
            ownerName: (string)($raw['identity']['preferred_name'] ?? 'Vishnupriyan'),
            currentCashBalance: (float)($raw['finance']['total_balance'] ?? 0.0),
            monthTotalExpenses: (float)($raw['finance']['month_expenses'] ?? 0.0),
            todayStepCount: (int)($raw['health']['today_steps'] ?? 0),
            todayWaterIntakeMl: (int)($raw['health']['today_water_ml'] ?? 0),
            currentMood: (string)($raw['wellbeing']['primary_emotion'] ?? 'Focused'),
            pendingTasksCount: (int)($raw['activities']['pending_tasks_count'] ?? 0),
            activeHabitsStreakMax: (int)($raw['habits']['longest_active_streak'] ?? 0),
            gateStudyHoursToday: (float)($raw['learning']['study_hours_today'] ?? 0.0),
            screenTimeMinutesToday: (int)($raw['digital']['screen_time_minutes'] ?? 0),
            batteryLevelPercent: (int)($raw['device']['battery_level'] ?? 100),
            isDeviceCharging: (bool)($raw['device']['is_charging'] ?? false),
            isPhoneOnline: (bool)($raw['device']['online'] ?? true),
            lastSyncedAt: (string)($raw['synced_at'] ?? date('Y-m-d H:i:s'))
        );
    }

    public function toArray(): array
    {
        return [
            'owner_name'               => $this->ownerName,
            'current_cash_balance'     => $this->currentCashBalance,
            'month_total_expenses'     => $this->monthTotalExpenses,
            'today_step_count'         => $this->todayStepCount,
            'today_water_intake_ml'    => $this->todayWaterIntakeMl,
            'current_mood'             => $this->currentMood,
            'pending_tasks_count'      => $this->pendingTasksCount,
            'active_habits_streak_max' => $this->activeHabitsStreakMax,
            'gate_study_hours_today'   => $this->gateStudyHoursToday,
            'screen_time_minutes_today'=> $this->screenTimeMinutesToday,
            'battery_level_percent'    => $this->batteryLevelPercent,
            'is_device_charging'       => $this->isDeviceCharging,
            'is_phone_online'          => $this->isPhoneOnline,
            'last_synced_at'           => $this->lastSyncedAt,
        ];
    }
}
