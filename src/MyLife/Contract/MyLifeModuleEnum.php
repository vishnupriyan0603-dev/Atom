<?php

declare(strict_types=1);

namespace Atom\MyLife\Contract;

/**
 * Enumeration of the 14 core life-management modules within MyLife OS.
 */
enum MyLifeModuleEnum: string
{
    case IDENTITY    = '01_identity';
    case FINANCE     = '02_finance';
    case HEALTH      = '03_health';
    case WELLBEING   = '04_wellbeing';
    case LOCATION    = '05_location';
    case ACTIVITIES  = '06_activities';
    case HABITS      = '07_habits';
    case LEARNING    = '08_learning';
    case CAREER      = '09_career';
    case SOCIAL      = '10_social';
    case NUTRITION   = '11_nutrition';
    case DIGITAL     = '12_digital';
    case DOCUMENTS   = '13_documents';
    case AI_INSIGHTS = '14_ai_insights';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::IDENTITY    => 'Personal Identity',
            self::FINANCE     => 'Finance & Accounts',
            self::HEALTH      => 'Physical Health & Vitals',
            self::WELLBEING   => 'Mental Wellbeing & Journal',
            self::LOCATION    => 'Location & Travel',
            self::ACTIVITIES  => 'Daily Activities & Routines',
            self::HABITS      => 'Habit Tracker',
            self::LEARNING    => 'Education & Learning (GATE / Tech)',
            self::CAREER      => 'Career & Professional Life',
            self::SOCIAL      => 'Social Life & Relationships',
            self::NUTRITION   => 'Food & Nutrition',
            self::DIGITAL     => 'Digital Life & Screen Time',
            self::DOCUMENTS   => 'Personal Documents Vault',
            self::AI_INSIGHTS => 'AI Personal Intelligence',
        };
    }
}

