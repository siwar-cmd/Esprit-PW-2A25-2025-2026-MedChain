<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/auth/sign-in.php');
    exit;
}
$admin_name = ($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assistant IA - MedChain</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
  --green:#1D9E75;--green-dark:#0F6E56;--green-light:#e6f7f1;--navy:#1E3A52;--navy-dark:#0F172A;
  --gray-700:#374151;--gray-500:#6B7280;--gray-300:#D1D5DB;--gray-200:#E5E7EB;--gray-100:#F3F4F6;--white:#fff;
  --shadow-sm:0 1px 3px rgba(0,0,0,.08);--shadow-md:0 4px 16px rgba(0,0,0,.10);
  --shadow-lg:0 12px 40px rgba(0,0,0,.13);
  --radius-sm:8px;--radius-md:12px;--radius-lg:20px;--radius-xl:28px;
}
body{font-family:'DM Sans',sans-serif;background:linear-gradient(145deg,#f0faf6,#e8f7f1,#ddf3ea);min-height:100vh;overflow-x:hidden}

/* LAYOUT */
.dashboard-container{display:grid;grid-template-columns:260px 1fr;min-height:100vh}

/* SIDEBAR */
.dashboard-sidebar{background:linear-gradient(180deg,var(--navy) 0%,var(--navy-dark) 100%);position:sticky;top:0;height:100vh;display:flex;flex-direction:column;overflow-y:auto}
.dashboard-logo{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.1);margin-bottom:20px}
.dashboard-logo a{display:flex;align-items:center;gap:10px;text-decoration:none}
.dashboard-logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center}
.dashboard-logo-icon i{font-size:18px;color:#fff}
.dashboard-logo-text{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;color:#fff}
.dashboard-logo-text span{color:var(--green)}
.dashboard-nav{flex:1;display:flex;flex-direction:column;gap:4px;padding:0 12px}
.dashboard-nav-item{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#94A3B8;text-decoration:none;border-radius:var(--radius-md);transition:all .3s;font-size:14px;font-weight:500}
.dashboard-nav-item i{font-size:18px;width:24px}
.dashboard-nav-item:hover{background:rgba(255,255,255,.1);color:#fff}
.dashboard-nav-item.active{background:rgba(29,158,117,.2);color:var(--green)}
.dashboard-nav-item.logout{margin-top:auto;margin-bottom:20px;color:#F87171}
.dashboard-nav-item.logout:hover{background:rgba(248,113,113,.1)}
.dashboard-nav-title{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748B;padding:16px 16px 8px;font-weight:600}

/* MAIN */
.dashboard-main{padding:32px 40px;display:flex;flex-direction:column;height:100vh;overflow:hidden}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-shrink:0}
.page-header h1{font-family:'Syne',sans-serif;font-size:26px;font-weight:700;color:var(--navy);display:flex;align-items:center;gap:12px}
.page-header h1 .ai-badge{background:linear-gradient(135deg,var(--green),var(--green-dark));color:#fff;font-size:11px;padding:3px 10px;border-radius:20px;font-family:'DM Sans',sans-serif;font-weight:600;letter-spacing:.5px}

/* CHAT AREA */
.chat-wrapper{flex:1;display:flex;flex-direction:column;background:var(--white);border-radius:var(--radius-xl);box-shadow:var(--shadow-lg);border:1px solid rgba(29,158,117,.15);overflow:hidden;min-height:0}

/* CHAT HEADER */
.chat-header{background:linear-gradient(135deg,var(--navy) 0%,var(--navy-dark) 100%);padding:18px 24px;display:flex;align-items:center;gap:14px;flex-shrink:0}
.chat-avatar{width:44px;height:44px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;position:relative}
.chat-avatar i{font-size:22px;color:#fff}
.online-dot{position:absolute;bottom:2px;right:2px;width:10px;height:10px;background:#22C55E;border-radius:50%;border:2px solid var(--navy-dark);animation:pulse-dot 2s infinite}
@keyframes pulse-dot{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.3);opacity:.7}}
.chat-header-info h2{font-family:'Syne',sans-serif;color:#fff;font-size:16px;font-weight:700;margin-bottom:2px}
.chat-header-info p{color:#94A3B8;font-size:12px}
.chat-header-actions{margin-left:auto;display:flex;gap:8px}
.icon-btn{background:rgba(255,255,255,.1);border:none;color:#fff;width:36px;height:36px;border-radius:var(--radius-sm);cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;font-size:16px}
.icon-btn:hover{background:rgba(255,255,255,.2)}

/* MESSAGES */
.messages-container{flex:1;overflow-y:auto;padding:24px;display:flex;flex-direction:column;gap:16px;scroll-behavior:smooth}
.messages-container::-webkit-scrollbar{width:5px}
.messages-container::-webkit-scrollbar-track{background:transparent}
.messages-container::-webkit-scrollbar-thumb{background:var(--gray-200);border-radius:10px}

.message{display:flex;gap:12px;max-width:80%;animation:msg-in .3s ease}
@keyframes msg-in{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.message.user{align-self:flex-end;flex-direction:row-reverse}
.message.bot{align-self:flex-start}

.msg-avatar{width:34px;height:34px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:16px}
.message.bot .msg-avatar{background:linear-gradient(135deg,var(--green),var(--green-dark));color:#fff}
.message.user .msg-avatar{background:linear-gradient(135deg,var(--navy),var(--navy-dark));color:#fff}

.msg-bubble{padding:12px 16px;border-radius:18px;font-size:14px;line-height:1.6;position:relative}
.message.bot .msg-bubble{background:var(--gray-100);color:var(--gray-700);border-bottom-left-radius:4px}
.message.user .msg-bubble{background:linear-gradient(135deg,var(--green),var(--green-dark));color:#fff;border-bottom-right-radius:4px}
.msg-time{font-size:11px;opacity:.6;margin-top:4px;text-align:right}
.message.bot .msg-time{text-align:left}

/* TYPING */
.typing-indicator{display:flex;gap:12px;align-items:center;align-self:flex-start;padding:4px 0}
.typing-bubble{background:var(--gray-100);border-radius:18px;border-bottom-left-radius:4px;padding:12px 18px;display:flex;gap:5px;align-items:center}
.typing-dot{width:7px;height:7px;border-radius:50%;background:var(--gray-300);animation:typing 1.4s infinite}
.typing-dot:nth-child(2){animation-delay:.2s}
.typing-dot:nth-child(3){animation-delay:.4s}
@keyframes typing{0%,60%,100%{transform:translateY(0);opacity:.5}30%{transform:translateY(-6px);opacity:1}}

/* SUGGESTIONS */
.suggestions{display:flex;flex-wrap:wrap;gap:8px;padding:0 24px 16px;flex-shrink:0}
.suggestion-chip{background:var(--green-light);color:var(--green-dark);border:1.5px solid rgba(29,158,117,.25);border-radius:20px;padding:6px 14px;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;font-family:'DM Sans',sans-serif;white-space:nowrap}
.suggestion-chip:hover{background:var(--green);color:#fff;border-color:var(--green);transform:translateY(-1px)}

/* INPUT */
.chat-input-area{border-top:1px solid var(--gray-200);padding:16px 20px;flex-shrink:0;background:#fff}
.input-row{display:flex;gap:10px;align-items:flex-end}
.input-box{flex:1;border:2px solid var(--gray-200);border-radius:var(--radius-lg);padding:12px 16px;font-size:14px;font-family:'DM Sans',sans-serif;resize:none;max-height:100px;min-height:48px;transition:all .3s;line-height:1.5;overflow-y:auto}
.input-box:focus{outline:none;border-color:var(--green);box-shadow:0 0 0 3px rgba(29,158,117,.12)}
.send-btn{width:48px;height:48px;background:linear-gradient(135deg,var(--green),var(--green-dark));border:none;border-radius:var(--radius-md);color:#fff;font-size:20px;cursor:pointer;transition:all .3s;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 3px 12px rgba(29,158,117,.35)}
.send-btn:hover{transform:translateY(-2px) scale(1.05);box-shadow:0 6px 20px rgba(29,158,117,.45)}
.send-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
.input-hint{font-size:11px;color:var(--gray-500);margin-top:8px;text-align:center}

/* WELCOME */
.welcome-card{text-align:center;padding:40px 24px;color:var(--gray-500)}
.welcome-icon{width:70px;height:70px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:32px;color:#fff;box-shadow:0 8px 30px rgba(29,158,117,.3)}
.welcome-card h3{font-family:'Syne',sans-serif;color:var(--navy);font-size:20px;font-weight:700;margin-bottom:8px}
.welcome-card p{font-size:14px;line-height:1.6;max-width:360px;margin:0 auto}

@media(max-width:900px){.dashboard-container{grid-template-columns:1fr}.dashboard-sidebar{display:none}.dashboard-main{padding:16px}}
</style>
</head>
<body>

<div class="dashboard-container">

  <!-- SIDEBAR -->
  <aside class="dashboard-sidebar">
    <div class="dashboard-logo">
      <a href="admin-dashboard.php">
        <div class="dashboard-logo-icon"><i class="bi bi-plus-square-fill"></i></div>
        <div class="dashboard-logo-text">Med<span>Chain</span></div>
      </a>
    </div>
    <nav class="dashboard-nav">
      <div class="dashboard-nav-title">Navigation</div>
      <a href="admin-dashboard.php" class="dashboard-nav-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="admin-users.php"     class="dashboard-nav-item"><i class="bi bi-people-fill"></i> Utilisateurs</a>
      <a href="admin-create-user.php" class="dashboard-nav-item"><i class="bi bi-person-plus-fill"></i> Nouvel utilisateur</a>
      <a href="admin-reports-statistics.php" class="dashboard-nav-item"><i class="bi bi-graph-up"></i> Statistiques</a>
      <div class="dashboard-nav-title">Médical</div>
      <a href="admin-medecin.php" class="dashboard-nav-item"><i class="bi bi-heart-pulse-fill"></i> Médecins</a>
      <a href="admin-patient.php" class="dashboard-nav-item"><i class="bi bi-person-lines-fill"></i> Patients</a>
      <div class="dashboard-nav-title">Outils</div>
      <a href="admin-chatbot.php" class="dashboard-nav-item active"><i class="bi bi-robot"></i> Assistant IA</a>
      <div class="dashboard-nav-title">Gestion</div>
      <a href="../frontoffice/auth/profile.php" class="dashboard-nav-item"><i class="bi bi-person-circle"></i> Mon profil</a>
      <a href="../../controllers/logout.php" class="dashboard-nav-item logout" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
    </nav>
  </aside>

  <!-- MAIN -->
  <main class="dashboard-main">

    <div class="page-header">
      <h1>
        <i class="bi bi-robot" style="color:var(--green)"></i>
        Assistant IA MedChain
        <span class="ai-badge">Powered by Gemini</span>
      </h1>
      <button class="icon-btn" id="clearBtn" style="background:rgba(239,68,68,.1);color:#EF4444;width:auto;padding:8px 16px;border-radius:var(--radius-md);gap:6px;display:flex;align-items:center;font-size:13px;font-weight:600" title="Effacer la conversation">
        <i class="bi bi-trash3"></i> Effacer
      </button>
    </div>

    <div class="chat-wrapper">

      <!-- Chat header -->
      <div class="chat-header">
        <div class="chat-avatar">
          <i class="bi bi-robot"></i>
          <span class="online-dot"></span>
        </div>
        <div class="chat-header-info">
          <h2>MedChain AI</h2>
          <p>Assistant médical & administratif · En ligne</p>
        </div>
        <div class="chat-header-actions">
          <button class="icon-btn" id="exportBtn" title="Exporter la conversation"><i class="bi bi-download"></i></button>
        </div>
      </div>

      <!-- Messages -->
      <div class="messages-container" id="messagesContainer">
        <div class="welcome-card" id="welcomeCard">
          <div class="welcome-icon"><i class="bi bi-robot"></i></div>
          <h3>Bonjour, <?= htmlspecialchars(trim($admin_name)) ?> 👋</h3>
          <p>Je suis votre assistant IA MedChain. Je peux vous aider à gérer les utilisateurs, analyser les statistiques, répondre à vos questions médicales et administratives.</p>
        </div>
      </div>

      <!-- Suggestions rapides -->
      <div class="suggestions" id="suggestionsArea">
        <button class="suggestion-chip" data-msg="Comment gérer les comptes utilisateurs inactifs ?">👥 Utilisateurs inactifs</button>
        <button class="suggestion-chip" data-msg="Quels sont les indicateurs clés à surveiller pour un admin médical ?">📊 Indicateurs KPI</button>
        <button class="suggestion-chip" data-msg="Comment valider un compte médecin en attente ?">🩺 Valider médecin</button>
        <button class="suggestion-chip" data-msg="Quelles sont les bonnes pratiques de sécurité pour une plateforme médicale ?">🔒 Sécurité données</button>
        <button class="suggestion-chip" data-msg="Comment exporter un rapport PDF des statistiques ?">📄 Export PDF</button>
      </div>

      <!-- Input -->
      <div class="chat-input-area">
        <div class="input-row">
          <textarea class="input-box" id="userInput" placeholder="Posez votre question à l'assistant MedChain..." rows="1"></textarea>
          <button class="send-btn" id="sendBtn" title="Envoyer">
            <i class="bi bi-send-fill"></i>
          </button>
        </div>
        <div class="input-hint"><i class="bi bi-info-circle"></i> Appuyez sur <kbd>Entrée</kbd> pour envoyer · <kbd>Shift+Entrée</kbd> pour aller à la ligne</div>
      </div>

    </div>
  </main>
</div>

<script>
const ADMIN_NAME = <?= json_encode(trim($admin_name)) ?>;
const messagesContainer = document.getElementById('messagesContainer');
const userInput = document.getElementById('userInput');
const sendBtn = document.getElementById('sendBtn');
const welcomeCard = document.getElementById('welcomeCard');
const suggestionsArea = document.getElementById('suggestionsArea');

let conversationHistory = [];
let isLoading = false;

// System prompt
const SYSTEM_PROMPT = `Tu es MedChain AI, un assistant administratif et médical expert intégré à la plateforme MedChain.
Tu aides l'administrateur ${ADMIN_NAME} à gérer la plateforme de dossiers médicaux.

Tes domaines d'expertise :
- Gestion des utilisateurs (patients, médecins, admins)
- Statistiques et rapports de la plateforme
- Bonnes pratiques médicales et RGPD/sécurité des données de santé
- Workflows administratifs (validation comptes médecins, gestion accès)
- Conseils sur la gestion d'une plateforme de santé numérique
- Terminologie médicale et administrative

Directives :
- Réponds toujours en français
- Sois précis, professionnel et bienveillant
- Donne des réponses structurées avec des listes quand c'est utile
- Si une question dépasse tes connaissances, dis-le clairement
- Utilise des emojis avec modération pour rendre les réponses plus lisibles
- Adapte ton niveau de détail à la complexité de la question`;

function getTime() {
  return new Date().toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'});
}

function addMessage(content, role) {
  welcomeCard.style.display = 'none';

  const div = document.createElement('div');
  div.className = `message ${role}`;

  const avatarIcon = role === 'bot' ? 'bi-robot' : 'bi-person-fill';
  const timeStr = getTime();

  // Simple markdown-like rendering
  let html = content
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    .replace(/`(.*?)`/g, '<code style="background:rgba(0,0,0,.08);padding:2px 6px;border-radius:4px;font-family:monospace;font-size:13px">$1</code>')
    .replace(/^### (.*)/gm, '<strong style="display:block;margin-top:8px;color:var(--navy)">$1</strong>')
    .replace(/^## (.*)/gm, '<strong style="display:block;margin-top:8px;font-size:15px;color:var(--navy)">$1</strong>')
    .replace(/^- (.*)/gm, '• $1')
    .replace(/\n/g, '<br>');

  div.innerHTML = `
    <div class="msg-avatar"><i class="bi ${avatarIcon}"></i></div>
    <div>
      <div class="msg-bubble">${html}</div>
      <div class="msg-time">${timeStr}</div>
    </div>`;

  messagesContainer.appendChild(div);
  scrollToBottom();
}

function addTypingIndicator() {
  const div = document.createElement('div');
  div.className = 'typing-indicator';
  div.id = 'typingIndicator';
  div.innerHTML = `
    <div class="msg-avatar" style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green-dark));color:#fff;display:flex;align-items:center;justify-content:center;font-size:16px">
      <i class="bi bi-robot"></i>
    </div>
    <div class="typing-bubble">
      <div class="typing-dot"></div>
      <div class="typing-dot"></div>
      <div class="typing-dot"></div>
    </div>`;
  messagesContainer.appendChild(div);
  scrollToBottom();
}

function removeTypingIndicator() {
  const el = document.getElementById('typingIndicator');
  if (el) el.remove();
}

function scrollToBottom() {
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

async function sendMessage(text) {
  if (isLoading || !text.trim()) return;

  const msg = text.trim();
  userInput.value = '';
  userInput.style.height = 'auto';
  isLoading = true;
  sendBtn.disabled = true;
  suggestionsArea.style.display = 'none';

  addMessage(msg, 'user');
  conversationHistory.push({ role: 'user', content: msg });

  addTypingIndicator();

  try {
    const response = await fetch('chatbot-proxy.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        system: SYSTEM_PROMPT,
        messages: conversationHistory
      })
    });

    const data = await response.json();
    removeTypingIndicator();

    if (!response.ok) {
      addMessage('⚠️ ' + (data.error || 'Erreur serveur ' + response.status), 'bot');
    } else if (data.content && data.content[0]) {
      const reply = data.content[0].text;
      addMessage(reply, 'bot');
      conversationHistory.push({ role: 'assistant', content: reply });
    } else {
      addMessage("Désolé, je n'ai pas pu obtenir une réponse. Veuillez réessayer.", 'bot');
    }
  } catch (err) {
    removeTypingIndicator();
    addMessage('❌ Erreur réseau. Vérifiez que chatbot-proxy.php est accessible sur le serveur.', 'bot');
  }

  isLoading = false;
  sendBtn.disabled = false;
  userInput.focus();
}

// Send button
sendBtn.addEventListener('click', () => sendMessage(userInput.value));

// Enter key
userInput.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage(userInput.value);
  }
});

// Auto-resize textarea
userInput.addEventListener('input', () => {
  userInput.style.height = 'auto';
  userInput.style.height = Math.min(userInput.scrollHeight, 100) + 'px';
});

// Suggestion chips
document.querySelectorAll('.suggestion-chip').forEach(chip => {
  chip.addEventListener('click', () => sendMessage(chip.dataset.msg));
});

// Clear conversation
document.getElementById('clearBtn').addEventListener('click', () => {
  if (conversationHistory.length === 0) return;
  if (!confirm('Effacer toute la conversation ?')) return;
  conversationHistory = [];
  messagesContainer.innerHTML = '';
  messagesContainer.appendChild(welcomeCard);
  welcomeCard.style.display = '';
  suggestionsArea.style.display = 'flex';
});

// Export conversation
document.getElementById('exportBtn').addEventListener('click', () => {
  if (conversationHistory.length === 0) { alert('Aucune conversation à exporter.'); return; }
  let txt = `Conversation MedChain AI — ${new Date().toLocaleString('fr-FR')}\n${'='.repeat(60)}\n\n`;
  conversationHistory.forEach(m => {
    txt += `[${m.role === 'user' ? 'Vous' : 'MedChain AI'}]\n${m.content}\n\n`;
  });
  const a = document.createElement('a');
  a.href = 'data:text/plain;charset=utf-8,' + encodeURIComponent(txt);
  a.download = `medchain-chat-${Date.now()}.txt`;
  a.click();
});
</script>
</body>
</html>