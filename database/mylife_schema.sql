-- ============================================================================
-- MYLIFE OS — Master SQLite / SQLCipher Database Schema
-- Authoritative Personal Data Store for Samsung Galaxy M31
-- Covers all 14 Life-Management Modules + System Pairing & Audit Logs
-- ============================================================================

PRAGMA foreign_keys = ON;

-- ----------------------------------------------------------------------------
-- SYSTEM & PAIRING
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS life_pairing_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_name TEXT NOT NULL,
    device_fingerprint TEXT NOT NULL UNIQUE,
    shared_secret_hash TEXT NOT NULL,
    paired_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_seen_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS life_audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    module_id TEXT NOT NULL,
    action TEXT NOT NULL, -- 'CREATE', 'READ', 'UPDATE', 'DELETE', 'EXPORT'
    actor TEXT NOT NULL,  -- 'PHONE_UI', 'ATOM_WINDOWS_API', 'BACKGROUND_TRACKER'
    details TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- MODULE 01: PERSONAL IDENTITY
-- ----------------------------------------------------------------------------
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
);

CREATE TABLE IF NOT EXISTS life_important_dates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    event_date TEXT NOT NULL, -- YYYY-MM-DD
    category TEXT NOT NULL DEFAULT 'Personal', -- 'Birthday', 'Anniversary', 'Renewal', 'Exam'
    reminder_days_before INTEGER DEFAULT 1,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_emergency_contacts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    relationship TEXT NOT NULL,
    phone_number TEXT NOT NULL,
    alternate_phone TEXT NULL,
    is_primary INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- MODULE 02: FINANCE
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS life_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    account_name TEXT NOT NULL,
    account_type TEXT NOT NULL, -- 'SAVINGS', 'CHECKING', 'CASH', 'CREDIT_CARD', 'INVESTMENT'
    currency TEXT NOT NULL DEFAULT 'INR',
    balance REAL NOT NULL DEFAULT 0.0,
    institution_name TEXT NULL,
    notes TEXT NULL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    account_id INTEGER NOT NULL,
    type TEXT NOT NULL, -- 'EXPENSE', 'INCOME', 'TRANSFER'
    category TEXT NOT NULL, -- 'Food', 'Transport', 'Education', 'Health', 'Tech', 'Utilities', 'Other'
    amount REAL NOT NULL,
    transaction_date TEXT NOT NULL, -- YYYY-MM-DD HH:MM:SS
    recipient_or_sender TEXT NULL,
    note TEXT NULL,
    is_reconciled INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES life_accounts(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_trans_date ON life_transactions(transaction_date);
CREATE INDEX IF NOT EXISTS idx_trans_category ON life_transactions(category);

CREATE TABLE IF NOT EXISTS life_budgets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category TEXT NOT NULL,
    monthly_limit REAL NOT NULL,
    month_year TEXT NOT NULL, -- YYYY-MM
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_name TEXT NOT NULL,
    cost REAL NOT NULL,
    billing_cycle TEXT NOT NULL DEFAULT 'MONTHLY', -- 'MONTHLY', 'ANNUAL'
    renewal_date TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'Digital',
    is_active INTEGER DEFAULT 1
);

-- ----------------------------------------------------------------------------
-- MODULE 03: PHYSICAL HEALTH
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS life_health_metrics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    metric_type TEXT NOT NULL, -- 'WEIGHT_KG', 'HEIGHT_CM', 'BLOOD_PRESSURE_SYS', 'BLOOD_PRESSURE_DIA', 'HEART_RATE_BPM'
    metric_value REAL NOT NULL,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL
);
CREATE INDEX IF NOT EXISTS idx_health_recorded ON life_health_metrics(recorded_at);

CREATE TABLE IF NOT EXISTS life_step_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    log_date TEXT NOT NULL UNIQUE, -- YYYY-MM-DD
    step_count INTEGER NOT NULL DEFAULT 0,
    distance_meters REAL DEFAULT 0.0,
    calories_burned REAL DEFAULT 0.0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_sleep_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sleep_start DATETIME NOT NULL,
    sleep_end DATETIME NOT NULL,
    quality_score INTEGER NULL, -- 1-10
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_water_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    log_date TEXT NOT NULL, -- YYYY-MM-DD
    amount_ml INTEGER NOT NULL,
    logged_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_medical_appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    doctor_name TEXT NOT NULL,
    specialty TEXT NULL,
    appointment_date TEXT NOT NULL,
    location TEXT NULL,
    reason TEXT NULL,
    prescription_notes TEXT NULL,
    status TEXT DEFAULT 'SCHEDULED' -- 'SCHEDULED', 'COMPLETED', 'CANCELLED'
);

