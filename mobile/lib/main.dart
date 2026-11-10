import 'dart:convert';
import 'package:flutter/material.dart';
import 'services/api_service.dart';
import 'screens/mylife_server_screen.dart';
import 'screens/mylife_modules_screen.dart';

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
    const MyLifeModulesScreen(),
    const MyLifeServerScreen(),
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
          BottomNavigationBarItem(icon: Icon(Icons.grid_view_rounded), label: '14 Modules'),
          BottomNavigationBarItem(icon: Icon(Icons.dns_rounded), label: 'Server HUD'),
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
                // Phase 27 — Desktop Automation & Sidecar Tile
                _buildCard('DESKTOP SIDECAR', 'ONLINE', const Color(0xFFF43F5E)),
                // Phase 28 — Real-Time WebSocket & Sync Tile
                _buildCard('REAL-TIME SYNC', 'REPLICATING', const Color(0xFF06B6D4)),
                // Phase 29 — Autonomous Test Gen & CI/CD Tile
                _buildCard('CI/CD & TESTING', 'PASSING (240)', const Color(0xFF10B981)),
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
  final ApiService _api = ApiService();
  final List<Map<String, dynamic>> _messages = [];
  List<dynamic> _chats = [];
  final TextEditingController _controller = TextEditingController();
  final TextEditingController _email = TextEditingController();
  final TextEditingController _password = TextEditingController();
  int? _chatId;
  bool _isThinking = false;
  bool _loggingIn = false;
  String? _error;

  @override
  void initState() { super.initState(); if (_api.hasSession) _loadChats(); }

  Future<void> _login() async {
    setState(() { _loggingIn = true; _error = null; });
    final ok = await _api.loginForApprovals(_email.text.trim(), _password.text);
    if (!mounted) return;
    setState(() { _loggingIn = false; _error = ok ? null : 'Sign in failed. Check the API connection and credentials.'; });
    if (ok) { _password.clear(); await _loadChats(); }
  }

  Future<void> _loadChats() async {
    final chats = await _api.fetchChats();
    if (mounted) setState(() { _chats = chats; });
    if (_chatId == null && chats.isNotEmpty) await _selectChat(chats.first);
  }

  Future<void> _newChat() async {
    final result = await _api.createChat();
    final data = result['data'];
    if (!mounted) return;
    if (result['success'] == true && data is Map && data['id'] != null) {
      setState(() { _chatId = int.tryParse(data['id'].toString()); _messages.clear(); _error = null; });
      await _loadChats();
    } else { setState(() => _error = result['message']?.toString() ?? 'Could not create chat.'); }
  }

  Future<void> _selectChat(dynamic chat) async {
    final id = int.tryParse(chat['id'].toString());
    if (id == null) return;
    setState(() { _chatId = id; _messages.clear(); _error = null; });
    final result = await _api.fetchChat(id);
    if (!mounted || _chatId != id) return;
    final data = result['data'];
    if (result['success'] == true && data is Map && data['messages'] is List) {
      setState(() { for (final m in data['messages']) { _messages.add({'sender': m['role'] == 'user' ? 'user' : 'ai', 'text': m['content'] ?? ''}); } });
    }
    final approvals = await _api.fetchPendingApprovals();
    if (!mounted || _chatId != id) return;
    for (final approval in approvals) {
      final params = approval['parameters'];
      if (approval['tool_name'] == 'brain_chat' && params is Map && int.tryParse('${params['chat_id']}') == id) {
        dynamic explanation = approval['reason'];
        try { if (explanation is String) explanation = jsonDecode(explanation); } catch (_) { explanation = {'purpose': approval['reason']}; }
        setState(() => _messages.add({'sender': 'ai', 'pending': {'approval_id': approval['id'], 'action': explanation is Map ? explanation : {}}}));
      }
    }
  }

  Future<void> _sendMessage() async {
    final text = _controller.text.trim();
    if (text.isEmpty || _chatId == null || _isThinking) return;

    setState(() {
      _messages.add({"sender": "user", "text": text});
      _isThinking = true;
      _error = null;
    });
    _controller.clear();
    final response = await _api.sendChatMessage(_chatId!, text);
    if (!mounted) return;
    final data = response['data'];
    setState(() {
      _isThinking = false;
      if (response['success'] == true && data is Map) {
        if (data['pending_approval'] is Map) _messages.add({'sender': 'ai', 'pending': data['pending_approval']});
        else _messages.add({'sender': 'ai', 'text': data['content'] ?? ''});
      } else { _error = response['message']?.toString() ?? 'Message could not be sent.'; }
    });
  }

  Future<void> _resolveApproval(Map pending, bool approve) async {
    final id = int.tryParse('${pending['approval_id']}');
    if (id == null) return;
    setState(() { pending['busy'] = true; pending['error'] = null; });
    final response = await _api.resolveChatApproval(id, approve: approve);
    if (!mounted) return;
    final data = response['data'];
    final chat = data is Map ? data['chat'] : null;
    setState(() {
      pending['busy'] = false;
      if (response['success'] != true) { pending['error'] = response['message'] ?? 'Decision failed.'; return; }
      pending['resolved'] = true;
      if (data is Map && data['status'] == 'failed') _messages.add({'sender': 'ai', 'text': 'Approval was recorded, but the action failed. The chat turn could not continue.'});
      else if (chat is Map && chat['pending_approval'] is Map) _messages.add({'sender': 'ai', 'pending': chat['pending_approval']});
      else if (chat is Map && chat['content'] != null) _messages.add({'sender': 'ai', 'text': chat['content']});
      else _messages.add({'sender': 'ai', 'text': approve ? 'Approved action completed.' : 'Action rejected. Nothing was executed.'});
    });
  }

  @override
  void dispose() { _controller.dispose(); _email.dispose(); _password.dispose(); super.dispose(); }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('AI Gateway Console', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFF0C0F14),
        elevation: 0,
        actions: [if (_api.hasSession) IconButton(tooltip: 'New chat', icon: const Icon(Icons.add_comment_outlined, color: Color(0xFF00F2FE)), onPressed: _newChat)],
      ),
      body: !_api.hasSession ? _buildChatLogin() : Column(
        children: [
          if (_chats.isNotEmpty) SizedBox(height: 56, child: ListView(scrollDirection: Axis.horizontal, padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7), children: _chats.map((chat) => Padding(padding: const EdgeInsets.only(right: 8), child: ChoiceChip(label: Text((chat['title'] ?? 'Chat').toString(), overflow: TextOverflow.ellipsis), selected: int.tryParse('${chat['id']}') == _chatId, onSelected: (_) => _selectChat(chat)))).toList())),
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: _messages.length,
              itemBuilder: (context, index) {
                final msg = _messages[index];
                final isUser = msg["sender"] == "user";
                final pending = msg['pending'] is Map<String, dynamic> ? msg['pending'] as Map<String, dynamic> : null;
                if (pending != null) return _buildApprovalCard(pending);
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
                    decoration: InputDecoration(
                      hintText: 'Ask ATOM anything...',
                      hintStyle: TextStyle(color: Color(0xFF64748B)),
                      border: InputBorder.none,
                    ),
                    onSubmitted: (_) => _sendMessage(),
                    enabled: _chatId != null && !_isThinking,
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.send_rounded, color: Color(0xFF00F2FE)),
                  onPressed: _chatId == null || _isThinking ? null : _sendMessage,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildChatLogin() => Center(child: ConstrainedBox(constraints: const BoxConstraints(maxWidth: 420), child: Card(color: const Color(0xFF11151C), margin: const EdgeInsets.all(20), child: Padding(padding: const EdgeInsets.all(20), child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [const Text('Sign in to chat with ATOM', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)), const SizedBox(height: 8), const Text('Your session stays in memory and clears when the app closes.', style: TextStyle(color: Color(0xFF94A3B8))), const SizedBox(height: 14), TextField(controller: _email, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'Email')), TextField(controller: _password, obscureText: true, decoration: const InputDecoration(labelText: 'Password')), if (_error != null) Padding(padding: const EdgeInsets.only(top: 8), child: Text(_error!, style: const TextStyle(color: Colors.redAccent))), const SizedBox(height: 14), ElevatedButton(onPressed: _loggingIn ? null : _login, child: Text(_loggingIn ? 'Signing in…' : 'Sign in'))])))));

  Widget _buildApprovalCard(Map<String, dynamic> pending) {
    final action = pending['action'] is Map ? pending['action'] as Map : const {};
    Widget detail(String label, String key) { final value = action[key]; if (value == null || value.toString().isEmpty) return const SizedBox.shrink(); return Padding(padding: const EdgeInsets.only(top: 8), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(label.toUpperCase(), style: const TextStyle(color: Color(0xFF7F93AA), fontSize: 9, fontWeight: FontWeight.bold, letterSpacing: 1)), const SizedBox(height: 3), Text(value is String ? value : jsonEncode(value), style: const TextStyle(color: Color(0xFFE5EDF7), fontSize: 12, height: 1.4))])); }
    final busy = pending['busy'] == true; final resolved = pending['resolved'] == true;
    return Card(color: const Color(0xFF111A24), margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 2), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16), side: const BorderSide(color: Color(0x6600F2FE))), child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Text('ATOM is asking before acting', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)), const SizedBox(height: 5), const Text('Review this one action. Approval applies once.', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12)), detail('Action', 'action'), detail('Target', 'target'), detail('Purpose', 'purpose'), detail('Access', 'access'), detail('External sharing', 'external_data_sharing'), detail('Effects', 'consequences'), detail('Parameters', 'parameters'), if (pending['error'] != null) Padding(padding: const EdgeInsets.only(top: 8), child: Text(pending['error'].toString(), style: const TextStyle(color: Colors.redAccent, fontSize: 12))), if (resolved) const Padding(padding: EdgeInsets.only(top: 12), child: Text('Decision recorded', style: TextStyle(color: Color(0xFF34D399), fontWeight: FontWeight.bold))) else Padding(padding: const EdgeInsets.only(top: 14), child: Row(children: [Expanded(child: OutlinedButton(onPressed: busy ? null : () => _resolveApproval(pending, false), child: const Text('Reject'))), const SizedBox(width: 10), Expanded(child: ElevatedButton(style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF34D399), foregroundColor: const Color(0xFF061A15)), onPressed: busy ? null : () => _resolveApproval(pending, true), child: Text(busy ? 'Working…' : 'Approve once')))]))])));
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
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  List<dynamic> _requests = [];
  bool _loading = true;
  bool _loggingIn = false;
  String? _loginError;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    if (!_api.hasApprovalSession) {
      setState(() {
        _requests = [];
        _loading = false;
      });
      return;
    }
    setState(() => _loading = true);
    final data = await _api.fetchPendingApprovals();
    setState(() {
      _requests = data;
      _loading = false;
    });
  }

  Future<void> _login() async {
    setState(() {
      _loggingIn = true;
      _loginError = null;
    });
    final ok = await _api.loginForApprovals(_emailController.text.trim(), _passwordController.text);
    if (!mounted) return;
    setState(() {
      _loggingIn = false;
      _loginError = ok ? null : 'Login failed. Check the API connection and credentials.';
    });
    if (ok) {
      _passwordController.clear();
      await _loadData();
    }
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Widget _buildLoginForm() {
    return Center(
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 420),
        child: Card(
          color: const Color(0xFF11151C),
          margin: const EdgeInsets.all(20),
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text('Sign in to review approvals', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                const Text('Your session token stays in memory and is cleared when ATOM Mobile closes.', style: TextStyle(color: Color(0xFF94A3B8))),
                const SizedBox(height: 16),
                TextField(controller: _emailController, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'Email')),
                TextField(controller: _passwordController, obscureText: true, decoration: const InputDecoration(labelText: 'Password')),
                if (_loginError != null) ...[
                  const SizedBox(height: 8),
                  Text(_loginError!, style: const TextStyle(color: Colors.redAccent)),
                ],
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: _loggingIn ? null : _login,
                  child: Text(_loggingIn ? 'Signing in…' : 'Sign in'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
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
      body: !_api.hasApprovalSession
          ? _buildLoginForm()
          : _loading
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
                                  child: Text((item['risk_level'] ?? 'ACTION').toString().toUpperCase(), style: const TextStyle(color: Colors.redAccent, fontSize: 10, fontWeight: FontWeight.bold)),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Text(item['reason'] ?? '', style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12)),
                            const SizedBox(height: 6),
                            Text('Exact parameters: ' + (item['parameters']?.toString() ?? '{}'), style: const TextStyle(color: Color(0xFFCBD5E1), fontSize: 11)),
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
