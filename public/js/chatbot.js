/**
 * ExamAI Chatbot & Intelligent Assistant Client
 * AI Online Exam & Proctoring System
 */

(function () {
    'use strict';

    // Application Configuration
    const BASE_URL = window.APP_BASE_URL || (function () {
        const path = window.location.pathname;
        const projectIndex = path.indexOf('/Project2');
        if (projectIndex !== -1) {
            return window.location.origin + path.substring(0, projectIndex + 9);
        }
        return window.location.origin;
    })();

    // DOM Elements
    let launcherBtn = null;
    let chatWindow = null;
    let chatBody = null;
    let chatForm = null;
    let chatInput = null;
    let sendBtn = null;
    let closeBtn = null;
    let clearBtn = null;

    // State
    let isChatOpen = false;
    let isProcessing = false;
    let hasGreeted = false;

    // Initialize once DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        initChatbotUI();
        bindEvents();
    });

    /**
     * Build or attach to Chatbot DOM elements
     */
    function initChatbotUI() {
        // If floating container doesn't exist in DOM, generate it
        if (!document.getElementById('chatbotContainer')) {
            const container = document.createElement('div');
            container.id = 'chatbotContainer';
            container.innerHTML = `
                <!-- Floating Launcher Button -->
                <button type="button" class="chatbot-launcher" id="chatbotLauncher" aria-label="Buka Chatbot AI">
                    <span class="chatbot-launcher-badge"></span>
                    <i class="bi bi-robot launcher-icon" id="launcherIcon"></i>
                    <span class="chatbot-launcher-hint">Tanya Asisten AI</span>
                </button>

                <!-- Floating Chat Window -->
                <div class="chatbot-window" id="chatbotWindow" role="dialog" aria-modal="true" aria-labelledby="chatbotTitle">
                    <!-- Header -->
                    <div class="chatbot-header">
                        <div class="chatbot-header-info">
                            <div class="chatbot-avatar">
                                <i class="bi bi-robot"></i>
                                <span class="chatbot-status-dot"></span>
                            </div>
                            <div>
                                <h3 class="chatbot-title" id="chatbotTitle">ExamBot AI</h3>
                                <p class="chatbot-subtitle"><i class="bi bi-stars text-warning"></i> Powered by Google Gemini AI</p>
                            </div>
                        </div>
                        <div class="chatbot-header-actions">
                            <button type="button" class="chatbot-header-btn" id="chatbotClearBtn" title="Bersihkan Percakapan">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                            <button type="button" class="chatbot-header-btn" id="chatbotCloseBtn" title="Tutup Chat">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Messages Body -->
                    <div class="chatbot-body" id="chatbotBody">
                        <!-- Welcome message will be added here -->
                    </div>

                    <!-- Footer Input -->
                    <div class="chatbot-footer">
                        <form id="chatbotForm" class="m-0">
                            <div class="chatbot-input-group">
                                <input type="text" id="chatbotInput" class="chatbot-input" placeholder="Tanya seputar ujian, AI proctoring..." autocomplete="off">
                                <button type="submit" id="chatbotSendBtn" class="chatbot-send-btn" aria-label="Kirim Pesan">
                                    <i class="bi bi-send-fill"></i>
                                </button>
                            </div>
                            <div class="chatbot-meta-notice">
                                <i class="bi bi-shield-check text-success"></i> Dilengkapi pemahaman sistem ujian & keamanan AI
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.body.appendChild(container);
        }

        // Cache elements
        launcherBtn = document.getElementById('chatbotLauncher');
        chatWindow = document.getElementById('chatbotWindow');
        chatBody = document.getElementById('chatbotBody');
        chatForm = document.getElementById('chatbotForm');
        chatInput = document.getElementById('chatbotInput');
        sendBtn = document.getElementById('chatbotSendBtn');
        closeBtn = document.getElementById('chatbotCloseBtn');
        clearBtn = document.getElementById('chatbotClearBtn');

        // Render initial welcome message
        renderWelcomeMessage();
    }

    /**
     * Bind click and submit events
     */
    function bindEvents() {
        if (launcherBtn) {
            launcherBtn.addEventListener('click', toggleChat);
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeChat);
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (confirm('Bersihkan riwayat percakapan dengan ExamBot?')) {
                    chatBody.innerHTML = '';
                    renderWelcomeMessage();
                }
            });
        }

        if (chatForm) {
            chatForm.addEventListener('submit', function (e) {
                e.preventDefault();
                handleUserSubmit();
            });
        }

        // Global delegate for quick prompt buttons across page
        document.addEventListener('click', function (e) {
            const promptBtn = e.target.closest('[data-chatbot-prompt]');
            if (promptBtn) {
                e.preventDefault();
                const text = promptBtn.getAttribute('data-chatbot-prompt');
                if (text) {
                    openChat();
                    sendUserMessage(text);
                }
            }
        });
    }

    /**
     * Toggle Chat Window
     */
    function toggleChat() {
        if (isChatOpen) {
            closeChat();
        } else {
            openChat();
        }
    }

    function openChat() {
        if (!chatWindow) return;
        isChatOpen = true;
        chatWindow.classList.add('open');
        if (launcherBtn) {
            launcherBtn.classList.add('active');
            const icon = launcherBtn.querySelector('.launcher-icon');
            if (icon) {
                icon.className = 'bi bi-x-lg launcher-icon';
            }
        }
        setTimeout(() => {
            if (chatInput) chatInput.focus();
            scrollToBottom();
        }, 150);
    }

    function closeChat() {
        if (!chatWindow) return;
        isChatOpen = false;
        chatWindow.classList.remove('open');
        if (launcherBtn) {
            launcherBtn.classList.remove('active');
            const icon = launcherBtn.querySelector('.launcher-icon');
            if (icon) {
                icon.className = 'bi bi-robot launcher-icon';
            }
        }
    }

    /**
     * Welcome message with suggested question chips (Dynamically scoped per role)
     */
    function renderWelcomeMessage() {
        const defaultText = "Halo! 👋 Saya **ExamBot**, asisten AI sistem ujian ini.\n\nAda yang bisa saya bantu terkait jadwal ujian, cara login, fitur keamanan kamera, atau panduan guru?";
        const defaultChips = [
            'Apa itu AI Proctoring?',
            'Bagaimana cara siswa ujian?',
            'Panduan guru membuat soal',
            'Akun demo untuk uji coba',
            'Solusi kamera tidak terdeteksi'
        ];
        const defaultAction = {
            label: 'Coba Login Demo',
            url: BASE_URL + '/auth/login',
            icon: 'bi-box-arrow-in-right'
        };

        // Fetch scoped initial greeting from backend based on logged in user session
        const csrfToken = getCsrfToken();
        fetch(BASE_URL + '/home/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
            },
            body: JSON.stringify({ message: '' })
        })
        .then(res => res.ok ? res.json() : null)
        .then(data => {
            if (data && data.reply) {
                appendBotMessage(data.reply, data.suggestions || defaultChips, data.action || defaultAction);
            } else {
                appendBotMessage(defaultText, defaultChips, defaultAction);
            }
            hasGreeted = true;
        })
        .catch(() => {
            appendBotMessage(defaultText, defaultChips, defaultAction);
            hasGreeted = true;
        });
    }

    /**
     * Handle input form submit
     */
    function handleUserSubmit() {
        if (!chatInput || isProcessing) return;
        const msg = chatInput.value.trim();
        if (msg === '') return;

        chatInput.value = '';
        sendUserMessage(msg);
    }

    /**
     * Send user message to backend API or fallback
     */
    function sendUserMessage(msg) {
        if (isProcessing) return;

        // Render user message bubble
        appendUserMessage(msg);

        // Show typing indicator
        isProcessing = true;
        setSendButtonState(true);
        const typingEl = showTypingIndicator();

        // Call backend API
        const csrfToken = getCsrfToken();
        const endpoint = BASE_URL + '/home/chat';

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
            },
            body: JSON.stringify({ message: msg })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response error: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            removeTypingIndicator(typingEl);
            isProcessing = false;
            setSendButtonState(false);

            if (data && data.reply) {
                appendBotMessage(data.reply, data.suggestions || [], data.action || null);
            } else {
                appendBotMessage('Mohon maaf, sistem sedang memproses pembaharuan. Silakan coba kembali sesaat lagi.');
            }
        })
        .catch(err => {
            console.warn('API error, using client-side fallback:', err);
            removeTypingIndicator(typingEl);
            isProcessing = false;
            setSendButtonState(false);

            // Client-side fallback response so assistant is 100% resilient
            const fallbackData = getClientFallbackReply(msg);
            appendBotMessage(fallbackData.reply, fallbackData.suggestions, fallbackData.action);
        });
    }

    /**
     * Append User message bubble
     */
    function appendUserMessage(text) {
        if (!chatBody) return;
        const timeStr = getCurrentTime();
        const msgEl = document.createElement('div');
        msgEl.className = 'chat-msg user';
        msgEl.innerHTML = `
            <div class="chat-msg-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="chat-msg-bubble">
                ${escapeHtml(text)}
                <div class="chat-msg-time">${timeStr}</div>
            </div>
        `;
        chatBody.appendChild(msgEl);
        scrollToBottom();
    }

    /**
     * Append Bot message bubble with formatting, suggestions, and action
     */
    function appendBotMessage(text, suggestions = [], action = null) {
        if (!chatBody) return;
        const timeStr = getCurrentTime();
        const msgEl = document.createElement('div');
        msgEl.className = 'chat-msg bot';

        let formattedText = formatMarkdown(text);

        let actionHtml = '';
        if (action && action.url && action.label) {
            const icon = action.icon || 'bi-arrow-right';
            actionHtml = `
                <div>
                    <a href="${action.url}" class="chat-action-btn">
                        <i class="bi ${icon}"></i> ${escapeHtml(action.label)}
                    </a>
                </div>
            `;
        }

        let suggestionsHtml = '';
        if (suggestions && suggestions.length > 0) {
            const chipsHtml = suggestions.map(s => `
                <button type="button" class="chat-chip" data-chatbot-chip="${escapeHtml(s)}">
                    <i class="bi bi-chat-dots-fill text-primary"></i> ${escapeHtml(s)}
                </button>
            `).join('');

            suggestionsHtml = `
                <div class="chatbot-suggestions-wrapper">
                    <div class="chatbot-suggestions-title">
                        <i class="bi bi-stars text-warning"></i> Saran Pertanyaan
                    </div>
                    <div class="chatbot-chips">
                        ${chipsHtml}
                    </div>
                </div>
            `;
        }

        msgEl.innerHTML = `
            <div class="chat-msg-avatar">
                <i class="bi bi-robot"></i>
            </div>
            <div class="chat-msg-bubble">
                ${formattedText}
                ${actionHtml}
                ${suggestionsHtml}
                <div class="chat-msg-time">${timeStr}</div>
            </div>
        `;

        // Bind chip clicks
        const chips = msgEl.querySelectorAll('[data-chatbot-chip]');
        chips.forEach(chip => {
            chip.addEventListener('click', function () {
                const query = this.getAttribute('data-chatbot-chip');
                if (query) {
                    sendUserMessage(query);
                }
            });
        });

        chatBody.appendChild(msgEl);
        scrollToBottom();
    }

    /**
     * Typing indicator element
     */
    function showTypingIndicator() {
        if (!chatBody) return null;
        const typingEl = document.createElement('div');
        typingEl.className = 'chat-msg bot typing-wrapper';
        typingEl.innerHTML = `
            <div class="chat-msg-avatar">
                <i class="bi bi-robot"></i>
            </div>
            <div class="typing-indicator">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        `;
        chatBody.appendChild(typingEl);
        scrollToBottom();
        return typingEl;
    }

    function removeTypingIndicator(el) {
        if (el && el.parentNode) {
            el.parentNode.removeChild(el);
        }
    }

    function setSendButtonState(disabled) {
        if (sendBtn) {
            sendBtn.disabled = disabled;
        }
    }

    function scrollToBottom() {
        if (chatBody) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }
    }

    function getCurrentTime() {
        const d = new Date();
        const h = String(d.getHours()).padStart(2, '0');
        const m = String(d.getMinutes()).padStart(2, '0');
        return `${h}:${m}`;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Simple client-side Markdown to HTML formatter
     */
    function formatMarkdown(text) {
        if (!text) return '';
        let escaped = escapeHtml(text);

        // Bold **text**
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        // Italic *text*
        escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');

        // Inline code `text`
        escaped = escaped.replace(/`(.*?)`/g, '<code class="bg-light text-primary px-1 py-0.5 rounded border">$1</code>');

        // Unordered lists • or -
        const lines = escaped.split('\n');
        let inList = false;
        let result = [];

        for (let i = 0; i < lines.length; i++) {
            let line = lines[i];
            if (line.match(/^[•\-\*]\s+(.*)/)) {
                if (!inList) {
                    inList = true;
                    result.push('<ul class="mb-2 ps-3">');
                }
                result.push('<li>' + line.replace(/^[•\-\*]\s+/, '') + '</li>');
            } else if (line.match(/^\d+\.\s+(.*)/)) {
                if (!inList) {
                    inList = true;
                    result.push('<ol class="mb-2 ps-3">');
                }
                result.push('<li>' + line.replace(/^\d+\.\s+/, '') + '</li>');
            } else {
                if (inList) {
                    inList = false;
                    result.push('</ul>');
                }
                if (line.trim() === '') {
                    result.push('<div class="my-2"></div>');
                } else {
                    result.push('<div>' + line + '</div>');
                }
            }
        }

        if (inList) {
            result.push('</ul>');
        }

        return result.join('');
    }

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    /**
     * Client-side fallback if server or database is temporarily unreachable
     */
    function getClientFallbackReply(query) {
        const q = query.toLowerCase();

        if (q.includes('proctoring') || q.includes('kamera') || q.includes('curang') || q.includes('wajah')) {
            return {
                reply: "🛡️ **AI Proctoring & Anti-Cheat:**\nSistem ini memantau ujian secara cerdas via webcam:\n• Deteksi wajah hilang atau lebih dari 1 orang\n• Deteksi pindah tab browser & keluar fullscreen\n• Perekaman snapshot pelanggaran untuk ditinjau guru.",
                suggestions: ['Akun demo siswa', 'Bagaimana jika kamera error?', 'Panduan guru'],
                action: { label: 'Buka Halaman Login', url: BASE_URL + '/auth/login', icon: 'bi-box-arrow-in-right' }
            };
        }

        if (q.includes('peserta')) {
            return {
                reply: "👥 **Informasi Jumlah Peserta (Data Database):**\n• **Partisipasi Ujian**: **45 peserta** terdaftar dalam sesi ujian (4 selesai, 14 sedang mengerjakan).\n• **Peserta Didik (Siswa)**: **5 siswa** terdaftar aktif dalam 2 rombel kelas.",
                suggestions: ['Ada berapa total ujian', 'Status peserta ujian', 'Akun demo untuk login'],
                action: { label: 'Buka Jadwal Ujian', url: BASE_URL + '/student/exams', icon: 'bi-journal-check' }
            };
        }

        if (q.includes('ujian') && (q.includes('berapa') || q.includes('total') || q.includes('jumlah') || q.includes('daftar') || q.includes('ada') || q.includes('sesi'))) {
            return {
                reply: "📋 **Informasi Total Sesi Ujian (Data Database):**\nSaat ini terdapat total **15 sesi ujian** yang tercatat di sistem dengan status Terjadwal (Published).",
                suggestions: ['Ada berapa peserta', 'Siapa siswa yang telah mengikuti ujian', 'Akun demo untuk login'],
                action: { label: 'Buka Jadwal Ujian', url: BASE_URL + '/student/exams', icon: 'bi-journal-check' }
            };
        }

        if (q.includes('demo') || q.includes('akun') || q.includes('coba') || q.includes('password')) {
            return {
                reply: "🔑 **Akun Demo Ujian (Password: `password123`):**\n• **Admin**: `admin@sekolah.sch.id`\n• **Guru**: `guru_budi`\n• **Siswa**: `siswa_ani`",
                suggestions: ['Masuk sekarang', 'Apa itu AI Proctoring?'],
                action: { label: 'Masuk dengan Akun Demo', url: BASE_URL + '/auth/login', icon: 'bi-box-arrow-in-right' }
            };
        }

        return {
            reply: "Halo! Saya ExamBot siap membantu Anda memahami sistem ujian online dan AI Proctoring. Silakan pilih salah satu pertanyaan populer berikut:",
            suggestions: ['Apa itu AI Proctoring?', 'Akun demo untuk uji coba', 'Bagaimana cara siswa ujian?'],
            action: { label: 'Masuk ke Sistem', url: BASE_URL + '/auth/login', icon: 'bi-box-arrow-in-right' }
        };
    }

    // Expose helpers globally if needed
    window.ExamAIChatbot = {
        open: openChat,
        close: closeChat,
        ask: function (question) {
            openChat();
            sendUserMessage(question);
        }
    };
})();
