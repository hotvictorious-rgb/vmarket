@extends('layouts.admin.app')

@section('title', translate('WhatsApp_Enterprise_CRM'))

@push('css_or_js')
<style>
    :root {
        --vmarket-purple: #4A154B;
        --vmarket-purple-dark: #2D0B31;
        --vmarket-gold: #FFB800;
        --vmarket-gold-dark: #D4AF37;
        --vmarket-chat-bg: #EFEAE2;
    }
    .crm-wrapper {
        height: calc(100vh - 160px);
        min-height: 650px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    .crm-sidebar {
        border-right: 1px solid #e9ecef;
        height: 100%;
        overflow-y: auto;
    }
    .crm-thread-item {
        padding: 14px 16px;
        border-bottom: 1px solid #f1f3f5;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .crm-thread-item:hover, .crm-thread-item.active {
        background-color: rgba(74, 21, 75, 0.05);
        border-left: 4px solid var(--vmarket-purple);
    }
    .crm-chat-area {
        height: 100%;
        display: flex;
        flex-direction: column;
        background-color: var(--vmarket-chat-bg);
        background-image: radial-gradient(#d1d7db 1px, transparent 1px);
        background-size: 20px 20px;
    }
    .crm-chat-header {
        padding: 14px 20px;
        background: #fff;
        border-bottom: 1px solid #e9ecef;
    }
    .crm-messages-container {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .msg-bubble {
        max-width: 68%;
        padding: 10px 14px;
        border-radius: 10px;
        font-size: 14px;
        line-height: 1.45;
        position: relative;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    .msg-customer {
        align-self: flex-start;
        background: #fff;
        border-bottom-left-radius: 2px;
    }
    .msg-agent {
        align-self: flex-end;
        background: #D9FDD3;
        border-bottom-right-radius: 2px;
    }
    .msg-bot {
        align-self: flex-end;
        background: #E8E5F8;
        border-bottom-right-radius: 2px;
        border-left: 3px solid var(--vmarket-purple);
    }
    .msg-time {
        font-size: 11px;
        color: #8696a0;
        margin-top: 4px;
        text-align: right;
    }
    .crm-chat-input-bar {
        padding: 14px 20px;
        background: #f0f2f5;
        border-top: 1px solid #e9ecef;
    }
    .crm-dossier-sidebar {
        border-left: 1px solid #e9ecef;
        height: 100%;
        overflow-y: auto;
        background: #fafbfc;
        padding: 18px;
    }
    .vip-badge {
        background: linear-gradient(135deg, #FFB800, #D4AF37);
        color: #2D0B31;
        font-weight: 700;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 12px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="h1 mb-0 d-flex align-items-center gap-2" style="color: var(--vmarket-purple);">
                <i class="tio-chat-outlined"></i> {{ translate('WhatsApp_Enterprise_CRM') }}
            </h2>
            <p class="text-muted fs-12 mb-0">{{ translate('Multi-Agent Team Inbox, AI Sales Copilot & Customer 360° Memory') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.whatsapp-crm.broadcasts') }}" class="btn btn-outline-primary">
                <i class="tio-speakerphone"></i> {{ translate('Broadcast_Campaigns') }}
            </a>
            <a href="{{ route('admin.whatsapp-crm.ai-settings') }}" class="btn btn--primary" style="background-color: var(--vmarket-purple); border-color: var(--vmarket-purple);">
                <i class="tio-settings"></i> {{ translate('AI_Brain_&_Settings') }}
            </a>
        </div>
    </div>

    <!-- Main 3-Pane Interface -->
    <div class="crm-wrapper row g-0">
        <!-- Left Pane: Conversation Queue -->
        <div class="col-lg-3 crm-sidebar">
            <div class="p-3 border-bottom bg-white sticky-top">
                <div class="input-group input-group-merge mb-2">
                    <input type="text" id="chatSearchInput" class="form-control form-control-sm" placeholder="{{ translate('Search_name_or_phone...') }}">
                </div>
                <div class="d-flex gap-1">
                    <a href="{{ route('admin.whatsapp-crm.index', ['status' => 'all']) }}" class="btn btn-xs {{ $status == 'all' ? 'btn-primary' : 'btn-ghost-secondary' }}">{{ translate('All') }}</a>
                    <a href="{{ route('admin.whatsapp-crm.index', ['status' => 'open']) }}" class="btn btn-xs {{ $status == 'open' ? 'btn-primary' : 'btn-ghost-secondary' }}">
                        {{ translate('Open') }} <span class="badge bg-danger ms-1">{{ $openCount }}</span>
                    </a>
                    <a href="{{ route('admin.whatsapp-crm.index', ['status' => 'bot_handling']) }}" class="btn btn-xs {{ $status == 'bot_handling' ? 'btn-primary' : 'btn-ghost-secondary' }}">
                        {{ translate('AI Bot') }} ({{ $botCount }})
                    </a>
                </div>
            </div>

            <div id="conversationsList">
                @forelse($conversations as $conv)
                    <div class="crm-thread-item" onclick="loadConversation({{ $conv->id }})" id="conv-item-{{ $conv->id }}">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h5 class="mb-0 fs-13 font-weight-bold text-truncate" style="max-width: 140px;">
                                {{ $conv->customer_name ?: $conv->phone }}
                            </h5>
                            <span class="fs-10 text-muted">{{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(null, true) : '' }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="fs-12 text-muted mb-0 text-truncate" style="max-width: 170px;">
                                {{ $conv->latestMessage?->message_body ?: translate('No_messages_yet') }}
                            </p>
                            @if($conv->unread_agent_count > 0)
                                <span class="badge rounded-pill bg-success">{{ $conv->unread_agent_count }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="tio-chat-outlined fs-30 mb-2"></i>
                        <p class="fs-13">{{ translate('No_conversations_found') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Center Pane: Chat Stream -->
        <div class="col-lg-6 crm-chat-area">
            <div class="crm-chat-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-circle avatar-sm" style="background: var(--vmarket-purple); color: #fff; display: flex; align-items: center; justify-content: center;">
                        <span id="activeAvatarText">VM</span>
                    </div>
                    <div>
                        <h5 class="mb-0 fs-14" id="activeChatName">{{ translate('Select_a_conversation') }}</h5>
                        <span class="fs-11 text-muted" id="activeChatPhone">{{ translate('WhatsApp_Verified_Channel') }}</span>
                    </div>
                </div>
                <div id="chatActionButtons" class="d-none d-flex align-items-center gap-2">
                    <select id="agentSelect" class="form-select form-select-sm" style="max-width: 150px; font-size: 11px;" onchange="reassignToAgent(this.value)">
                        <option value="">{{ translate('Assign Agent...') }}</option>
                        @foreach($agents ?? [] as $ag)
                            <option value="{{ $ag->id }}">{{ $ag->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-xs btn-outline-danger" onclick="toggleBotHandling()">
                        <i class="tio-android-robot"></i> <span id="botToggleText">{{ translate('Handover_to_Bot') }}</span>
                    </button>
                    <button class="btn btn-xs btn-outline-success" onclick="markResolved()">
                        <i class="tio-checkmark-circle"></i> {{ translate('Resolve') }}
                    </button>
                </div>
            </div>

            <div class="crm-messages-container" id="messagesContainer">
                <div class="text-center py-5 text-muted m-auto">
                    <i class="tio-whatsapp-outlined fs-40 mb-2" style="color: var(--vmarket-purple);"></i>
                    <h5>{{ translate('Select_a_Customer_to_Start_Chatting') }}</h5>
                    <p class="fs-12">{{ translate('Official_Single_Number_Multi_Agent_Support_System') }}</p>
                </div>
            </div>

            <div class="crm-chat-input-bar">
                <form id="sendMessageForm" onsubmit="event.preventDefault(); submitMessage();">
                    <div class="input-group">
                        <input type="text" id="messageInput" class="form-control" placeholder="{{ translate('Type_a_message_as_Victorious_MARKET...') }}" disabled>
                        <button type="submit" id="sendBtn" class="btn btn--primary" style="background: var(--vmarket-purple); border-color: var(--vmarket-purple);" disabled>
                            <i class="tio-send"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Pane: Customer 360° Memory & Intelligence -->
        <div class="col-lg-3 crm-dossier-sidebar">
            <h5 class="mb-3 d-flex align-items-center gap-1 font-weight-bold" style="color: var(--vmarket-purple);">
                <i class="tio-user"></i> {{ translate('Customer_360°_Intelligence') }}
            </h5>

            <div id="dossierContent" class="text-muted fs-12 text-center py-5">
                <i class="tio-info-outined fs-25 mb-1"></i>
                <p>{{ translate('Select_chat_to_view_customer_past,_present_&_AI_memory') }}</p>
            </div>
        </div>
    </div>
</div>

<script>
    let activeConversationId = null;

    function loadConversation(id) {
        activeConversationId = id;
        document.querySelectorAll('.crm-thread-item').forEach(el => el.classList.remove('active'));
        document.getElementById('conv-item-' + id)?.classList.add('active');

        fetch("{{ url('admin/whatsapp-crm/messages') }}/" + id)
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    renderChatHeader(data.conversation);
                    renderMessages(data.messages);
                    renderDossier(data.dossier, data.conversation);
                    document.getElementById('messageInput').disabled = false;
                    document.getElementById('sendBtn').disabled = false;
                    document.getElementById('chatActionButtons').classList.remove('d-none');
                }
            });
    }

    function renderChatHeader(conv) {
        document.getElementById('activeChatName').innerText = conv.customer_name || conv.phone;
        document.getElementById('activeChatPhone').innerText = conv.phone + ' • ' + (conv.status === 'bot_handling' ? '🤖 AI Active' : '👩‍💼 Human Agent');
        document.getElementById('botToggleText').innerText = conv.status === 'bot_handling' ? 'Disable Bot' : 'Enable Bot';
    }

    function renderMessages(messages) {
        const container = document.getElementById('messagesContainer');
        container.innerHTML = '';

        messages.forEach(msg => {
            const div = document.createElement('div');
            let bubbleClass = 'msg-customer';
            if (msg.sender_type === 'agent') bubbleClass = 'msg-agent';
            if (msg.sender_type === 'bot') bubbleClass = 'msg-bot';

            div.className = `msg-bubble ${bubbleClass}`;
            div.innerHTML = `
                <div>${msg.message_body || ''}</div>
                <div class="msg-time">${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} • ${msg.delivery_status}</div>
            `;
            container.appendChild(div);
        });

        container.scrollTop = container.scrollHeight;
    }

    function renderDossier(dossier, conv) {
        const panel = document.getElementById('dossierContent');
        panel.className = 'text-dark fs-12';
        
        let ordersHtml = '';
        if (dossier.active_orders && dossier.active_orders.length > 0) {
            dossier.active_orders.forEach(ord => {
                if (ord.receipt_image || ord.bank_session_id) {
                    let meta = ord.receipt_metadata || {};
                    let isDup = meta.is_duplicate ? '<span class="text-danger font-weight-bold">🚨 DUPLICATE DETECTED</span>' : '<span class="text-success">✅ Unique Ref</span>';
                    let isMatch = meta.is_amount_matched ? '<span class="text-success">✅ Amount Match</span>' : '<span class="text-warning">⚠️ Mismatch</span>';

                ordersHtml += `
                    <div class="p-2 mb-2 rounded border bg-white border-warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-dark fs-12">#${ord.id} (${ord.order_type})</strong>
                            <span class="badge bg-warning">${ord.status}</span>
                        </div>
                        <div class="text-muted fs-11 mt-1">Total: ₦${ord.order_amount.toLocaleString()}</div>
                        
                        <div class="p-2 mt-2 rounded bg-light border">
                            <div class="font-weight-bold fs-11 text-primary mb-1">💸 Transfer Receipt Review:</div>
                            <div class="fs-10">Ref: <code>${ord.bank_session_id || 'N/A'}</code> (${isDup})</div>
                            <div class="fs-10">AI Check: ${isMatch}</div>
                            ${ord.receipt_image ? `<a href="${ord.receipt_image}" target="_blank" class="fs-10 text-primary d-block mt-1">🔍 View Uploaded Receipt</a>` : ''}
                            
                            <div class="d-flex gap-1 mt-2">
                                <button class="btn btn-xs btn-success w-100" onclick="verifyReceipt(${ord.id})">✅ Approve Payment</button>
                                <button class="btn btn-xs btn-outline-danger w-100" onclick="rejectReceipt(${ord.id})">❌ Reject</button>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                ordersHtml += `
                    <div class="p-2 mb-2 rounded border bg-white">
                        <div class="d-flex justify-content-between">
                            <strong>#${ord.id} (${ord.order_type})</strong>
                            <span class="badge bg-warning">${ord.status}</span>
                        </div>
                        <div class="text-muted fs-11 mt-1">Total: ₦${ord.order_amount.toLocaleString()}</div>
                        <div class="text-muted fs-11">OTP: <strong class="text-primary">${ord.verification_code}</strong></div>
                    </div>
                `;
            }
            });
        } else {
            ordersHtml = '<div class="text-muted fs-11">No active pending orders.</div>';
        }

        let ticketsHtml = '';
        if (dossier.support_tickets && dossier.support_tickets.length > 0) {
            dossier.support_tickets.forEach(t => {
                let badgeClass = t.status === 'open' ? 'bg-danger' : (t.status === 'pending' ? 'bg-warning' : 'bg-success');
                ticketsHtml += `
                    <div class="p-2 mb-2 rounded border bg-white">
                        <div class="d-flex justify-content-between font-weight-bold">
                            <span class="text-truncate" style="max-width: 140px;" title="${t.subject}">#${t.id}: ${t.subject}</span>
                            <span class="badge ${badgeClass}">${t.status}</span>
                        </div>
                        <div class="d-flex justify-content-between text-muted fs-10 mt-1">
                            <span>Priority: <strong>${t.priority}</strong></span>
                            <span>${t.created_at}</span>
                        </div>
                        <a href="{{ url('admin/support-ticket/single-ticket') }}/${t.id}" target="_blank" class="fs-10 text-primary mt-1 d-inline-block">View Full Ticket &rarr;</a>
                    </div>
                `;
            });
        } else {
            ticketsHtml = '<div class="text-muted fs-11">No support tickets found.</div>';
        }

        let memoriesHtml = '';
        if (dossier.episodic_memory && dossier.episodic_memory.length > 0) {
            dossier.episodic_memory.forEach(m => {
                memoriesHtml += `<div class="badge bg-light text-dark border p-1 mb-1 me-1 fs-10 text-wrap text-start d-block">📌 ${m.fact}</div>`;
            });
        } else {
            memoriesHtml = '<div class="text-muted fs-10">No custom episodic memory points yet.</div>';
        }

        panel.innerHTML = `
            <div class="text-center pb-3 border-bottom mb-3">
                <h4 class="mb-1">${dossier.name}</h4>
                <span class="vip-badge">${dossier.loyalty_tier}</span>
                <p class="text-muted fs-11 mt-1 mb-0">${dossier.phone}</p>
                <div class="mt-2 text-primary font-weight-bold">LTV Spend: ₦${dossier.total_spend.toLocaleString()}</div>
                <div class="mt-2 p-2 rounded text-white font-weight-bold fs-12" style="background-color: var(--vmarket-purple);">
                    💳 Wallet Balance: ₦${(dossier.wallet_balance || 0).toLocaleString()}
                </div>
            </div>

            <h6 class="font-weight-bold text-uppercase fs-11 text-muted mb-2">🧠 AI Episodic Memory & Preferences</h6>
            <div class="p-2 mb-3 rounded bg-white border">
                <div><strong>Tone:</strong> ${dossier.preferred_tone}</div>
                <div><strong>Landmark:</strong> ${dossier.delivery_landmark}</div>
                <div><strong>Favorite Shoes:</strong> Size ${dossier.size_preferences?.shoes || 'N/A'}</div>
                <div class="mt-2 pt-2 border-top">
                    <strong class="fs-11 text-muted d-block mb-1">Persistent Lifetime Notes:</strong>
                    ${memoriesHtml}
                </div>
                <button class="btn btn-xs btn-outline-primary w-100 mt-2" onclick="addMemoryPrompt('${dossier.phone}')">➕ Add Lifetime Memory Note</button>
            </div>

            <h6 class="font-weight-bold text-uppercase fs-11 text-muted mb-2">📦 Active Orders</h6>
            ${ordersHtml}

            <h6 class="font-weight-bold text-uppercase fs-11 text-muted mb-2 mt-3">🎫 Web Support Tickets</h6>
            ${ticketsHtml}

            <h6 class="font-weight-bold text-uppercase fs-11 text-muted mb-2 mt-3">⚡ 1-Click Quick Actions</h6>
            <div class="d-grid gap-2">
                <button class="btn btn-xs btn-outline-primary" onclick="sendActionTemplate('otp')">Resend 6-Digit Delivery OTP</button>
                <button class="btn btn-xs btn-outline-success" onclick="sendActionTemplate('paystack')">Send Paystack Payment Link</button>
                <button class="btn btn-xs btn-outline-info" onclick="sendActionTemplate('wallet_topup')">💰 Send ₦5,000 Wallet Top-Up Link</button>
                <button class="btn btn-xs btn-outline-secondary" onclick="manualCreditWalletPrompt(${dossier.user_id || 'null'})">➕ Manual Credit Wallet</button>
                <button class="btn btn-xs btn-outline-danger" onclick="banCustomerPrompt('${dossier.phone}', ${dossier.customer_id || 'null'})">🚫 1-Click Ban Customer</button>
            </div>
        `;
    }

    function addMemoryPrompt(phone) {
        let fact = prompt('Enter a persistent fact or preference for this customer (e.g. Always delivers near Shelter Afrique gate, prefers size 43):');
        if (!fact) return;

        fetch("{{ url('admin/customer/add-memory') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ phone: phone, fact: fact })
        }).then(res => res.json()).then(data => {
            alert(data.message);
            if (data.status) {
                loadConversation(activeConversationId);
            }
        });
    }

    function manualCreditWalletPrompt(userId) {
        if (!userId) {
            alert('Customer account must be registered first.');
            return;
        }
        let amount = prompt('Enter amount to credit to this customer\'s wallet (in Naira):', '5000');
        if (!amount || isNaN(amount) || amount <= 0) return;

        fetch("{{ url('admin/customer/credit-wallet') }}/" + userId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ amount: amount })
        }).then(res => res.json()).then(data => {
            alert(data.message);
            if (data.status) {
                loadConversation(activeConversationId);
            }
        });
    }

    function verifyReceipt(orderId) {
        if (!confirm('Are you sure you want to approve this bank transfer and confirm Order #' + orderId + '?')) return;
        fetch("{{ url('admin/customer/verify-receipt') }}/" + orderId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(res => res.json()).then(data => {
            alert(data.message);
            if (data.status) {
                loadConversation(activeConversationId);
            }
        });
    }

    function rejectReceipt(orderId) {
        let reason = prompt('Please enter the reason for rejection (e.g., Transfer not reflecting):');
        if (!reason) return;
        fetch("{{ url('admin/customer/reject-receipt') }}/" + orderId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ reason: reason })
        }).then(res => res.json()).then(data => {
            alert(data.message);
            if (data.status) {
                loadConversation(activeConversationId);
            }
        });
    }

    function banCustomerPrompt(phone, userId) {
        let reason = prompt('Enter reason for banning this customer (e.g., Fake payment receipt fraud):');
        if (!reason) return;
        fetch("{{ url('admin/customer/ban') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ phone: phone, user_id: userId, reason: reason })
        }).then(res => res.json()).then(data => {
            alert(data.message);
            if (data.status) {
                loadConversation(activeConversationId);
            }
        });
    }

    function sendActionTemplate(type) {
        if (!activeConversationId) return;
        let msg = '';
        if (type === 'otp') {
            msg = 'Hello! Here is a reminder of your 6-digit Delivery OTP code for your Victorious MARKET order. Please present this code to your rider upon delivery.';
        } else if (type === 'paystack') {
            msg = 'Hello! You can complete payment for your Victorious MARKET order securely via Paystack using this link: ' + window.location.origin + '/pay';
        } else if (type === 'wallet_topup') {
            msg = 'Hello! You can add funds to your Victorious MARKET wallet instantly with Card, Transfer or USSD using this link: ' + window.location.origin + '/payment-mobile?payment_method=paystack&amount=5000&type=wallet';
        }
        document.getElementById('messageInput').value = msg;
        submitMessage();
    }

    function submitMessage() {
        const input = document.getElementById('messageInput');
        const text = input.value.trim();
        if (!text || !activeConversationId) return;

        fetch("{{ url('admin/whatsapp-crm/send') }}/" + activeConversationId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ message: text })
        }).then(res => res.json()).then(data => {
            if (data.status) {
                input.value = '';
                loadConversation(activeConversationId);
            }
        });
    }

    function toggleBotHandling() {
        if (!activeConversationId) return;
        fetch("{{ url('admin/whatsapp-crm/reassign') }}/" + activeConversationId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ handoff_to_bot: true })
        }).then(() => loadConversation(activeConversationId));
    }

    function reassignToAgent(agentId) {
        if (!activeConversationId) return;
        fetch("{{ url('admin/whatsapp-crm/reassign') }}/" + activeConversationId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ agent_id: agentId || null })
        }).then(() => loadConversation(activeConversationId));
    }

    function markResolved() {
        if (!activeConversationId) return;
        fetch("{{ url('admin/whatsapp-crm/status') }}/" + activeConversationId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: 'resolved' })
        }).then(() => loadConversation(activeConversationId));
    }
</script>
@endsection
