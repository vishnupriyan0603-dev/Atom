<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Atom\MyLife\Contract\MyLifeModuleEnum;
use Atom\MyLife\Contract\MyLifeResponse;
use Atom\MyLife\Contract\MyLifeSummaryDto;

class MyLifeContractsTest extends TestCase
{
    public function testFourteenModulesEnumDefinitions(): void
    {
        $cases = MyLifeModuleEnum::cases();
        $this->assertCount(14, $cases, 'MyLife OS must define exactly 14 modules');

        $expectedKeys = [
            '01_identity', '02_finance', '03_health', '04_wellbeing',
            '05_location', '06_activities', '07_habits', '08_learning',
            '09_career', '10_social', '11_nutrition', '12_digital',
            '13_documents', '14_ai_insights'
        ];

        foreach ($cases as $case) {
            $this->assertContains($case->value, $expectedKeys);
            $this->assertNotEmpty($case->getDisplayName());
        }
    }

    public function testResponseEnvelopeSuccessAndFailure(): void
    {
        $success = MyLifeResponse::success(['status' => 'active'], 14.2, 'Samsung Galaxy M31');
        $this->assertTrue($success->isSuccess());
        $this->assertEquals(200, $success->getStatusCode());
        $this->assertEquals(14.2, $success->getLatencyMs());
        $this->assertEquals('Samsung Galaxy M31', $success->getDeviceInfo());
        $this->assertNull($success->getError());
        $this->assertEquals('active', $success->getData()['status']);

        $failure = MyLifeResponse::failure('Connection timed out', 504, 3000.0);
        $this->assertFalse($failure->isSuccess());
        $this->assertEquals(504, $failure->getStatusCode());
        $this->assertEquals('Connection timed out', $failure->getError());
        $this->assertEmpty($failure->getData());
    }

    public function testSummaryDtoTransformation(): void
    {
        $raw = [
            'identity' => ['preferred_name' => 'Vishnupriyan'],
            'finance' => ['total_balance' => 45000.50, 'month_expenses' => 12500.0],
            'health' => ['today_steps' => 6420, 'today_water_ml' => 2200],
            'wellbeing' => ['primary_emotion' => 'Calm'],
            'activities' => ['pending_tasks_count' => 3],
            'habits' => ['longest_active_streak' => 14],
            'learning' => ['study_hours_today' => 3.5],
            'digital' => ['screen_time_minutes' => 185],
            'device' => ['battery_level' => 88, 'is_charging' => true, 'online' => true],
            'synced_at' => '2026-11-06 01:30:00'
        ];

        $dto = MyLifeSummaryDto::fromArray($raw);

        $this->assertEquals('Vishnupriyan', $dto->ownerName);
        $this->assertEquals(45000.50, $dto->currentCashBalance);
        $this->assertEquals(12500.0, $dto->monthTotalExpenses);
        $this->assertEquals(6420, $dto->todayStepCount);
        $this->assertEquals(2200, $dto->todayWaterIntakeMl);
        $this->assertEquals('Calm', $dto->currentMood);
        $this->assertEquals(3, $dto->pendingTasksCount);
        $this->assertEquals(14, $dto->activeHabitsStreakMax);
        $this->assertEquals(3.5, $dto->gateStudyHoursToday);
        $this->assertEquals(185, $dto->screenTimeMinutesToday);
        $this->assertEquals(88, $dto->batteryLevelPercent);
        $this->assertTrue($dto->isDeviceCharging);
        $this->assertTrue($dto->isPhoneOnline);

        $array = $dto->toArray();
        $this->assertArrayHasKey('current_cash_balance', $array);
        $this->assertEquals(45000.50, $array['current_cash_balance']);
    }

    public function testDatabaseDdlExecutionAndCrud(): void
    {
        $schemaPath = dirname(__DIR__, 3) . '/database/mylife_schema.sql';
        $this->assertFileExists($schemaPath);

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = file_get_contents($schemaPath);
        $this->assertNotEmpty($sql);
        $pdo->exec($sql);

        // Verify 39 tables created
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name != 'sqlite_sequence'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $this->assertCount(39, $tables);

        // Test Module 01 (Identity) CRUD
        $pdo->prepare("INSERT INTO life_identity_profile (full_name, preferred_name) VALUES (?, ?)")
            ->execute(['Vishnupriyan R', 'Vishnu']);
        $profile = $pdo->query("SELECT preferred_name FROM life_identity_profile LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('Vishnu', $profile['preferred_name']);

        // Test Module 02 (Finance) CRUD
        $pdo->prepare("INSERT INTO life_accounts (account_name, account_type, balance) VALUES (?, ?, ?)")
            ->execute(['Savings Bank', 'SAVINGS', 25000.00]);
        $accountId = (int)$pdo->lastInsertId();

        $pdo->prepare("INSERT INTO life_transactions (account_id, type, category, amount, transaction_date) VALUES (?, ?, ?, ?, ?)")
            ->execute([$accountId, 'EXPENSE', 'Food', 350.0, date('Y-m-d H:i:s')]);

        $trans = $pdo->query("SELECT category, amount FROM life_transactions WHERE account_id = {$accountId}")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('Food', $trans['category']);
        $this->assertEquals(350.0, (float)$trans['amount']);

        // Test Module 07 (Habits)
        $pdo->prepare("INSERT INTO life_habits (name, frequency) VALUES (?, ?)")
            ->execute(['Drink 3L Water', 'DAILY']);
        $habitId = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO life_habit_logs (habit_id, log_date) VALUES (?, ?)")
            ->execute([$habitId, date('Y-m-d')]);

        $habitLog = $pdo->query("SELECT status FROM life_habit_logs WHERE habit_id = {$habitId}")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('COMPLETED', $habitLog['status']);
    }
}
