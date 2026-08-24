import 'package:flutter/material.dart';
import 'services/api_service.dart';

void main() {
  runApp(const AtomApp());
}

class AtomApp extends StatelessWidget {
  const AtomApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'ATOM AI Core Platform',
      debugShowCheckedModeBanner: false,
      theme: ThemeData.dark().copyWith(
        primaryColor: const Color(0xFF00F2FE),
        scaffoldBackgroundColor: const Color(0xFF080A0D),
        cardColor: const Color(0xFF11151C),
        colorScheme: const ColorScheme.dark(
          primary: Color(0xFF00F2FE),
          secondary: Color(0xFFA855F7),
          surface: Color(0xFF11151C),
        ),
        bottomNavigationBarTheme: const BottomNavigationBarThemeData(
          backgroundColor: Color(0xFF0C0F14),
          selectedItemColor: Color(0xFF00F2FE),
          unselectedItemColor: Colors.grey,
        ),
      ),
      home: const MainNavigationScreen(),
    );
  }
}

class MainNavigationScreen extends StatefulWidget {
  const MainNavigationScreen({Key? key}) : super(key: key);

  @override
  State<MainNavigationScreen> createState() => _MainNavigationScreenState();
}

class _MainNavigationScreenState extends State<MainNavigationScreen> {
  int _currentIndex = 0;

  final List<Widget> _screens = [
    const MobileDashboardScreen(),
    const MobileChatScreen(),
    const MobileMemoryScreen(),
    const MobileApprovalsScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: _screens,
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        type: BottomNavigationBarType.fixed,
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.dashboard_rounded), label: 'Home'),
          BottomNavigationBarItem(icon: Icon(Icons.chat_bubble_rounded), label: 'Chat'),
          BottomNavigationBarItem(icon: Icon(Icons.psychology_rounded), label: 'Memory 2.0'),
          BottomNavigationBarItem(icon: Icon(Icons.verified_user_rounded), label: 'Approvals'),
        ],
      ),
    );
  }
}

class MobileDashboardScreen extends StatelessWidget {
  const MobileDashboardScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('ATOM NEURAL CORE v1.0', style: TextStyle(fontSize: 16, fontWeight: FontWeight.black, letterSpacing: 1.2)),
        backgroundColor: const Color(0xFF0C0F14),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Greetings, Vishnupriyan', style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.white)),
            const SizedBox(height: 4),
            const Text('ATOM Personal AI Brain is online — JARVIS-style orchestration active.', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 13)),
            const SizedBox(height: 20),
            GridView.count(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisCount: 2,
              crossAxisSpacing: 14,
              mainAxisSpacing: 14,
              childAspectRatio: 1.3,
              children: [
                _buildCard('CORE STATUS', 'ONLINE', const Color(0xFF00E676)),
                _buildCard('MODEL GATEWAY', 'AUTO ROUTE', const Color(0xFF00F2FE)),
                _buildCard('RAG VECTORS', '14,290', const Color(0xFFA855F7)),
                _buildCard('APPROVAL GATE', 'ENFORCED', const Color(0xFFFF9100)),
                // Phase 23 — Brain Status Tile
                _buildCard('BRAIN STATE', 'IDLE', const Color(0xFFA78BFA)),
                _buildCard('INTENT ENGINE', '14 CLASSES', const Color(0xFF34D399)),
                // Phase 24 — Multi-Modal Speech & Vision Tile
                _buildCard('VOICE & VISION', 'MULTI-MODAL', const Color(0xFFEC4899)),
                // Phase 25 — Proactive Daemon Tile
                _buildCard('PROACTIVE DAEMON', 'ACTIVE PULSE', const Color(0xFF38BDF8)),
                // Phase 26 — Developer IDE Protocol (LSP) Tile
                _buildCard('IDE PROTOCOL (LSP)', 'JSON-RPC 2.0', const Color(0xFF6366F1)),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCard(String title, String value, Color color) {
    return Card(
      elevation: 4,
      color: const Color(0xFF11151C),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: Color(0xFF1E2838)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(title, style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8), fontWeight: FontWeight.black, letterSpacing: 1)),
            const SizedBox(height: 8),
            Text(value, style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: color)),
          ],
        ),
      ),
    );
  }
}

