param()

$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$laravelEnv = Join-Path $projectRoot '.env'
$chatbotEnv = Join-Path $projectRoot 'services\royal-ai-chatbot\backend\.env'

function Set-EnvValues([string] $path, [System.Collections.IDictionary] $values) {
    if (-not (Test-Path -LiteralPath $path)) { throw "Missing environment file: $path" }
    $content = [System.IO.File]::ReadAllText($path)
    foreach ($key in $values.Keys) {
        $entry = "$key=$($values[$key])"
        $pattern = "(?m)^$([regex]::Escape($key))=.*$"
        if ([regex]::IsMatch($content, $pattern)) {
            $content = [regex]::Replace($content, $pattern, [System.Text.RegularExpressions.MatchEvaluator] { param($match) $entry })
        } else {
            $content = $content.TrimEnd("`r", "`n") + "`r`n" + $entry + "`r`n"
        }
    }
    [System.IO.File]::WriteAllText($path, $content, [System.Text.UTF8Encoding]::new($false))
}

$laravel = [ordered]@{
    REDIS_CLIENT = 'predis'
    CACHE_STORE = 'redis'
    QUEUE_CONNECTION = 'redis'
    REDIS_HOST = '127.0.0.1'
    REDIS_PORT = '16379'
    REDIS_DB = '0'
    REDIS_CACHE_DB = '1'
    REDIS_QUEUE_CONNECTION = 'queue'
    REDIS_QUEUE_HOST = '127.0.0.1'
    REDIS_QUEUE_PORT = '16380'
    REDIS_QUEUE_DB = '0'
    REDIS_QUEUE = 'laravel-default'
}
$chatbot = [ordered]@{
    REDIS_QUEUE_HOST = '127.0.0.1'
    REDIS_QUEUE_PORT = '16380'
    REDIS_QUEUE_DB = '0'
    USE_BULLMQ_WORKER = 'true'
    CHATBOT_WORKER_CONCURRENCY = '2'
    KAFKA_BROKERS = '127.0.0.1:19092'
    KAFKA_CLIENT_ID = 'rosaliza-local'
}

Set-EnvValues $laravelEnv $laravel
Set-EnvValues $chatbotEnv $chatbot
Write-Host 'Configured Laravel Redis cache/queue and chatbot BullMQ/Kafka endpoints for local infrastructure.'
