<?php require_once BASE_PATH . '/views/templates/front/header.php'; ?>

<style>
/* ── Chat layout ── */
.chat-wrap {
    max-width: 780px;
    margin: 0 auto;
}
.chat-header {
    background: linear-gradient(135deg, var(--green), var(--green-dark));
    border-radius: var(--radius-xl) var(--radius-xl) 0 0;
    padding: 22px 28px;
    display: flex;
    align-items: center;
    gap: 14px;
    color: #fff;
}
.chat-header-icon {
    width: 48px; height: 48px;
    background: rgba(255,255,255,.2);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
}
.chat-header h2 { margin: 0; font-family: 'Syne', sans-serif; font-size: 18px; }
.chat-header p  { margin: 2px 0 0; font-size: 13px; opacity: .85; }

.chat-body {
    background: #fff;
    border: 1px solid var(--gray-200);
    border-top: none;
    height: 420px;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    scroll-behavior: smooth;
}

/* Messages */
.msg { display: flex; gap: 10px; max-width: 85%; }
.msg.user  { align-self: flex-end; flex-direction: row-reverse; }
.msg.bot   { align-self: flex-start; }

.msg-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0;
}
.msg.bot  .msg-avatar { background: linear-gradient(135deg, var(--green), var(--green-dark)); color: #fff; }
.msg.user .msg-avatar { background: var(--navy); color: #fff; }

.msg-bubble {
    padding: 11px 16px;
    border-radius: 18px;
    font-size: 14px;
    line-height: 1.55;
}
.msg.bot  .msg-bubble { background: var(--green-light); color: var(--navy); border-bottom-left-radius: 4px; }
.msg.user .msg-bubble { background: var(--navy); color: #fff; border-bottom-right-radius: 4px; }

/* Typing indicator */
.typing-dots span {
    display: inline-block;
    width: 7px; height: 7px;
    background: var(--green);
    border-radius: 50%;
    margin: 0 2px;
    animation: bounce 1.2s infinite;
}
.typing-dots span:nth-child(2) { animation-delay: .2s; }
.typing-dots span:nth-child(3) { animation-delay: .4s; }
@keyframes bounce {
    0%,80%,100% { transform: translateY(0); }
    40%          { transform: translateY(-7px); }
}

/* Object cards inside chat */
.chat-objects {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 8px;
}
.chat-obj-card {
    background: #fff;
    border: 1px solid rgba(29,158,117,.25);
    border-radius: var(--radius-md);
    padding: 10px 14px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--navy);
    transition: box-shadow .2s;
}
.chat-obj-card:hover { box-shadow: 0 4px 14px rgba(29,158,117,.18); }
.chat-obj-card img {
    width: 40px; height: 40px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
}
.chat-obj-card .obj-name { font-weight: 600; }
.chat-obj-card .obj-type { font-size: 11px; color: var(--gray-500); }

/* Input bar */
.chat-input-bar {
    background: #f8fafc;
    border: 1px solid var(--gray-200);
    border-top: none;
    border-radius: 0 0 var(--radius-xl) var(--radius-xl);
    padding: 16px 20px;
    display: flex;
    gap: 10px;
    align-items: center;
}
#chatInput {
    flex: 1;
    border: 1.5px solid var(--gray-200);
    border-radius: 24px;
    padding: 11px 18px;
    font-size: 14px;
    font-family: inherit;
    outline: none;
    transition: border-color .2s;
    background: #fff;
}
#chatInput:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,158,117,.1); }
#sendBtn {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--green), var(--green-dark));
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 17px;
    transition: transform .2s, box-shadow .2s;
    box-shadow: 0 3px 10px rgba(29,158,117,.3);
    flex-shrink: 0;
}
#sendBtn:hover { transform: scale(1.08); box-shadow: 0 5px 16px rgba(29,158,117,.4); }
#sendBtn:disabled { opacity: .5; cursor: not-allowed; transform: none; }

/* Suggestions */
.suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 16px;
}
.suggestion-chip {
    background: var(--green-light);
    color: var(--green-dark);
    border: 1px solid rgba(29,158,117,.2);
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 13px;
    cursor: pointer;
    transition: background .2s;
}
.suggestion-chip:hover { background: rgba(29,158,117,.2); }
</style>