class MobileChatScreen extends StatefulWidget {
  const MobileChatScreen({Key? key}) : super(key: key);

  @override
  State<MobileChatScreen> createState() => _MobileChatScreenState();
}

class _MobileChatScreenState extends State<MobileChatScreen> {
  final List<Map<String, dynamic>> _messages = [
    {
      "sender": "ai",
      "text": "Greetings Vishnupriyan! I am ATOM, your Personal AI Assistant with integrated Model Gateway, Advanced RAG, and Tool Calling Framework.",
      "citation": "[CodeIgniter 4 Manual | Page 12 | Score: 0.94]"
    }
  ];
  final TextEditingController _controller = TextEditingController();
  String _selectedModel = "Gemini 3.6 Flash";
  bool _isThinking = false;

  void _sendMessage() {
    final text = _controller.text.trim();
    if (text.isEmpty) return;

    setState(() {
      _messages.add({"sender": "user", "text": text});
      _isThinking = true;
    });
    _controller.clear();

    Future.delayed(const Duration(milliseconds: 800), () {
      if (!mounted) return;
      setState(() {
        _isThinking = false;
        _messages.add({
          "sender": "ai",
          "text": "ATOM AI Gateway ($_selectedModel): Processed query: '$text'. API v1.0 response verified.",
          "citation": "[ATOM Knowledge Graph | Hybrid Score: 0.88]"
        });
      });
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('AI Gateway Console', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFF0C0F14),
        elevation: 0,
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: DropdownButton<String>(
              value: _selectedModel,
              dropdownColor: const Color(0xFF11151C),
              underline: const SizedBox(),
              style: const TextStyle(color: Color(0xFF00F2FE), fontSize: 12, fontWeight: FontWeight.bold),
              items: <String>['Gemini 3.6 Flash', 'Groq (OSS-120B)', 'OpenAI GPT-4o', 'Ollama (Local)', 'Llama.cpp (Local)']
                  .map((String value) {
                return DropdownMenuItem<String>(
                  value: value,
                  child: Text(value),
                );
              }).toList(),
              onChanged: (val) {
                if (val != null) {
                  setState(() {
                    _selectedModel = val;
                  });
                }
              },
            ),
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: _messages.length,
              itemBuilder: (context, index) {
                final msg = _messages[index];
                final isUser = msg["sender"] == "user";
                return Align(
                  alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,
                  child: Container(
                    margin: const EdgeInsets.symmetric(vertical: 6),
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.82),
                    decoration: BoxDecoration(
                      color: isUser ? const Color(0xFF00F2FE).withOpacity(0.15) : const Color(0xFF11151C),
                      border: Border.all(
                        color: isUser ? const Color(0xFF00F2FE).withOpacity(0.4) : const Color(0xFF1E2838),
                      ),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          msg["text"],
                          style: TextStyle(
                            color: isUser ? const Color(0xFF00F2FE) : const Color(0xFFF8FAFC),
                            fontSize: 14,
                            height: 1.4,
                          ),
                        ),
                        if (msg["citation"] != null) ...[
                          const SizedBox(height: 6),
                          Text(
                            msg["citation"],
                            style: const TextStyle(color: Color(0xFFA855F7), fontSize: 10, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
          if (_isThinking)
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Row(
                children: [
                  SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF00F2FE))),
                  SizedBox(width: 8),
                  Text("ATOM Model Gateway is thinking...", style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12, fontStyle: FontStyle.italic)),
                ],
              ),
            ),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: const BoxDecoration(
              color: Color(0xFF0C0F14),
              border: Border(top: BorderSide(color: Color(0xFF1E2838))),
            ),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _controller,
                    style: const TextStyle(color: Colors.white, fontSize: 14),
                    decoration: const InputDecoration(
                      hintText: 'Ask ATOM anything...',
                      hintStyle: TextStyle(color: Color(0xFF64748B)),
                      border: InputBorder.none,
                    ),
                    onSubmitted: (_) => _sendMessage(),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.send_rounded, color: Color(0xFF00F2FE)),
                  onPressed: _sendMessage,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class MobileMemoryScreen extends StatelessWidget {
  const MobileMemoryScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final memories = [
      {"type": "PREFERENCE", "content": "I prefer dark mode glassmorphism UI themes", "importance": 8},
      {"type": "INSTRUCTION", "content": "Always use CodeIgniter 4 query builder with prefixTable() for database operations", "importance": 9},
      {"type": "FACT", "content": "Vishnupriyan R (Vichu) is a Senior Full-Stack Software Developer from Karur", "importance": 10},
      {"type": "PROJECT", "content": "ATOM Platform uses CodeIgniter 4 backend with MySQL Vector RAG and Flutter client", "importance": 9},
    ];

    return Scaffold(
      appBar: AppBar(
        title: const Text('Memory 2.0 Subsystem', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFF0C0F14),
        elevation: 0,
      ),
      body: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: memories.length,
        itemBuilder: (context, index) {
          final item = memories[index];
          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            color: const Color(0xFF11151C),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
              side: const BorderSide(color: Color(0xFF1E2838)),
            ),
            child: ListTile(
              title: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: const Color(0xFF00F2FE).withOpacity(0.15),
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(color: const Color(0xFF00F2FE).withOpacity(0.3)),
                    ),
                    child: Text(item["type"] as String, style: const TextStyle(color: Color(0xFF00F2FE), fontSize: 10, fontWeight: FontWeight.bold)),
                  ),
                  const Spacer(),
                  Text('Importance: ${item["importance"]}/10', style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 10)),
                ],
              ),
              subtitle: Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(item["content"] as String, style: const TextStyle(color: Color(0xFFF8FAFC), fontSize: 13, height: 1.3)),
              ),
            ),
          );
        },
      ),
    );
  }
}

