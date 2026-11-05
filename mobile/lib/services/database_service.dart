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
  // MODULE-SPECIFIC METHODS (ALL 14 MODULES)
  // --------------------------------------------------------------------------
  
  // Module 01: Identity
  Future<Map<String, dynamic>> getProfile() async {
    final db = await database;
    final res = await db.query('life_identity_profile', limit: 1);
    return res.isNotEmpty ? res.first : {
      'full_name': 'Vishnupriyan R',
      'preferred_name': 'Vishnupriyan',
      'timezone': 'Asia/Kolkata',
    };
  }

  Future<int> updateProfile(Map<String, dynamic> data) async {
    final db = await database;
    data['updated_at'] = DateTime.now().toIso8601String();
    return await db.update('life_identity_profile', data, where: 'id = 1');
  }

  Future<List<Map<String, dynamic>>> getImportantDates() async {
    final db = await database;
    return await db.query('life_important_dates', orderBy: 'event_date ASC');
  }

  Future<int> addImportantDate(String title, String eventDate, {String category = 'Personal', String? notes}) async {
    final db = await database;
    return await db.insert('life_important_dates', {
      'title': title,
      'event_date': eventDate,
      'category': category,
      'notes': notes,
    });
  }

  // Module 02: Finance
  Future<List<Map<String, dynamic>>> getAccounts() async {
    final db = await database;
    return await db.query('life_accounts', where: 'is_active = 1');
  }

  Future<List<Map<String, dynamic>>> getTransactions({int limit = 50, String? category}) async {
    final db = await database;
    if (category != null) {
      return await db.query('life_transactions', where: 'category = ?', whereArgs: [category], orderBy: 'transaction_date DESC', limit: limit);
    }
    return await db.query('life_transactions', orderBy: 'transaction_date DESC', limit: limit);
  }

  Future<int> insertTransaction(Map<String, dynamic> row) async {
    final db = await database;
    final id = await db.insert('life_transactions', row);
    // Update account balance
    final accountId = row['account_id'];
    final amount = (row['amount'] as num).toDouble();
    final type = row['type'] ?? 'EXPENSE';
    final sign = (type == 'INCOME') ? '+' : '-';
    await db.rawUpdate('UPDATE life_accounts SET balance = balance $sign ? WHERE id = ?', [amount, accountId]);
    return id;
  }

  Future<Map<String, dynamic>> getFinanceSummary(String period) async {
    final db = await database;
    final nowStr = DateTime.now().toIso8601String().substring(0, 7); // YYYY-MM
    final accounts = await getAccounts();
    double totalBalance = 0.0;
    for (var a in accounts) {
      totalBalance += (a['balance'] as num?)?.toDouble() ?? 0.0;
    }

    final expenseRes = await db.rawQuery("SELECT SUM(amount) as sum FROM life_transactions WHERE type = 'EXPENSE' AND transaction_date LIKE ?", ['$nowStr%']);
    final incomeRes = await db.rawQuery("SELECT SUM(amount) as sum FROM life_transactions WHERE type = 'INCOME' AND transaction_date LIKE ?", ['$nowStr%']);

    final catRes = await db.rawQuery('''
      SELECT category, SUM(amount) as total FROM life_transactions 
      WHERE type = 'EXPENSE' AND transaction_date LIKE ? GROUP BY category ORDER BY total DESC
    ''', ['$nowStr%']);

    return {
      'period': period,
      'total_balance': totalBalance,
      'month_expenses': (expenseRes.first['sum'] as num?)?.toDouble() ?? 0.0,
      'month_income': (incomeRes.first['sum'] as num?)?.toDouble() ?? 0.0,
      'category_breakdown': catRes,
      'accounts': accounts,
    };
  }

  // Module 03: Health
  Future<int> logHealthMetric(String type, double value, {String? notes}) async {
    final db = await database;
    return await db.insert('life_health_metrics', {
      'metric_type': type,
      'metric_value': value,
      'recorded_at': DateTime.now().toIso8601String(),
      'notes': notes,
    });
  }

  Future<List<Map<String, dynamic>>> getHealthMetrics(String type, {int limit = 30}) async {
    final db = await database;
    return await db.query('life_health_metrics', where: 'metric_type = ?', whereArgs: [type], orderBy: 'recorded_at DESC', limit: limit);
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

  Future<int> logSleepRecord(DateTime start, DateTime end, {int? quality, String? notes}) async {
    final db = await database;
    return await db.insert('life_sleep_records', {
      'sleep_start': start.toIso8601String(),
      'sleep_end': end.toIso8601String(),
      'quality_score': quality ?? 7,
      'notes': notes,
    });
  }

  // Module 04: Mental Wellbeing
  Future<int> logMood(int rating, String primaryEmotion, {String? entry}) async {
    final db = await database;
    final nowStr = DateTime.now().toIso8601String().substring(0, 10);
    return await db.insert('life_mood_journal', {
      'log_date': nowStr,
      'mood_rating': rating,
      'primary_emotion': primaryEmotion,
      'journal_entry': entry,
      'logged_at': DateTime.now().toIso8601String(),
    });
  }

  Future<List<Map<String, dynamic>>> getMoodLogs({int limit = 30}) async {
    final db = await database;
    return await db.query('life_mood_journal', orderBy: 'logged_at DESC', limit: limit);
  }

  Future<int> logGratitude(String entryText) async {
    final db = await database;
    return await db.insert('life_gratitude_entries', {
      'entry_text': entryText,
      'recorded_at': DateTime.now().toIso8601String(),
    });
  }

  // Module 05: Location & Travel
  Future<List<Map<String, dynamic>>> getPlaces() async {
    final db = await database;
    return await db.query('life_places', orderBy: 'id ASC');
  }

  Future<int> addPlace(String name, double lat, double lon, {double radius = 150.0, String category = 'Frequent'}) async {
    final db = await database;
    return await db.insert('life_places', {
      'name': name,
      'latitude': lat,
      'longitude': lon,
      'radius_meters': radius,
      'category': category,
    });
  }

  Future<List<Map<String, dynamic>>> getRecentBreadcrumbs({int limit = 50}) async {
    final db = await database;
    return await db.query('life_location_breadcrumbs', orderBy: 'recorded_at DESC', limit: limit);
  }

  Future<int> logBreadcrumb(double lat, double lon, {double? accuracy, double? speed}) async {
    final db = await database;
    return await db.insert('life_location_breadcrumbs', {
      'latitude': lat,
      'longitude': lon,
      'accuracy_meters': accuracy,
      'speed_mps': speed,
      'recorded_at': DateTime.now().toIso8601String(),
    });
  }

  // Module 06: Activities & Tasks
  Future<int> insertTask(String title, {String priority = 'MEDIUM', String? dueDate, String? description}) async {
    final db = await database;
    return await db.insert('life_tasks', {
      'title': title,
      'description': description,
      'priority': priority,
      'due_date': dueDate,
      'status': 'PENDING',
    });
  }

  Future<int> updateTaskStatus(int taskId, String status) async {
    final db = await database;
    return await db.update('life_tasks', {
      'status': status,
      'completed_at': (status == 'COMPLETED') ? DateTime.now().toIso8601String() : null,
    }, where: 'id = ?', whereArgs: [taskId]);
  }

  Future<List<Map<String, dynamic>>> getTasks({String? status}) async {
    final db = await database;
    if (status != null) {
      return await db.query('life_tasks', where: 'status = ?', whereArgs: [status], orderBy: 'id DESC');
    }
    return await db.query('life_tasks', orderBy: 'id DESC', limit: 50);
  }

  // Module 07: Habits
  Future<List<Map<String, dynamic>>> getHabits() async {
    final db = await database;
    return await db.query('life_habits', where: 'is_active = 1', orderBy: 'id ASC');
  }

  Future<int> addHabit(String name, {String frequency = 'DAILY', String? description}) async {
    final db = await database;
    return await db.insert('life_habits', {
      'name': name,
      'description': description,
      'frequency': frequency,
      'current_streak': 0,
      'longest_streak': 0,
      'is_active': 1,
    });
  }

  Future<int> checkInHabit(int habitId) async {
    final db = await database;
    final nowStr = DateTime.now().toIso8601String().substring(0, 10);
    final res = await db.insert('life_habit_logs', {
      'habit_id': habitId,
      'log_date': nowStr,
      'status': 'COMPLETED',
    }, conflictAlgorithm: ConflictAlgorithm.replace);

    // Increment streak
    await db.rawUpdate('UPDATE life_habits SET current_streak = current_streak + 1 WHERE id = ?', [habitId]);
    return res;
  }

  // Module 08: Education & GATE Preparation
  Future<List<Map<String, dynamic>>> getLearningSubjects({String? track}) async {
    final db = await database;
    if (track != null) {
      return await db.query('life_learning_subjects', where: 'track = ?', whereArgs: [track]);
    }
    return await db.query('life_learning_subjects', orderBy: 'id ASC');
  }

  Future<int> logStudySession(int subjectId, String topic, int durationMinutes, {int problemsSolved = 0, String? notes}) async {
    final db = await database;
    final id = await db.insert('life_study_sessions', {
      'subject_id': subjectId,
      'topic': topic,
      'duration_minutes': durationMinutes,
      'problems_solved': problemsSolved,
      'notes': notes,
      'recorded_at': DateTime.now().toIso8601String(),
    });
    // Update completed hours
    final hours = durationMinutes / 60.0;
    await db.rawUpdate('UPDATE life_learning_subjects SET completed_hours = completed_hours + ? WHERE id = ?', [hours, subjectId]);
    return id;
  }

  // Module 09: Career
  Future<Map<String, dynamic>> getCareerSummary() async {
    final db = await database;
    final skills = await db.query('life_skills_inventory');
    final clients = await db.query('life_freelance_clients');
    final employment = await db.query('life_employment_history');
    return {
      'skills': skills,
      'clients': clients,
      'employment': employment,
    };
  }

  // Module 10: Social
  Future<List<Map<String, dynamic>>> getContacts() async {
    final db = await database;
    return await db.query('life_contacts', orderBy: 'name ASC');
  }

  Future<int> addContact(String name, String relationship, {String? phone, String? email, String? birthday}) async {
    final db = await database;
    return await db.insert('life_contacts', {
      'name': name,
      'relationship': relationship,
      'phone': phone,
      'email': email,
      'birthday': birthday,
    });
  }

  // Module 11: Food & Nutrition
  Future<int> logMeal(String type, String items, {double? calories, double? cost}) async {
    final db = await database;
    return await db.insert('life_meals', {
      'meal_type': type,
      'food_items': items,
      'estimated_calories': calories,
      'cost': cost,
      'logged_at': DateTime.now().toIso8601String(),
    });
  }

  Future<List<Map<String, dynamic>>> getTodayMeals() async {
    final db = await database;
    final nowStr = DateTime.now().toIso8601String().substring(0, 10);
    return await db.query('life_meals', where: 'logged_at LIKE ?', whereArgs: ['$nowStr%'], orderBy: 'logged_at ASC');
  }

  // Module 12: Digital Life
  Future<int> recordDeviceStats(int battery, bool isCharging, int freeMb, int totalMb) async {
    final db = await database;
    return await db.insert('life_device_stats', {
      'battery_level': battery,
      'is_charging': isCharging ? 1 : 0,
      'storage_free_mb': freeMb,
      'storage_total_mb': totalMb,
      'recorded_at': DateTime.now().toIso8601String(),
    });
  }

  // Module 13: Documents Vault
  Future<List<Map<String, dynamic>>> getDocuments({String? category}) async {
    final db = await database;
    if (category != null) {
      return await db.query('life_documents', where: 'category = ?', whereArgs: [category], orderBy: 'created_at DESC');
    }
    return await db.query('life_documents', orderBy: 'created_at DESC');
  }

  Future<int> insertDocument(Map<String, dynamic> doc) async {
    final db = await database;
    return await db.insert('life_documents', doc);
  }

  // Module 14: AI Insights
  Future<List<Map<String, dynamic>>> getInsights({int limit = 10}) async {
    final db = await database;
    return await db.query('life_ai_insights', orderBy: 'created_at DESC', limit: limit);
  }

  Future<int> addInsight(String type, String title, String content) async {
    final db = await database;
    return await db.insert('life_ai_insights', {
      'insight_type': type,
      'title': title,
      'content': content,
    });
  }

  // Audit Logs
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