<div class="chat-wrap">

    <!-- Header -->
    <div class="chat-header">
        <div class="chat-header-icon"><i class="bi bi-robot"></i></div>
        <div>
            <h2>Assistant MedChain</h2>
            <p>Décrivez comment vous vous sentez — je trouverai ce qu'il vous faut.</p>
        </div>
    </div>

    <!-- Messages -->
    <div class="chat-body" id="chatBody">
        <div class="msg bot">
            <div class="msg-avatar"><i class="bi bi-robot"></i></div>
            <div class="msg-bubble">
                Bonjour <?php echo htmlspecialchars($_SESSION['user_prenom'] ?? 'ami(e)', ENT_QUOTES, 'UTF-8'); ?> 👋<br>
                Comment vous sentez-vous aujourd'hui ? Décrivez votre état et je vous suggérerai des activités adaptées.
            </div>
        </div>
    </div>

    <!-- Input -->
    <div class="chat-input-bar">
        <input type="text" id="chatInput" placeholder="Ex : Je me sens stressé et j'ai du mal à dormir…" autocomplete="off">
        <button id="sendBtn" title="Envoyer"><i class="bi bi-send-fill"></i></button>
    </div>

</div>

<!-- Quick suggestions -->
<div class="chat-wrap">
    <div class="suggestions" id="suggestions">
        <span class="suggestion-chip">😴 Je suis fatigué(e)</span>
        <span class="suggestion-chip">😰 Je me sens stressé(e)</span>
        <span class="suggestion-chip">😔 Je m'ennuie</span>
        <span class="suggestion-chip">🧩 Je veux réfléchir</span>
        <span class="suggestion-chip">🎬 Je veux regarder un film</span>
        <span class="suggestion-chip">🏃 Je veux bouger</span>
    </div>
</div>

<script>
(function () {
    const body    = document.getElementById('chatBody');
    const input   = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    const chips   = document.querySelectorAll('.suggestion-chip');

    const ENDPOINT = '/midchaine/index1.php?controller=chat&action=message&office=front';

    function scrollBottom() {
        body.scrollTop = body.scrollHeight;
    }

    function appendMsg(role, html) {
        const isUser = role === 'user';
        const div = document.createElement('div');
        div.className = 'msg ' + role;
        div.innerHTML = `
            <div class="msg-avatar">
                <i class="bi bi-${isUser ? 'person-fill' : 'robot'}"></i>
            </div>
            <div class="msg-bubble">${html}</div>`;
        body.appendChild(div);
        scrollBottom();
        return div;
    }

    function showTyping() {
        const div = document.createElement('div');
        div.className = 'msg bot';
        div.id = 'typingIndicator';
        div.innerHTML = `
            <div class="msg-avatar"><i class="bi bi-robot"></i></div>
            <div class="msg-bubble typing-dots">
                <span></span><span></span><span></span>
            </div>`;
        body.appendChild(div);
        scrollBottom();
    }

    function removeTyping() {
        const el = document.getElementById('typingIndicator');
        if (el) el.remove();
    }

    function buildObjectCards(objects) {
        if (!objects || objects.length === 0) return '';
        let html = '<div class="chat-objects">';
        objects.forEach(obj => {
            const img = obj.image_url
                ? `<img src="${escHtml(obj.image_url)}" alt="${escHtml(obj.nom_objet)}">`
                : `<div style="width:40px;height:40px;border-radius:8px;background:var(--green-light);display:flex;align-items:center;justify-content:center;"><i class="bi bi-box-seam" style="color:var(--green);"></i></div>`;
            html += `
                <div class="chat-obj-card">
                    ${img}
                    <div>
                        <div class="obj-name">${escHtml(obj.nom_objet)}</div>
                        <div class="obj-type">${escHtml(obj.type_objet)}</div>
                    </div>
                </div>`;
        });
        html += '</div>';
        return html;
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    async function sendMessage(text) {
        text = text.trim();
        if (!text) return;

        appendMsg('user', escHtml(text));
        input.value = '';
        sendBtn.disabled = true;
        showTyping();

        try {
            const res  = await fetch(ENDPOINT, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ message: text }),
            });
            const data = await res.json();
            removeTyping();

            if (data.error) {
                appendMsg('bot', `<span style="color:#DC2626;">${escHtml(data.error)}</span>`);
            } else {
                const cards = buildObjectCards(data.objects);
                appendMsg('bot', escHtml(data.reply) + cards);
            }
        } catch (e) {
            removeTyping();
            appendMsg('bot', '<span style="color:#DC2626;">Une erreur réseau est survenue. Veuillez réessayer.</span>');
        } finally {
            sendBtn.disabled = false;
            input.focus();
        }
    }

    sendBtn.addEventListener('click', () => sendMessage(input.value));
    input.addEventListener('keydown', e => { if (e.key === 'Enter') sendMessage(input.value); });
    chips.forEach(chip => chip.addEventListener('click', () => sendMessage(chip.textContent.replace(/^[^\w]+/, '').trim())));
})();
</script>

<?php require_once BASE_PATH . '/views/templates/front/footer.php'; ?>