class MobileApprovalsScreen extends StatefulWidget {
  const MobileApprovalsScreen({Key? key}) : super(key: key);

  @override
  State<MobileApprovalsScreen> createState() => _MobileApprovalsScreenState();
}

class _MobileApprovalsScreenState extends State<MobileApprovalsScreen> {
  final ApiService _api = ApiService();
  List<dynamic> _requests = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _loading = true);
    final data = await _api.fetchPendingApprovals();
    setState(() {
      _requests = data;
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Human Approval Gate', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFF0C0F14),
        elevation: 0,
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadData),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF00F2FE)))
          : _requests.isEmpty
              ? const Center(child: Text("No pending approval requests", style: TextStyle(color: Colors.grey, fontSize: 13)))
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _requests.length,
                  itemBuilder: (context, index) {
                    final item = _requests[index];
                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      color: const Color(0xFF11151C),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                        side: const BorderSide(color: Color(0xFF1E2838)),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.between,
                              children: [
                                Text(item['tool_name'] ?? 'System Action', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14)),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: Colors.red.withOpacity(0.15),
                                    borderRadius: BorderRadius.circular(6),
                                    border: Border.all(color: Colors.red.withOpacity(0.4)),
                                  ),
                                  child: const Text('HIGH RISK', style: TextStyle(color: Colors.redAccent, fontSize: 10, fontWeight: FontWeight.bold)),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Text(item['reason'] ?? '', style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12)),
                            const SizedBox(height: 12),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.end,
                              children: [
                                ElevatedButton(
                                  style: ElevatedButton.styleFrom(backgroundColor: Colors.red.withOpacity(0.2)),
                                  onPressed: () async {
                                    await _api.rejectRequest(item['id']);
                                    _loadData();
                                  },
                                  child: const Text('Reject', style: TextStyle(color: Colors.redAccent, fontSize: 12)),
                                ),
                                const SizedBox(width: 8),
                                ElevatedButton(
                                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF00E676).withOpacity(0.2)),
                                  onPressed: () async {
                                    await _api.approveRequest(item['id']);
                                    _loadData();
                                  },
                                  child: const Text('Approve', style: TextStyle(color: Color(0xFF00E676), fontSize: 12)),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
    );
  }
}
