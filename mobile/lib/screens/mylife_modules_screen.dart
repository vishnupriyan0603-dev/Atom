import 'package:flutter/material.dart';
import '../services/database_service.dart';

class MyLifeModulesScreen extends StatefulWidget {
  const MyLifeModulesScreen({Key? key}) : super(key: key);

  @override
  State<MyLifeModulesScreen> createState() => _MyLifeModulesScreenState();
}

class _MyLifeModulesScreenState extends State<MyLifeModulesScreen> {
  final DatabaseService _db = DatabaseService();
  Map<String, dynamic>? _summary;
  List<Map<String, dynamic>> _habits = [];
  List<Map<String, dynamic>> _tasks = [];
  List<Map<String, dynamic>> _subjects = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final summary = await _db.getAggregatedSummary();
      final habits = await _db.getHabits();
      final tasks = await _db.getTasks();
      final subjects = await _db.getLearningSubjects();

      if (mounted) {
        setState(() {
          _summary = summary;
          _habits = habits;
          _tasks = tasks;
          _subjects = subjects;
          _isLoading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF080A0D),
      appBar: AppBar(
        title: const Text('MyLife OS • 14 Modules', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        backgroundColor: const Color(0xFF0C0F14),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: Color(0xFF00F2FE)),
            onPressed: _loadData,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF00F2FE)))
          : ListView(
              padding: const EdgeInsets.all(16.0),
              children: [
                // 1. Module 01: Identity & Profile
                _buildIdentityCard(),
                const SizedBox(height: 12),

                // 2. Module 02: Finance
                _buildFinanceCard(),
                const SizedBox(height: 12),

                // 3. Module 03: Physical Health
                _buildHealthCard(),
                const SizedBox(height: 12),

                // 4. Module 04: Mental Wellbeing
                _buildWellbeingCard(),
                const SizedBox(height: 12),

                // 5. Module 06 & 07: Tasks & Habits
                _buildHabitsAndTasksCard(),
                const SizedBox(height: 12),

                // 6. Module 08: Education & GATE Preparation
                _buildEducationCard(),
                const SizedBox(height: 12),

                // 7. Grid of Remaining Modules (05, 09, 10, 11, 12, 13, 14)
                _buildRemainingModulesGrid(),
              ],
            ),
    );
  }

  Widget _buildIdentityCard() {
    final identity = _summary?['identity'] ?? {};
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF11151C),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF1E2838)),
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: const LinearGradient(colors: [Color(0xFF00F2FE), Color(0xFF4FACFE)]),
            ),
            child: const Center(
              child: Text('V', style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold, fontSize: 18)),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  identity['full_name'] ?? 'Vishnupriyan R',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 2),
                const Text(
                  'Module 01 • Personal Identity & Emergency Ice',
                  style: TextStyle(color: Color(0xFF00F2FE), fontSize: 11),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFinanceCard() {
    final finance = _summary?['finance'] ?? {};
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF11151C),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF1E2838)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: const [
              Text('Module 02 • Finance & Ledger', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
              Icon(Icons.account_balance_wallet_rounded, color: Color(0xFF10B981), size: 18),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _buildMiniStat('Total Balance', '₹${finance['total_balance'] ?? 0}', const Color(0xFF10B981)),
              _buildMiniStat('Month Expenses', '₹${finance['month_expenses'] ?? 0}', Colors.redAccent),
              _buildMiniStat('Accounts', '${finance['accounts_count'] ?? 2}', Colors.white),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildHealthCard() {
    final health = _summary?['health'] ?? {};
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF11151C),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF1E2838)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: const [
              Text('Module 03 • Health & Vitals', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
              Icon(Icons.favorite_rounded, color: Colors.rose, size: 18),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _buildMiniStat('Today Steps', '${health['today_steps'] ?? 0}', const Color(0xFF00F2FE)),
              _buildMiniStat('Water Intake', '${health['today_water_ml'] ?? 0} ml', Colors.cyan),
              ElevatedButton.icon(
                icon: const Icon(Icons.add, size: 14),
                label: const Text('+250ml', style: TextStyle(fontSize: 11)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF00F2FE).withOpacity(0.2),
                  foregroundColor: const Color(0xFF00F2FE),
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                ),
                onPressed: () async {
                  await _db.logWaterIntake(250);
                  await _loadData();
                },
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildWellbeingCard() {
    final wb = _summary?['wellbeing'] ?? {};
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF11151C),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF1E2838)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Module 04 • Mental Wellbeing', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
              const SizedBox(height: 4),
              Text('Current State: ${wb['primary_emotion'] ?? "Calm"}', style: const TextStyle(color: Color(0xFFA855F7), fontSize: 12, fontWeight: FontWeight.w600)),
            ],
          ),
          OutlinedButton(
            child: const Text('Check In', style: TextStyle(fontSize: 11, color: Color(0xFFA855F7))),
            style: OutlinedButton.styleFrom(side: const BorderSide(color: Color(0xFFA855F7))),
            onPressed: () async {
              await _db.logMood(5, 'Focused', entry: 'Productive coding session');
              await _loadData();
              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Mood recorded: Focused')));
            },
          ),
        ],
      ),
    );
  }

  Widget _buildHabitsAndTasksCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF11151C),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF1E2838)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: const [
              Text('Modules 06 & 07 • Habits & Tasks', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
              Icon(Icons.check_circle_outline_rounded, color: Color(0xFF00F2FE), size: 18),
            ],
          ),
          const SizedBox(height: 10),
          ..._habits.take(3).map((h) => Padding(
            padding: const EdgeInsets.symmetric(vertical: 4),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    h['name'] ?? '',
                    style: const TextStyle(color: Colors.white70, fontSize: 12),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                Text('🔥 ${h['current_streak']}d', style: const TextStyle(color: Colors.amber, fontSize: 12, fontWeight: FontWeight.bold)),
                const SizedBox(width: 8),
                InkWell(
                  onTap: () async {
                    await _db.checkInHabit(h['id']);
                    await _loadData();
                  },
                  child: Container(
                    padding: const EdgeInsets.all(4),
                    decoration: BoxDecoration(
                      color: const Color(0xFF10B981).withOpacity(0.2),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.check, color: Color(0xFF10B981), size: 14),
                  ),
                ),
              ],
            ),
          )),
        ],
      ),
    );
  }

  Widget _buildEducationCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF11151C),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF1E2838)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: const [
              Text('Module 08 • GATE CS & Technical Study', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
              Icon(Icons.school_rounded, color: Colors.cyan, size: 18),
            ],
          ),
          const SizedBox(height: 10),
          ..._subjects.take(2).map((s) {
            final target = (s['target_hours'] as num?)?.toDouble() ?? 50.0;
            final completed = (s['completed_hours'] as num?)?.toDouble() ?? 0.0;
            final progress = (target > 0) ? (completed / target).clamp(0.0, 1.0) : 0.0;

            return Padding(
              padding: const EdgeInsets.symmetric(vertical: 4),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(s['title'] ?? '', style: const TextStyle(color: Colors.white, fontSize: 12)),
                      Text('${completed.toStringAsFixed(1)} / ${target.toInt()}h', style: const TextStyle(color: Colors.cyan, fontSize: 11, fontWeight: FontWeight.bold)),
                    ],
                  ),
                  const SizedBox(height: 4),
                  LinearProgressIndicator(
                    value: progress,
                    backgroundColor: const Color(0xFF1E2838),
                    color: Colors.cyan,
                    minHeight: 4,
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildRemainingModulesGrid() {
    final modules = [
      {'num': '05', 'title': 'Location & Travel', 'icon': Icons.map_rounded, 'color': Colors.blue},
      {'num': '09', 'title': 'Career & Freelance', 'icon': Icons.work_rounded, 'color': Colors.indigo},
      {'num': '10', 'title': 'Social Contacts', 'icon': Icons.people_rounded, 'color': Colors.teal},
      {'num': '11', 'title': 'Food & Nutrition', 'icon': Icons.restaurant_rounded, 'color': Colors.orange},
      {'num': '12', 'title': 'Digital Life', 'icon': Icons.phone_android_rounded, 'color': Colors.purple},
      {'num': '13', 'title': 'Document Vault', 'icon': Icons.lock_rounded, 'color': Colors.redAccent},
      {'num': '14', 'title': 'AI Intelligence', 'icon': Icons.psychology_rounded, 'color': const Color(0xFF00F2FE)},
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Additional Connected Domains', style: TextStyle(color: Colors.grey, fontSize: 12, fontWeight: FontWeight.bold)),
        const SizedBox(height: 8),
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            childAspectRatio: 2.8,
            crossAxisSpacing: 10,
            mainAxisSpacing: 10,
          ),
          itemCount: modules.length,
          itemBuilder: (context, index) {
            final m = modules[index];
            return Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: const Color(0xFF11151C),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFF1E2838)),
              ),
              child: Row(
                children: [
                  Icon(m['icon'] as IconData, color: m['color'] as Color, size: 18),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      '${m['num']} • ${m['title']}',
                      style: const TextStyle(color: Colors.white70, fontSize: 11, fontWeight: FontWeight.w500),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _buildMiniStat(String label, String value, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(color: Colors.grey, fontSize: 10)),
        const SizedBox(height: 2),
        Text(value, style: TextStyle(color: color, fontSize: 14, fontWeight: FontWeight.bold)),
      ],
    );
  }
}
