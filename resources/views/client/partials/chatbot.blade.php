@php
    $chatUserId = (int) session('customer_user_id', 0);
    $chatUserName = (string) data_get(session('customer_user'), 'fullname', '');
@endphp
<section
    class="royal-chat"
    data-royal-chat
    data-endpoint="{{ route('chatbot.stream') }}"
    data-reset-endpoint="{{ route('chatbot.reset') }}"
    data-user-id="{{ $chatUserId > 0 ? $chatUserId : '' }}"
    data-user-name="{{ $chatUserName }}"
></section>
