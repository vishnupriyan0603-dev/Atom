import 'package:flutter/material.dart';

void main() {
  runApp(const AtomApp());
}

class AtomApp extends StatelessWidget {
  const AtomApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'ATOM AI Core',
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
    const MobileLearningScreen(),
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
          BottomNavigationBarItem(icon: Icon(Icons.psychology_rounded), label: 'Memory'),
          BottomNavigationBarItem(icon: Icon(Icons.school_rounded), label: 'Learning'),
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
        title: const Text('ATOM NEURAL CORE', style: TextStyle(fontSize: 16, fontWeight: FontWeight.black, letterSpacing: 1.2)),
        backgroundColor: const Color(0xFF0C0F14),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Greetings, Vichu', style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.white)),
            const SizedBox(height: 4),
            const Text('ATOM Personal AI Core is online and active.', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 13)),
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
                _buildCard('ACTIVE AI', 'GEMINI 3.6', const Color(0xFF00F2FE)),
                _buildCard('RAG VECTORS', '14,290', const Color(0xFFA855F7)),
                _buildCard('SAFETY GATE', 'ENFORCED', const Color(0xFFFF9100)),
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
      "text": "Greetings Vichu! I am ATOM, your Personal AI Assistant with integrated Hybrid RAG and Knowledge Graph capabilities."
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
          "text": "ATOM AI Core ($_selectedModel): Received your query: '$text'. CodeIgniter 4 API & Vector DB operational."
        });
      });
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('AI Assistant Console', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
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
              items: <String>['Gemini 3.6 Flash', 'Groq (OSS-120B)', 'OpenAI GPT-4o', 'Ollama (Local)']
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
                    constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.8),
                    decoration: BoxDecoration(
                      color: isUser ? const Color(0xFF00F2FE).withOpacity(0.15) : const Color(0xFF11151C),
                      border: Border.all(
                        color: isUser ? const Color(0xFF00F2FE).withOpacity(0.4) : const Color(0xFF1E2838),
                      ),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Text(
                      msg["text"],
                      style: TextStyle(
                        color: isUser ? const Color(0xFF00F2FE) : const Color(0xFFF8FAFC),
                        fontSize: 14,
                        height: 1.4,
                      ),
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
                  Text("ATOM is thinking...", style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12, fontStyle: FontStyle.italic)),
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
      {"title": "Owner Identity", "desc": "Vishnupriyan R (Vichu) • Software Developer from Karur, Tamil Nadu"},
      {"title": "Tech Stack", "desc": "PHP, CodeIgniter 4, Laravel, MySQL, Flutter, C# WPF, JavaScript"},
      {"title": "Response Style", "desc": "Concise, actionable, technically accurate markdown responses"},
      {"title": "Self-Learning Gate", "desc": "Human Authorization Gatekeeper active for prompt & code patches"},
    ];

    return Scaffold(
      appBar: AppBar(
        title: const Text('ATOM Memory & Profile', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
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
              title: Text(item["title"]!, style: const TextStyle(color: Color(0xFF00F2FE), fontWeight: FontWeight.bold, fontSize: 14)),
              subtitle: Padding(
                padding: const EdgeInsets.only(top: 6),
                child: Text(item["desc"]!, style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12)),
              ),
              leading: const Icon(Icons.psychology, color: Color(0xFFA855F7)),
            ),
          );
        },
      ),
    );
  }
}

class MobileLearningScreen extends StatelessWidget {
  const MobileLearningScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final topics = [
      {"topic": "PHP 8.3 & CodeIgniter 4 REST API", "progress": 0.95, "level": "Level 5 (Expert)"},
      {"topic": "Laravel Framework & Architecture", "progress": 0.78, "level": "Level 4 (Advanced)"},
      {"topic": "MySQL & Vector Embeddings Indexing", "progress": 0.88, "level": "Level 4 (Advanced)"},
      {"topic": "Flutter Mobile & Cross-Platform UI", "progress": 0.72, "level": "Level 3 (Intermediate)"},
    ];

    return Scaffold(
      appBar: AppBar(
        title: const Text('Learning Topics & Progress', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFF0C0F14),
        elevation: 0,
      ),
      body: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: topics.length,
        itemBuilder: (context, index) {
          final t = topics[index];
          final double progress = t["progress"] as double;

          return Card(
            margin: const EdgeInsets.only(bottom: 14),
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
                      Expanded(
                        child: Text(
                          t["topic"] as String,
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                        ),
                      ),
                      Text(
                        t["level"] as String,
                        style: const TextStyle(color: Color(0xFF00E676), fontSize: 11, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  LinearProgressIndicator(
                    value: progress,
                    backgroundColor: const Color(0xFF1E2838),
                    color: const Color(0xFF00F2FE),
                    minHeight: 6,
                    borderRadius: BorderRadius.circular(4),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    '${(progress * 100).toInt()}% Proficiency Mastery',
                    style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11),
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