-- ----------------------------------------------------------------------------
-- MODULE 04: MENTAL WELLBEING
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS life_mood_journal (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    log_date TEXT NOT NULL, -- YYYY-MM-DD
    mood_rating INTEGER NOT NULL, -- 1 (Very Low) to 5 (Excellent)
    primary_emotion TEXT NOT NULL, -- 'Calm', 'Focused', 'Stressed', 'Joyful', 'Tired', 'Anxious'
    journal_entry TEXT NULL,
    logged_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_stress_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    stress_level INTEGER NOT NULL, -- 1-10
    stressor_tags TEXT NULL, -- 'Work', 'Exam', 'Sleep', 'Health'
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_gratitude_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entry_text TEXT NOT NULL,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- MODULE 05: LOCATION & TRAVEL
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS life_places (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL, -- 'Home', 'Work / College', 'Gym', 'Library'
    latitude REAL NOT NULL,
    longitude REAL NOT NULL,
    radius_meters REAL DEFAULT 150.0,
    category TEXT DEFAULT 'Frequent',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_location_breadcrumbs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    latitude REAL NOT NULL,
    longitude REAL NOT NULL,
    accuracy_meters REAL NULL,
    speed_mps REAL NULL,
    nearest_place_id INTEGER NULL,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nearest_place_id) REFERENCES life_places(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_location_recorded ON life_location_breadcrumbs(recorded_at);

CREATE TABLE IF NOT EXISTS life_trips (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    start_date TEXT NOT NULL,
    end_date TEXT NULL,
    destination TEXT NOT NULL,
    notes TEXT NULL,
    total_expense REAL DEFAULT 0.0
);

-- ----------------------------------------------------------------------------
-- MODULE 06: DAILY ACTIVITIES & ROUTINES
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS life_tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT NULL,
    priority TEXT NOT NULL DEFAULT 'MEDIUM', -- 'LOW', 'MEDIUM', 'HIGH', 'URGENT'
    due_date TEXT NULL, -- YYYY-MM-DD
    status TEXT NOT NULL DEFAULT 'PENDING', -- 'PENDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'
    completed_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_task_status ON life_tasks(status);

CREATE TABLE IF NOT EXISTS life_routines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    routine_name TEXT NOT NULL,
    time_of_day TEXT NOT NULL, -- 'MORNING', 'AFTERNOON', 'EVENING', 'NIGHT'
    target_time TEXT NULL, -- HH:MM
    checklist_items TEXT NOT NULL, -- JSON array of items
    is_active INTEGER DEFAULT 1
);

CREATE TABLE IF NOT EXISTS life_time_blocks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    activity_name TEXT NOT NULL,
    category TEXT NOT NULL, -- 'Coding', 'GATE Prep', 'Exercise', 'Reading', 'Rest'
    start_time DATETIME NOT NULL,
    end_time DATETIME NULL,
    duration_minutes INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- MODULE 07: HABIT TRACKER
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS life_habits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT NULL,
    frequency TEXT NOT NULL DEFAULT 'DAILY', -- 'DAILY', 'WEEKDAYS', 'WEEKLY'
    target_count_per_period INTEGER DEFAULT 1,
    current_streak INTEGER DEFAULT 0,
    longest_streak INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_habit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    habit_id INTEGER NOT NULL,
    log_date TEXT NOT NULL, -- YYYY-MM-DD
    status TEXT NOT NULL DEFAULT 'COMPLETED', -- 'COMPLETED', 'SKIPPED'
    logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (habit_id) REFERENCES life_habits(id) ON DELETE CASCADE,
    UNIQUE(habit_id, log_date)
);

