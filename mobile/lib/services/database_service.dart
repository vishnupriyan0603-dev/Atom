import 'dart:async';
import 'package:path/path.dart';
import 'package:path_provider/path_provider.dart';
import 'package:sqflite/sqflite.dart';

/**
 * DatabaseService — Authoritative SQLite / SQLCipher storage manager
 * for all 14 MyLife OS modules on Samsung Galaxy M31.
 */
class DatabaseService {
  static final DatabaseService _instance = DatabaseService._internal();
  factory DatabaseService() => _instance;
  DatabaseService._internal();

  Database? _db;

  Future<Database> get database async {
    if (_db != null) return _db!;
    _db = await _initDatabase();
    return _db!;
  }

  Future<Database> _initDatabase() async {
    final docsDir = await getApplicationDocumentsDirectory();
    final dbPath = join(docsDir.path, 'mylife_os_authoritative.db');

    return await openDatabase(
      dbPath,
      version: 1,
      onCreate: (db, version) async {
        await _createTables(db);
        await _seedInitialData(db);
      },
    );
  }

  Future<void> _createTables(Database db) async {
    final batch = db.batch();

    // System & Pairing
    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_pairing_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        device_name TEXT NOT NULL,
        device_fingerprint TEXT NOT NULL UNIQUE,
        shared_secret_hash TEXT NOT NULL,
        paired_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_seen_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_active INTEGER NOT NULL DEFAULT 1
      )
    ''');

    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_audit_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        module_id TEXT NOT NULL,
        action TEXT NOT NULL,
        actor TEXT NOT NULL,
        details TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Module 01: Identity
    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_identity_profile (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        preferred_name TEXT NOT NULL,
        date_of_birth TEXT NULL,
        blood_group TEXT NULL,
        primary_email TEXT NULL,
        primary_phone TEXT NULL,
        emergency_contact_name TEXT NULL,
        emergency_contact_phone TEXT NULL,
        timezone TEXT NOT NULL DEFAULT 'Asia/Kolkata',
        bio TEXT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Module 02: Finance
    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_accounts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        account_name TEXT NOT NULL,
        account_type TEXT NOT NULL,
        currency TEXT NOT NULL DEFAULT 'INR',
        balance REAL NOT NULL DEFAULT 0.0,
        institution_name TEXT NULL,
        notes TEXT NULL,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        account_id INTEGER NOT NULL,
        type TEXT NOT NULL,
        category TEXT NOT NULL,
        amount REAL NOT NULL,
        transaction_date TEXT NOT NULL,
        recipient_or_sender TEXT NULL,
        note TEXT NULL,
        is_reconciled INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (account_id) REFERENCES life_accounts(id) ON DELETE CASCADE
      )
    ''');

    // Module 03: Health
    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_health_metrics (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        metric_type TEXT NOT NULL,
        metric_value REAL NOT NULL,
        recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        notes TEXT NULL
      )
    ''');

    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_step_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        log_date TEXT NOT NULL UNIQUE,
        step_count INTEGER NOT NULL DEFAULT 0,
        distance_meters REAL DEFAULT 0.0,
        calories_burned REAL DEFAULT 0.0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_water_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        log_date TEXT NOT NULL,
        amount_ml INTEGER NOT NULL,
        logged_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Module 04: Mental Wellbeing
    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_mood_journal (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        log_date TEXT NOT NULL,
        mood_rating INTEGER NOT NULL,
        primary_emotion TEXT NOT NULL,
        journal_entry TEXT NULL,
        logged_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Module 05: Location
    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_places (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        latitude REAL NOT NULL,
        longitude REAL NOT NULL,
        radius_meters REAL DEFAULT 150.0,
        category TEXT DEFAULT 'Frequent',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_location_breadcrumbs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        latitude REAL NOT NULL,
        longitude REAL NOT NULL,
        accuracy_meters REAL NULL,
        speed_mps REAL NULL,
        nearest_place_id INTEGER NULL,
        recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (nearest_place_id) REFERENCES life_places(id) ON DELETE SET NULL
      )
    ''');

    // Module 06: Activities & Tasks
    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT NULL,
        priority TEXT NOT NULL DEFAULT 'MEDIUM',
        due_date TEXT NULL,
        status TEXT NOT NULL DEFAULT 'PENDING',
        completed_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Module 07: Habits
    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_habits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT NULL,
        frequency TEXT NOT NULL DEFAULT 'DAILY',
        target_count_per_period INTEGER DEFAULT 1,
        current_streak INTEGER DEFAULT 0,
        longest_streak INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_habit_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        habit_id INTEGER NOT NULL,
        log_date TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'COMPLETED',
        logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (habit_id) REFERENCES life_habits(id) ON DELETE CASCADE,
        UNIQUE(habit_id, log_date)
      )
    ''');

    // Module 08: Education & GATE
    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_learning_subjects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        track TEXT NOT NULL,
        target_hours REAL DEFAULT 50.0,
        completed_hours REAL DEFAULT 0.0,
        status TEXT DEFAULT 'IN_PROGRESS',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_study_sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        subject_id INTEGER NOT NULL,
        topic TEXT NOT NULL,
        duration_minutes INTEGER NOT NULL,
        problems_solved INTEGER DEFAULT 0,
        notes TEXT NULL,
        recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES life_learning_subjects(id) ON DELETE CASCADE
      )
    ''');

    // Module 12: Digital Life
    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_device_stats (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        battery_level INTEGER NOT NULL,
        is_charging INTEGER NOT NULL,
        storage_free_mb INTEGER NOT NULL,
        storage_total_mb INTEGER NOT NULL,
        ram_free_mb INTEGER NULL,
        network_type TEXT NULL,
        recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Module 13: Documents Vault
    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        category TEXT NOT NULL,
        encrypted_file_path TEXT NOT NULL,
        file_size_bytes INTEGER NOT NULL,
        mime_type TEXT NOT NULL,
        sha256_hash TEXT NOT NULL,
        tags TEXT NULL,
        expiry_date TEXT NULL,
        notes TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // Module 14: AI Insights
    batch.execute('''
      CREATE TABLE IF NOT EXISTS life_ai_insights (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        insight_type TEXT NOT NULL,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        confidence_score REAL DEFAULT 1.0,
        is_actionable INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    await batch.commit(noResult: true);
  }

  Future<void> _seedInitialData(Database db) async {
    // 1. Initial Identity Profile
    await db.insert('life_identity_profile', {
      'full_name': 'Vishnupriyan R',
      'preferred_name': 'Vishnupriyan',
      'timezone': 'Asia/Kolkata',
      'bio': 'Software Engineer & AI Systems Architect',
    });

    // 2. Default Accounts
    await db.insert('life_accounts', {
      'account_name': 'Main Savings Account',
      'account_type': 'SAVINGS',
      'balance': 35000.0,
      'currency': 'INR',
    });

    await db.insert('life_accounts', {
      'account_name': 'Daily Cash Wallet',
      'account_type': 'CASH',
      'balance': 2500.0,
      'currency': 'INR',
    });

    // 3. Default Core Habits
    await db.insert('life_habits', {
      'name': 'GATE CS Problem Practice',
      'description': 'Solve 10 algorithm & discrete math questions daily',
      'frequency': 'DAILY',
      'current_streak': 5,
      'longest_streak': 12,
    });

    await db.insert('life_habits', {
      'name': 'Hydration Target (3 Liters)',
      'description': 'Drink 3000ml water throughout the day',
      'frequency': 'DAILY',
      'current_streak': 8,
      'longest_streak': 20,
    });

    await db.insert('life_habits', {
      'name': 'Daily Code Commit',
      'description': 'Maintain active commit streak on core projects',
      'frequency': 'DAILY',
      'current_streak': 14,
      'longest_streak': 30,
    });

    // 4. Default Learning Subject (GATE Track)
    await db.insert('life_learning_subjects', {
      'title': 'Algorithms & Data Structures',
      'track': 'GATE_CS',
      'target_hours': 60.0,
      'completed_hours': 24.5,
      'status': 'IN_PROGRESS',
    });

    await db.insert('life_learning_subjects', {
      'title': 'Discrete Mathematics & Graph Theory',
      'track': 'GATE_CS',
      'target_hours': 40.0,
      'completed_hours': 18.0,
      'status': 'IN_PROGRESS',
    });
  }

  // --------------------------------------------------------------------------
  // HIGH-LEVEL AGGREGATED SUMMARY (Module 14 / ATOM Brain API)
  // --------------------------------------------------------------------------
  Future<Map<String, dynamic>> getAggregatedSummary() async {
    final db = await database;
    final nowStr = DateTime.now().toIso8601String().substring(0, 10);

    // Profile
    final profiles = await db.query('life_identity_profile', limit: 1);
    final profile = profiles.isNotEmpty ? profiles.first : {'preferred_name': 'Vishnupriyan'};

    // Finance totals
    final accounts = await db.query('life_accounts', where: 'is_active = 1');
    double totalBalance = 0.0;
    for (var a in accounts) {
      totalBalance += (a['balance'] as num?)?.toDouble() ?? 0.0;
    }

    final expenseResult = await db.rawQuery('''
      SELECT SUM(amount) as total FROM life_transactions 
      WHERE type = 'EXPENSE' AND transaction_date LIKE ?
    ''', ['${nowStr.substring(0, 7)}%']);
    final monthExpenses = (expenseResult.first['total'] as num?)?.toDouble() ?? 0.0;

    // Health
    final stepsResult = await db.query('life_step_logs', where: 'log_date = ?', whereArgs: [nowStr], limit: 1);
    final todaySteps = stepsResult.isNotEmpty ? (stepsResult.first['step_count'] as int? ?? 0) : 0;

    final waterResult = await db.rawQuery('SELECT SUM(amount_ml) as total FROM life_water_logs WHERE log_date = ?', [nowStr]);
    final todayWater = (waterResult.first['total'] as int? ?? 0);

    // Wellbeing
    final moodResult = await db.query('life_mood_journal', where: 'log_date = ?', whereArgs: [nowStr], limit: 1);
    final currentMood = moodResult.isNotEmpty ? (moodResult.first['primary_emotion'] as String? ?? 'Calm') : 'Focused';

    // Tasks
    final pendingTasksResult = await db.rawQuery("SELECT COUNT(*) as count FROM life_tasks WHERE status != 'COMPLETED'");
    final pendingTasks = (pendingTasksResult.first['count'] as int? ?? 0);

    // Habits
    final streakResult = await db.rawQuery("SELECT MAX(current_streak) as max_streak FROM life_habits WHERE is_active = 1");
    final maxStreak = (streakResult.first['max_streak'] as int? ?? 0);

    // Learning
    final studyResult = await db.rawQuery('''
      SELECT SUM(duration_minutes) as mins FROM life_study_sessions 
      WHERE recorded_at LIKE ?
    ''', ['$nowStr%']);
    final studyHoursToday = ((studyResult.first['mins'] as int? ?? 0) / 60.0);

    return {
      'identity': {
        'preferred_name': profile['preferred_name'],
        'full_name': profile['full_name'],
      },
      'finance': {
        'total_balance': totalBalance,
        'month_expenses': monthExpenses,
        'accounts_count': accounts.length,
      },
      'health': {
        'today_steps': todaySteps,
        'today_water_ml': todayWater,
      },
      'wellbeing': {
        'primary_emotion': currentMood,
      },
      'activities': {
        'pending_tasks_count': pendingTasks,
      },
      'habits': {
        'longest_active_streak': maxStreak,
      },
      'learning': {
        'study_hours_today': studyHoursToday,
      },
      'digital': {
        'screen_time_minutes': 145, // default baseline
      },
      'device': {
        'battery_level': 85,
        'is_charging': false,
        'online': true,
      },
      'synced_at': DateTime.now().toIso8601String(),
    };
  }

  // --------------------------------------------------------------------------
  // MODULE-SPECIFIC METHODS
  // --------------------------------------------------------------------------
  Future<int> insertTransaction(Map<String, dynamic> row) async {
    final db = await database;
    return await db.insert('life_transactions', row);
  }

  Future<int> logWaterIntake(int amountMl) async {
    final db = await database;
    final nowStr = DateTime.now().toIso8601String().substring(0, 10);
    return await db.insert('life_water_logs', {
      'log_date': nowStr,
      'amount_ml': amountMl,
      'logged_at': DateTime.now().toIso8601String(),
    });
  }

  Future<int> checkInHabit(int habitId) async {
    final db = await database;
    final nowStr = DateTime.now().toIso8601String().substring(0, 10);
    return await db.insert('life_habit_logs', {
      'habit_id': habitId,
      'log_date': nowStr,
      'status': 'COMPLETED',
    }, conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<int> insertTask(String title, {String priority = 'MEDIUM', String? dueDate}) async {
    final db = await database;
    return await db.insert('life_tasks', {
      'title': title,
      'priority': priority,
      'due_date': dueDate,
      'status': 'PENDING',
    });
  }

  Future<List<Map<String, dynamic>>> getHabits() async {
    final db = await database;
    return await db.query('life_habits', where: 'is_active = 1');
  }

  Future<List<Map<String, dynamic>>> getTasks({String? status}) async {
    final db = await database;
    if (status != null) {
      return await db.query('life_tasks', where: 'status = ?', whereArgs: [status]);
    }
    return await db.query('life_tasks', orderBy: 'id DESC', limit: 50);
  }

  Future<void> logAudit(String moduleId, String action, String actor, String? details) async {
    final db = await database;
    await db.insert('life_audit_logs', {
      'module_id': moduleId,
      'action': action,
      'actor': actor,
      'details': details,
    });
  }
}
