/**
 * AI Question Explainer Client Script (Chatbot Tipe 2: Penjelasan Tiap Soal)
 * AI Online Exam & Proctoring System
 */

(function () {
    'use strict';

    const BASE_URL = window.APP_BASE_URL || (function () {
        const path = window.location.pathname;
        const projectIndex = path.indexOf('/Project2');
        if (projectIndex !== -1) {
            return window.location.origin + path.substring(0, projectIndex + 9);
        }
        return window.location.origin;
    })();

    let modalEl = null;
    let modalInstance = null;
    let chatBody = null;
    let formEl = null;
    let inputEl = null;
    let sendBtn = null;
    let chipsContainer = null;

    let currentParticipantId = 0;
    let currentQuestionId = 0;
    let currentQuestionNum = 0;
    let conversationHistory = [];
    let isRequestInProgress = false;

    document.addEventListener('DOMContentLoaded', function () {
        initQuestionExplainer();
    });

    function initQuestionExplainer() {
        modalEl = document.getElementById('qExplainerModal');
        if (!modalEl) return;

        chatBody = document.getElementById('qExplainerChatBody');
        formEl = document.getElementById('qExplainerForm');
        inputEl = document.getElementById('qExplainerInput');
        sendBtn = document.getElementById('qExplainerSendBtn');
        chipsContainer = document.getElementById('qExplainerChips');

        // Delegate trigger buttons on question cards
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-open-qexplainer');
            if (btn) {
                e.preventDefault();
                openExplainerForQuestion(btn);
            }
        });

        // Form submit
        if (formEl) {
            formEl.addEventListener('submit', function (e) {
                e.preventDefault();
                handleStudentSubmit();
            });
        }

        // Suggestions chips click
        if (chipsContainer) {
            chipsContainer.addEventListener('click', function (e) {
                const chip = e.target.closest('.q-chip');
                if (chip && !isRequestInProgress) {
                    const prompt = chip.getAttribute('data-prompt');
                    if (prompt) {
                        sendStudentMessage(prompt);
                    }
                }
            });
        }
    }

    /**
     * Open Modal and initiate explanation for chosen question
     */
    function openExplainerForQuestion(buttonEl) {
        currentParticipantId = parseInt(buttonEl.getAttribute('data-participant-id'), 10) || 0;
        currentQuestionId = parseInt(buttonEl.getAttribute('data-question-id'), 10) || 0;
        currentQuestionNum = buttonEl.getAttribute('data-question-num') || '1';

        conversationHistory = [];

        // Update modal header & meta
        const titleBadge = document.getElementById('modalQBadge');
        if (titleBadge) titleBadge.textContent = 'Soal #' + currentQuestionNum;

        const textEl = document.getElementById('modalQText');
        const qCard = document.getElementById('q-card-' + currentQuestionId);
        if (textEl && qCard) {
            const rawText = qCard.querySelector('.card-body .fw-semibold')?.textContent || '';
            textEl.textContent = rawText.trim();
        }

        // Reset chat body
        chatBody.innerHTML = '';

        // Show modal
        if (window.bootstrap && window.bootstrap.Modal) {
            modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();
        }

        // Show loading state and fetch initial explanation
        showInitialLoading();
        fetchExplanation('init');
    }

    /**
     * Fetch explanation or reply from backend
     */
    function fetchExplanation(messageText) {
        isRequestInProgress = true;
        setButtonState(true);
        const typingEl = showTypingBubble();

        const csrfToken = getCsrfToken();
        const payload = {
            participant_id: currentParticipantId,
            question_id: currentQuestionId,
            message: messageText,
            history: conversationHistory
        };

        fetch(BASE_URL + '/student/explainQuestion', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(errData => {
                    throw new Error(errData.message || ('HTTP ' + response.status));
                });
            }
            return response.json();
        })
        .then(res => {
            removeTypingBubble(typingEl);
            isRequestInProgress = false;
            setButtonState(false);

            if (res.status === 'success' && res.data) {
                const aiData = res.data;

                // Update question meta snippet in modal
                if (res.question_meta) {
                    const studentAnsEl = document.getElementById('modalQStudentAns');
                    const correctAnsEl = document.getElementById('modalQCorrectAns');
                    const statusEl = document.getElementById('modalQStatus');

                    if (studentAnsEl) studentAnsEl.textContent = res.question_meta.student_answer;
                    if (correctAnsEl) correctAnsEl.textContent = res.question_meta.correct_answer;
                    if (statusEl) {
                        statusEl.innerHTML = res.question_meta.is_correct 
                            ? '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Jawaban Anda Benar</span>' 
                            : '<span class="text-danger fw-bold"><i class="bi bi-x-circle-fill me-1"></i>Jawaban Anda Belum Tepat</span>';
                    }
                }

                // Append AI reply
                appendAiMessage(aiData.reply, aiData.source);

                // Record turn in history
                if (messageText !== 'init') {
                    conversationHistory.push({ role: 'user', text: messageText });
                }
                conversationHistory.push({ role: 'model', text: aiData.reply });
            } else {
                appendAiMessage('Maaf, sistem tidak dapat memuat penjelasan soal saat ini. Silakan coba kembali.');
            }
        })
        .catch(err => {
            removeTypingBubble(typingEl);
            isRequestInProgress = false;
            setButtonState(false);
            appendAiMessage('⚠️ Gagal terhubung ke AI Tutor: ' + (err.message || 'Kesalahan jaringan.'));
        });
    }

    /**
     * Handle follow-up student submit
     */
    function handleStudentSubmit() {
        if (!inputEl || isRequestInProgress) return;
        const msg = inputEl.value.trim();
        if (msg === '') return;

        inputEl.value = '';
        sendStudentMessage(msg);
    }

    function sendStudentMessage(msg) {
        appendUserMessage(msg);
        fetchExplanation(msg);
    }

    /**
     * UI Render Helpers
     */
    function appendUserMessage(text) {
        const row = document.createElement('div');
        row.className = 'd-flex justify-content-end mb-2';
        row.innerHTML = `
            <div class="p-3 rounded-4 shadow-sm text-sm" style="background: #2563eb; color: #ffffff; max-width: 82%; border-bottom-right-radius: 4px;">
                <div class="fw-semibold text-xs text-white-50 mb-1">Pertanyaan Anda</div>
                <div>${escapeHtml(text)}</div>
            </div>
        `;
        chatBody.appendChild(row);
        scrollToBottom();
    }

    function appendAiMessage(text, source) {
        const row = document.createElement('div');
        row.className = 'd-flex justify-content-start mb-2';

        const sourceBadge = (source === 'guardrail')
            ? '<span class="badge bg-warning text-dark text-xs"><i class="bi bi-shield-lock-fill me-1"></i>Guardrail Alert</span>'
            : '<span class="badge bg-primary-subtle text-primary text-xs"><i class="bi bi-stars text-warning me-1"></i>ExamBot AI Tutor</span>';

        row.innerHTML = `
            <div class="d-flex align-items-start gap-2" style="max-width: 88%;">
                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm border mt-1" style="width: 32px; height: 32px; font-size: 1rem; flex-shrink: 0;">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="p-3 rounded-4 bg-white border shadow-sm text-sm text-dark" style="border-bottom-left-radius: 4px;">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                        ${sourceBadge}
                        <span class="text-xs text-muted">${new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })} WIB</span>
                    </div>
                    <div class="ai-rendered-content">${formatMarkdown(text)}</div>
                </div>
            </div>
        `;
        chatBody.appendChild(row);
        scrollToBottom();
    }

    function showInitialLoading() {
        chatBody.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-2" role="status" style="width: 2.2rem; height: 2.2rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="text-xs text-muted fw-semibold">ExamBot sedang menganalisis soal & menyiapkan pembahasan materi...</div>
            </div>
        `;
    }

    function showTypingBubble() {
        const typing = document.createElement('div');
        typing.className = 'd-flex justify-content-start mb-2 typing-indicator-row';
        typing.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm border" style="width: 32px; height: 32px; font-size: 0.9rem;">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="p-2 px-3 rounded-pill bg-white border shadow-sm text-xs text-muted d-flex align-items-center gap-1">
                    <span>AI Tutor sedang mengetik</span>
                    <span class="spinner-grow spinner-grow-sm text-primary" style="width: 6px; height: 6px;" role="status"></span>
                </div>
            </div>
        `;
        chatBody.appendChild(typing);
        scrollToBottom();
        return typing;
    }

    function removeTypingBubble(el) {
        if (el && el.parentNode) {
            el.parentNode.removeChild(el);
        }
    }

    function setButtonState(disabled) {
        if (sendBtn) sendBtn.disabled = disabled;
        if (inputEl) inputEl.disabled = disabled;
    }

    function scrollToBottom() {
        if (chatBody) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }
    }

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
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

    function formatMarkdown(text) {
        if (!text) return '';
        let escaped = escapeHtml(text);

        // Convert ### and ####
        escaped = escaped.replace(/### (.*?)\n/g, '<h5 class="fw-bold text-dark mt-2 mb-1">$1</h5>');
        escaped = escaped.replace(/#### (.*?)\n/g, '<h6 class="fw-bold text-primary mt-2 mb-1">$1</h6>');

        // Bold
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        // Italic
        escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');

        // Bullet lists
        escaped = escaped.replace(/^- (.*?)$/gm, '<li class="ms-3">$1</li>');

        // Line breaks
        escaped = escaped.replace(/\n/g, '<br>');

        return escaped;
    }

})();
