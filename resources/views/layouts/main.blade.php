<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Khách Sạn ROYAL HOTEL')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'DM Sans', sans-serif; background-color: #f8f9fa; display: flex; flex-direction: column; min-height: 100vh; }
        h1, h2, h3, h4, h5, .page-title { font-family: 'Cormorant Garamond', serif; }
        .navbar-brand { font-weight: 700; letter-spacing: .5px; }
        .room-card { transition: transform .2s, box-shadow .2s; }
        .room-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12) !important; }
        footer { border-top: 1px solid #dee2e6; margin-top: auto !important; }
        main { flex: 1 0 auto; }
    </style>
    @stack('styles')
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">
            <i class="bi bi-building me-1"></i>ROYAL HOTEL
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('home') }}">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('about') }}">Giới thiệu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('amenities') }}">Tiện nghi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('contact') }}">Liên hệ</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                @if(session('user_id'))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            {{ session('user.fullname', 'Tài khoản') }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('booking.mine') }}">
                                    <i class="bi bi-calendar-check me-1"></i>Đặt phòng của tôi
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Đăng nhập</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm ms-2 my-auto" href="{{ route('register') }}">Đăng ký</a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>

<!-- FLASH MESSAGE -->
@if(session('success'))
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif
@if(session('error'))
    <div class="container mt-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif

<!-- NỘI DUNG CHÍNH -->
<main class="py-4">
    <div class="container">
        @yield('content')
    </div>
</main>

<!-- FOOTER -->
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row g-3">
            <div class="col-md-4">
                <h6 class="fw-bold"><i class="bi bi-building me-1"></i>ROYAL HOTEL</h6>
                <p class="text-light small mb-0">Dịch vụ lưu trú cao cấp, phục vụ tận tình 24/7.</p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Liên hệ</h6>
                <p class="text-light small mb-1"><i class="bi bi-geo-alt me-1"></i>12 Chùa Bộc, Hà Nội</p>
                <p class="text-light small mb-1"><i class="bi bi-telephone me-1"></i>0123 456 789</p>
                <p class="text-light small mb-0"><i class="bi bi-envelope me-1"></i>royalhotel@gmail.com.vn</p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Liên kết nhanh</h6>
                <ul class="list-unstyled small">
                    <li><a class="text-light text-decoration-none" href="{{ route('rooms.index') }}">Danh sách phòng</a></li>
                    <li><a class="text-light text-decoration-none" href="{{ route('rooms.search') }}">Tìm phòng trống</a></li>
                    <li><a class="text-light text-decoration-none" href="{{ route('contact') }}">Liên hệ</a></li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary mt-3 mb-2">
        <p class="text-center text-light small mb-0">&copy; {{ date('Y') }} ROYAL HOTEL. All rights reserved.</p>
    </div>
</footer>

<!-- CHATBOT WIDGET -->
<style>
    /* Chatbot Styles */
    #chatbot-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #d4af37 0%, #aa8c2c 100%);
        color: white;
        text-align: center;
        line-height: 60px;
        font-size: 28px;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
        z-index: 1000;
        transition: transform 0.3s;
    }
    #chatbot-btn:hover {
        transform: scale(1.1);
    }
    #chatbot-window {
        position: fixed;
        bottom: 100px;
        right: 30px;
        width: 350px;
        height: 450px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        display: none;
        flex-direction: column;
        z-index: 1000;
        overflow: hidden;
        border: 1px solid #e0e0e0;
    }
    #chatbot-header {
        background: linear-gradient(135deg, #d4af37 0%, #aa8c2c 100%);
        color: white;
        padding: 15px;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    #chatbot-header .close-btn {
        cursor: pointer;
        font-size: 20px;
    }
    #chatbot-messages {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        background: #f9f9f9;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .msg-bot {
        background: #e9ecef;
        color: #333;
        padding: 10px 15px;
        border-radius: 15px 15px 15px 0;
        align-self: flex-start;
        max-width: 80%;
        font-size: 0.9rem;
    }
    .msg-user {
        background: #d4af37;
        color: white;
        padding: 10px 15px;
        border-radius: 15px 15px 0 15px;
        align-self: flex-end;
        max-width: 80%;
        font-size: 0.9rem;
    }
    #chatbot-input-area {
        display: flex;
        padding: 10px;
        border-top: 1px solid #eee;
        background: white;
    }
    #chatbot-input {
        flex: 1;
        border: 1px solid #ddd;
        border-radius: 20px;
        padding: 8px 15px;
        outline: none;
    }
    #chatbot-send {
        background: #d4af37;
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        margin-left: 10px;
        cursor: pointer;
        transition: background 0.2s;
    }
    #chatbot-send:hover {
        background: #b5952f;
    }
    .typing-indicator {
        display: none;
        align-self: flex-start;
        color: #888;
        font-size: 0.8rem;
        font-style: italic;
        margin-left: 15px;
        margin-bottom: 5px;
    }
</style>

<div id="chatbot-btn" onclick="toggleChat()">
    <i class="bi bi-chat-dots-fill"></i>
</div>

<div id="chatbot-window">
    <div id="chatbot-header">
        <span><i class="bi bi-robot me-2"></i> Tư vấn viên AI</span>
        <span class="close-btn" onclick="toggleChat()">&times;</span>
    </div>
    <div id="chatbot-messages">
        <div class="msg-bot">Xin chào! Tôi là trợ lý ảo của Royal Hotel. Tôi có thể giúp gì cho bạn?</div>
    </div>
    <div class="typing-indicator" id="chatbot-typing">AI đang trả lời...</div>
    <div id="chatbot-input-area">
        <input type="text" id="chatbot-input" placeholder="Nhập câu hỏi..." onkeypress="handleEnter(event)">
        <button id="chatbot-send" onclick="sendMessage()"><i class="bi bi-send-fill"></i></button>
    </div>
</div>

<script>
    function toggleChat() {
        const chat = document.getElementById('chatbot-window');
        chat.style.display = (chat.style.display === 'none' || chat.style.display === '') ? 'flex' : 'none';
        if(chat.style.display === 'flex') {
            document.getElementById('chatbot-input').focus();
        }
    }

    function handleEnter(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    }

    function appendMessage(text, sender) {
        const msgDiv = document.createElement('div');
        msgDiv.className = sender === 'user' ? 'msg-user' : 'msg-bot';
        msgDiv.innerHTML = text;
        
        const chatContainer = document.getElementById('chatbot-messages');
        chatContainer.appendChild(msgDiv);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    function sendMessage() {
        const input = document.getElementById('chatbot-input');
        const message = input.value.trim();
        if (!message) return;

        appendMessage(message, 'user');
        input.value = '';
        
        const typingIndicator = document.getElementById('chatbot-typing');
        typingIndicator.style.display = 'block';
        
        const chatContainer = document.getElementById('chatbot-messages');
        chatContainer.scrollTop = chatContainer.scrollHeight;

        fetch('{{ route('chatbot.api') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => response.json())
        .then(data => {
            typingIndicator.style.display = 'none';
            if(data.reply) {
                appendMessage(data.reply, 'bot');
            } else {
                appendMessage("Lỗi không xác định từ máy chủ.", 'bot');
            }
        })
        .catch(err => {
            typingIndicator.style.display = 'none';
            appendMessage("Lỗi kết nối mạng.", 'bot');
            console.error(err);
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>