-- ----------------------------------------------------------------------------
-- MODULE 08: EDUCATION & LEARNING (GATE & TECH ROADMAP)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS life_learning_subjects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL, -- 'Engineering Mathematics', 'Algorithms', 'PHP Core', '.NET WPF'
    track TEXT NOT NULL, -- 'GATE_CS', 'SOFTWARE_ENGINEERING', 'SYSTEM_DESIGN'
    target_hours REAL DEFAULT 50.0,
    completed_hours REAL DEFAULT 0.0,
    status TEXT DEFAULT 'IN_PROGRESS',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_study_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    subject_id INTEGER NOT NULL,
    topic TEXT NOT NULL,
    duration_minutes INTEGER NOT NULL,
    problems_solved INTEGER DEFAULT 0,
    notes TEXT NULL,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES life_learning_subjects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS life_reading_list (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    author TEXT NULL,
    category TEXT DEFAULT 'Technical',
    total_pages INTEGER DEFAULT 0,
    pages_read INTEGER DEFAULT 0,
    status TEXT DEFAULT 'READING', -- 'WANT_TO_READ', 'READING', 'COMPLETED'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- MODULE 09: CAREER & PROFESSIONAL LIFE
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS life_employment_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_name TEXT NOT NULL,
    role_title TEXT NOT NULL,
    start_date TEXT NOT NULL,
    end_date TEXT NULL,
    technologies_used TEXT NULL,
    key_achievements TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_skills_inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    skill_name TEXT NOT NULL,
    category TEXT NOT NULL, -- 'Backend', 'Frontend', 'Database', 'DevOps', 'Mobile'
    proficiency_level TEXT NOT NULL, -- 'Beginner', 'Intermediate', 'Advanced', 'Expert'
    last_practiced_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS life_freelance_clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_name TEXT NOT NULL,
    project_scope TEXT NULL,
    status TEXT DEFAULT 'ACTIVE',
    rate_hourly REAL NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- MODULE 10: SOCIAL LIFE
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS life_contacts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    relationship TEXT NOT NULL, -- 'Family', 'Close Friend', 'Colleague', 'Mentor'
    phone TEXT NULL,
    email TEXT NULL,
    birthday TEXT NULL, -- MM-DD or YYYY-MM-DD
    notes TEXT NULL,
    last_interaction_date TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_interactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contact_id INTEGER NOT NULL,
    interaction_type TEXT NOT NULL, -- 'Call', 'Meeting', 'Chat', 'Event'
    summary TEXT NULL,
    occurred_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contact_id) REFERENCES life_contacts(id) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- MODULE 11: FOOD & NUTRITION
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS life_meals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    meal_type TEXT NOT NULL, -- 'BREAKFAST', 'LUNCH', 'DINNER', 'SNACK'
    food_items TEXT NOT NULL,
    estimated_calories REAL NULL,
    cost REAL NULL,
    logged_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_dietary_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    preference_key TEXT NOT NULL UNIQUE,
    preference_value TEXT NOT NULL
);

-- ----------------------------------------------------------------------------
-- MODULE 12: DIGITAL LIFE
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS life_device_stats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    battery_level INTEGER NOT NULL,
    is_charging INTEGER NOT NULL,
    storage_free_mb INTEGER NOT NULL,
    storage_total_mb INTEGER NOT NULL,
    ram_free_mb INTEGER NULL,
    network_type TEXT NULL, -- 'WIFI', 'CELLULAR', 'OFFLINE'
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS life_app_usage_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    package_name TEXT NOT NULL,
    app_label TEXT NOT NULL,
    foreground_seconds INTEGER NOT NULL,
    usage_date TEXT NOT NULL, -- YYYY-MM-DD
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_usage_date ON life_app_usage_logs(usage_date);

-- ----------------------------------------------------------------------------
-- MODULE 13: PERSONAL DOCUMENTS VAULT
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS life_documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    category TEXT NOT NULL, -- 'Identity', 'Academic', 'Employment', 'Financial', 'Medical', 'Travel'
    encrypted_file_path TEXT NOT NULL,
    file_size_bytes INTEGER NOT NULL,
    mime_type TEXT NOT NULL,
    sha256_hash TEXT NOT NULL,
    tags TEXT NULL, -- Comma-separated tags
    expiry_date TEXT NULL, -- YYYY-MM-DD
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- MODULE 14: AI PERSONAL INTELLIGENCE
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS life_ai_insights (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    insight_type TEXT NOT NULL, -- 'DAILY_SUMMARY', 'FINANCE_ALERT', 'HEALTH_TREND', 'HABIT_COACH'
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    confidence_score REAL DEFAULT 1.0,
    is_actionable INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

