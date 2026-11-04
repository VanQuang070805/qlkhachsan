<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'api_key' => env('GOOGLE_API_KEY', env('GEMINI_API_KEY')),
        'chat_model' => env('GEMINI_CHAT_MODEL', 'gemini-2.5-flash'),
        'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'text-embedding-004'),
    ],

    'langgraph' => [
        'enabled' => env('LANGGRAPH_ENABLED', true),
        'base_url' => env('LANGGRAPH_BASE_URL', 'http://127.0.0.1:2024'),
        'internal_api_key' => env('LANGGRAPH_INTERNAL_API_KEY'),
        'api_key' => env('LANGGRAPH_INTERNAL_API_KEY'),
        'timeout' => (int) env('LANGGRAPH_TIMEOUT', 60),
        'retrieval_assistant_id' => env('LANGGRAPH_RETRIEVAL_ASSISTANT_ID', 'retrieval_graph'),
        'ingestion_assistant_id' => env('LANGGRAPH_INGESTION_ASSISTANT_ID', 'ingestion_graph'),
        'vector_provider' => env('LANGGRAPH_VECTOR_PROVIDER', 'mysql'),
        'query_model' => env('LANGGRAPH_QUERY_MODEL', 'google-genai/'.env('GEMINI_CHAT_MODEL', 'gemini-flash-latest')),
        'tool_api_key' => env('LANGGRAPH_TOOL_API_KEY'),
        'live_tools_enabled' => env('LANGGRAPH_LIVE_TOOLS_ENABLED', true),
        'capability_ttl' => (int) env('LANGGRAPH_CAPABILITY_TTL', 90),
        'require_capability_for_public_tools' => env('LANGGRAPH_REQUIRE_CAPABILITY_PUBLIC', false),
    ],

    'portal' => [
        'context' => env('PORTAL_CONTEXT', 'all'),
        'customer_url' => env('CUSTOMER_PORTAL_URL', 'http://localhost:18080'),
        'staff_url' => env('STAFF_PORTAL_URL', 'http://localhost:18081'),
        'admin_url' => env('ADMIN_PORTAL_URL', 'http://localhost:18082'),
    ],

    'devices' => [
        'api_key' => env('DEVICE_API_KEY', ''),
    ],

    'kafka' => [
        'brokers' => env('KAFKA_BROKERS', 'kafka:9092'),
        'client_id' => env('KAFKA_CLIENT_ID', 'royal-hotel-nhom10-v2'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
    ],

];
