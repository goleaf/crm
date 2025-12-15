<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Type-safe environment variable accessor with IDE autocompletion.
 *
 * This class provides a centralized, type-safe API for accessing environment variables with:
 * - Type safety and validation
 * - IDE autocompletion
 * - Explicit defaults
 * - Nullable support
 *
 * Note: worksome/envy is used as a dev tool for managing .env files via CLI,
 * not as a runtime accessor. This class wraps Laravel's env() function with type safety.
 *
 * @see https://laravel-news.com/laravel-envy
 * @see docs/laravel-envy-integration.md
 */
final class Env
{
    private function __construct()
    {
        // Private constructor to enforce static usage
    }

    public static function make(): self
    {
        return new self;
    }
    // =========================================================================
    // Application Configuration
    // =========================================================================

    public function appName(): string
    {
        return (string) env('APP_NAME', 'Relaticle');
    }

    public function appEnv(): string
    {
        return (string) env('APP_ENV', 'production');
    }

    public function appDebug(): bool
    {
        return (bool) env('APP_DEBUG', false);
    }

    public function appUrl(): string
    {
        return (string) env('APP_URL', 'http://localhost');
    }

    public function appTimezone(): string
    {
        return (string) env('APP_TIMEZONE', 'UTC');
    }

    public function appLocale(): string
    {
        return (string) env('APP_LOCALE', 'en');
    }

    public function appFallbackLocale(): string
    {
        return (string) env('APP_FALLBACK_LOCALE', 'en');
    }

    // =========================================================================
    // Security Configuration
    // =========================================================================

    public function securityHeadersEnabled(): bool
    {
        return (bool) env('SECURITY_HEADERS_ENABLED', true);
    }

    public function securityHeadersOnlyHttps(): bool
    {
        return (bool) env('SECURITY_HEADERS_ONLY_HTTPS', true);
    }

    public function bcryptRounds(): int
    {
        return (int) env('BCRYPT_ROUNDS', 12);
    }

    public function zxcvbnMinScore(): int
    {
        return (int) env('ZXCVBN_MIN_SCORE', 3);
    }

    // =========================================================================
    // Database Configuration
    // =========================================================================

    public function dbConnection(): string
    {
        return (string) env('DB_CONNECTION', 'pgsql');
    }

    public function dbHost(): string
    {
        return (string) env('DB_HOST', '127.0.0.1');
    }

    public function dbPort(): int
    {
        return (int) env('DB_PORT', 5432);
    }

    public function dbDatabase(): string
    {
        return (string) env('DB_DATABASE', 'relaticle');
    }

    public function dbUsername(): string
    {
        return (string) env('DB_USERNAME', 'postgres');
    }

    public function dbPassword(): string
    {
        return (string) env('DB_PASSWORD', '');
    }

    // =========================================================================
    // Cache & Session Configuration
    // =========================================================================

    public function cacheStore(): string
    {
        return (string) env('CACHE_STORE', 'memcached');
    }

    public function cachePrefix(): string
    {
        return (string) env('CACHE_PREFIX', 'relaticle_cache');
    }

    public function sessionDriver(): string
    {
        return (string) env('SESSION_DRIVER', 'memcached');
    }

    public function sessionLifetime(): int
    {
        return (int) env('SESSION_LIFETIME', 120);
    }

    // =========================================================================
    // Redis Configuration
    // =========================================================================

    public function redisHost(): string
    {
        return (string) env('REDIS_HOST', '127.0.0.1');
    }

    public function redisPort(): int
    {
        return (int) env('REDIS_PORT', 6379);
    }

    public function redisPassword(): ?string
    {
        return env('REDIS_PASSWORD');
    }

    // =========================================================================
    // Mail Configuration
    // =========================================================================

    public function mailMailer(): string
    {
        return (string) env('MAIL_MAILER', 'log');
    }

    public function mailHost(): string
    {
        return (string) env('MAIL_HOST', '127.0.0.1');
    }

    public function mailPort(): int
    {
        return (int) env('MAIL_PORT', 2525);
    }

    public function mailFromAddress(): string
    {
        return (string) env('MAIL_FROM_ADDRESS', 'hello@example.com');
    }

    public function mailFromName(): string
    {
        return (string) env('MAIL_FROM_NAME', $this->appName());
    }

    // =========================================================================
    // Queue Configuration
    // =========================================================================

    public function queueConnection(): string
    {
        return (string) env('QUEUE_CONNECTION', 'database');
    }

    // =========================================================================
    // OAuth Configuration
    // =========================================================================

    public function googleClientId(): ?string
    {
        return env('GOOGLE_CLIENT_ID');
    }

    public function googleClientSecret(): ?string
    {
        return env('GOOGLE_CLIENT_SECRET');
    }

    public function githubClientId(): ?string
    {
        return env('GITHUB_CLIENT_ID');
    }

    public function githubClientSecret(): ?string
    {
        return env('GITHUB_CLIENT_SECRET');
    }

    public function githubToken(): ?string
    {
        return env('GITHUB_TOKEN');
    }

    // =========================================================================
    // Monitoring & Analytics
    // =========================================================================

    public function sentryDsn(): ?string
    {
        return env('SENTRY_LARAVEL_DSN');
    }

    public function sentryTracesSampleRate(): float
    {
        return (float) env('SENTRY_TRACES_SAMPLE_RATE', 1.0);
    }

    public function fathomSiteId(): ?string
    {
        return env('FATHOM_ANALYTICS_SITE_ID');
    }

    // =========================================================================
    // OCR Configuration
    // =========================================================================

    public function ocrDriver(): string
    {
        return (string) env('OCR_DRIVER', 'tesseract');
    }

    public function ocrTesseractPath(): string
    {
        return (string) env('OCR_TESSERACT_PATH', '/usr/local/bin/tesseract');
    }

    public function ocrAiEnabled(): bool
    {
        return (bool) env('OCR_AI_ENABLED', true);
    }

    public function ocrQueueEnabled(): bool
    {
        return (bool) env('OCR_QUEUE_ENABLED', true);
    }

    public function ocrMinConfidence(): float
    {
        return (float) env('OCR_MIN_CONFIDENCE', 0.7);
    }

    public function ocrMaxFileSize(): int
    {
        return (int) env('OCR_MAX_FILE_SIZE', 10240);
    }

    // =========================================================================
    // PCOV Code Coverage Configuration
    // =========================================================================

    public function pcovEnabled(): bool
    {
        return (bool) env('PCOV_ENABLED', true);
    }

    public function coverageMinPercentage(): int
    {
        return (int) env('COVERAGE_MIN_PERCENTAGE', 80);
    }

    public function coverageMinTypeCoverage(): float
    {
        return (float) env('COVERAGE_MIN_TYPE_COVERAGE', 99.9);
    }

    // =========================================================================
    // Warden Security Audit Configuration
    // =========================================================================

    public function wardenScheduleEnabled(): bool
    {
        return (bool) env('WARDEN_SCHEDULE_ENABLED', true);
    }

    public function wardenScheduleFrequency(): string
    {
        return (string) env('WARDEN_SCHEDULE_FREQUENCY', 'daily');
    }

    public function wardenCacheEnabled(): bool
    {
        return (bool) env('WARDEN_CACHE_ENABLED', true);
    }

    public function wardenCacheDuration(): int
    {
        return (int) env('WARDEN_CACHE_DURATION', 3600);
    }

    public function wardenHistoryEnabled(): bool
    {
        return (bool) env('WARDEN_HISTORY_ENABLED', true);
    }

    // =========================================================================
    // Unsplash Configuration
    // =========================================================================

    public function unsplashAccessKey(): ?string
    {
        return env('UNSPLASH_ACCESS_KEY');
    }

    public function unsplashSecretKey(): ?string
    {
        return env('UNSPLASH_SECRET_KEY');
    }

    public function unsplashCacheEnabled(): bool
    {
        return (bool) env('UNSPLASH_CACHE_ENABLED', true);
    }

    public function unsplashCacheTtl(): int
    {
        return (int) env('UNSPLASH_CACHE_TTL', 3600);
    }

    public function unsplashAutoDownload(): bool
    {
        return (bool) env('UNSPLASH_AUTO_DOWNLOAD', true);
    }

    // =========================================================================
    // Geo Configuration
    // =========================================================================

    public function geoAutoTranslate(): bool
    {
        return (bool) env('GEO_AUTO_TRANSLATE', true);
    }

    public function geoPhoneDefaultCountry(): string
    {
        return (string) env('GEO_PHONE_DEFAULT_COUNTRY', 'us');
    }

    public function geoCacheTtlMinutes(): int
    {
        return (int) env('GEO_CACHE_TTL_MINUTES', 10080);
    }

    // =========================================================================
    // System Admin Configuration
    // =========================================================================

    public function sysadminDomain(): ?string
    {
        return env('SYSADMIN_DOMAIN');
    }

    public function sysadminPath(): string
    {
        return (string) env('SYSADMIN_PATH', 'sysadmin');
    }

    // =========================================================================
    // Community Links
    // =========================================================================

    public function discordInviteUrl(): string
    {
        return (string) env('DISCORD_INVITE_URL', 'https://discord.gg/b9WxzUce4Q');
    }

    // =========================================================================
    // Email Verification
    // =========================================================================

    public function fortifyEmailVerification(): bool
    {
        return (bool) env('FORTIFY_EMAIL_VERIFICATION', false);
    }

    // =========================================================================
    // Session Configuration
    // =========================================================================

    public function sessionExpireOnClose(): bool
    {
        return (bool) env('SESSION_EXPIRE_ON_CLOSE', false);
    }

    public function sessionEncrypt(): bool
    {
        return (bool) env('SESSION_ENCRYPT', false);
    }

    public function sessionConnection(): ?string
    {
        return env('SESSION_CONNECTION');
    }

    public function sessionTable(): string
    {
        return (string) env('SESSION_TABLE', 'sessions');
    }

    public function sessionStore(): ?string
    {
        return env('SESSION_STORE');
    }

    public function sessionCookie(): string
    {
        return (string) env('SESSION_COOKIE', $this->appName() . '_session');
    }

    public function sessionPath(): string
    {
        return (string) env('SESSION_PATH', '/');
    }

    public function sessionDomain(): ?string
    {
        return env('SESSION_DOMAIN');
    }

    public function sessionSecureCookie(): ?bool
    {
        return env('SESSION_SECURE_COOKIE') !== null ? (bool) env('SESSION_SECURE_COOKIE') : null;
    }

    public function sessionHttpOnly(): bool
    {
        return (bool) env('SESSION_HTTP_ONLY', true);
    }

    public function sessionSameSite(): string
    {
        return (string) env('SESSION_SAME_SITE', 'lax');
    }

    public function sessionPartitionedCookie(): bool
    {
        return (bool) env('SESSION_PARTITIONED_COOKIE', false);
    }

    // =========================================================================
    // Filesystem Configuration
    // =========================================================================

    public function filesystemDisk(): string
    {
        return (string) env('FILESYSTEM_DISK', 'local');
    }

    public function awsAccessKeyId(): ?string
    {
        return env('AWS_ACCESS_KEY_ID');
    }

    public function awsSecretAccessKey(): ?string
    {
        return env('AWS_SECRET_ACCESS_KEY');
    }

    public function awsDefaultRegion(): string
    {
        return (string) env('AWS_DEFAULT_REGION', 'us-east-1');
    }

    public function awsBucket(): ?string
    {
        return env('AWS_BUCKET');
    }

    public function awsUrl(): ?string
    {
        return env('AWS_URL');
    }

    public function awsEndpoint(): ?string
    {
        return env('AWS_ENDPOINT');
    }

    public function awsUsePathStyleEndpoint(): bool
    {
        return (bool) env('AWS_USE_PATH_STYLE_ENDPOINT', false);
    }

    // =========================================================================
    // Unsplash Extended Configuration
    // =========================================================================

    public function unsplashUtmSource(): string
    {
        return (string) env('UNSPLASH_UTM_SOURCE', $this->appName());
    }

    public function unsplashHttpTimeout(): int
    {
        return (int) env('UNSPLASH_HTTP_TIMEOUT', 30);
    }

    public function unsplashHttpRetryTimes(): int
    {
        return (int) env('UNSPLASH_HTTP_RETRY_TIMES', 3);
    }

    public function unsplashHttpRetrySleep(): int
    {
        return (int) env('UNSPLASH_HTTP_RETRY_SLEEP', 1000);
    }

    public function unsplashApiBaseUrl(): string
    {
        return (string) env('UNSPLASH_API_BASE_URL', 'https://api.unsplash.com');
    }

    public function unsplashDefaultPerPage(): int
    {
        return (int) env('UNSPLASH_DEFAULT_PER_PAGE', 30);
    }

    public function unsplashDefaultOrientation(): ?string
    {
        return env('UNSPLASH_DEFAULT_ORIENTATION');
    }

    public function unsplashDefaultQuality(): int
    {
        return (int) env('UNSPLASH_DEFAULT_QUALITY', 80);
    }

    public function unsplashStorageDisk(): string
    {
        return (string) env('UNSPLASH_STORAGE_DISK', 'public');
    }

    public function unsplashStoragePath(): string
    {
        return (string) env('UNSPLASH_STORAGE_PATH', 'unsplash');
    }

    public function unsplashAssetsTable(): string
    {
        return (string) env('UNSPLASH_ASSETS_TABLE', 'unsplash_assets');
    }

    public function unsplashPivotTable(): string
    {
        return (string) env('UNSPLASH_PIVOT_TABLE', 'unsplashables');
    }

    public function unsplashCachePrefix(): string
    {
        return (string) env('UNSPLASH_CACHE_PREFIX', 'unsplash');
    }

    public function unsplashFilamentEnabled(): bool
    {
        return (bool) env('UNSPLASH_FILAMENT_ENABLED', true);
    }

    public function unsplashFilamentModalWidth(): string
    {
        return (string) env('UNSPLASH_FILAMENT_MODAL_WIDTH', 'xl');
    }

    public function unsplashFilamentColumnsGrid(): int
    {
        return (int) env('UNSPLASH_FILAMENT_COLUMNS_GRID', 3);
    }

    public function unsplashFilamentShowPhotographer(): bool
    {
        return (bool) env('UNSPLASH_FILAMENT_SHOW_PHOTOGRAPHER', true);
    }

    // =========================================================================
    // Favicon Fetcher Configuration
    // =========================================================================

    public function faviconFetcherVerifyTls(): bool
    {
        return (bool) env('FAVICON_FETCHER_VERIFY_TLS', true);
    }

    public function faviconFetcherUserAgent(): ?string
    {
        return env('FAVICON_FETCHER_USER_AGENT');
    }

    // =========================================================================
    // Prism AI Configuration
    // =========================================================================

    public function prismServerEnabled(): bool
    {
        return (bool) env('PRISM_SERVER_ENABLED', false);
    }

    public function anthropicApiKey(): string
    {
        return (string) env('ANTHROPIC_API_KEY', '');
    }

    public function anthropicApiVersion(): string
    {
        return (string) env('ANTHROPIC_API_VERSION', '2023-06-01');
    }

    public function anthropicUrl(): string
    {
        return (string) env('ANTHROPIC_URL', 'https://api.anthropic.com/v1');
    }

    public function anthropicDefaultThinkingBudget(): int
    {
        return (int) env('ANTHROPIC_DEFAULT_THINKING_BUDGET', 1024);
    }

    public function anthropicBeta(): ?string
    {
        return env('ANTHROPIC_BETA');
    }

    public function ollamaUrl(): string
    {
        return (string) env('OLLAMA_URL', 'http://localhost:11434');
    }

    public function mistralApiKey(): string
    {
        return (string) env('MISTRAL_API_KEY', '');
    }

    public function mistralUrl(): string
    {
        return (string) env('MISTRAL_URL', 'https://api.mistral.ai/v1');
    }

    public function groqApiKey(): string
    {
        return (string) env('GROQ_API_KEY', '');
    }

    public function groqUrl(): string
    {
        return (string) env('GROQ_URL', 'https://api.groq.com/v1');
    }

    public function xaiApiKey(): string
    {
        return (string) env('XAI_API_KEY', '');
    }

    public function xaiUrl(): string
    {
        return (string) env('XAI_URL', 'https://api.x.ai/v1');
    }

    public function geminiApiKey(): string
    {
        return (string) env('GEMINI_API_KEY', '');
    }

    public function geminiUrl(): string
    {
        return (string) env('GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta/models');
    }

    public function deepseekApiKey(): string
    {
        return (string) env('DEEPSEEK_API_KEY', '');
    }

    public function deepseekUrl(): string
    {
        return (string) env('DEEPSEEK_URL', 'https://api.deepseek.com/v1');
    }

    public function elevenlabsApiKey(): string
    {
        return (string) env('ELEVENLABS_API_KEY', '');
    }

    public function elevenlabsUrl(): string
    {
        return (string) env('ELEVENLABS_URL', 'https://api.elevenlabs.io/v1/');
    }

    // =========================================================================
    // Application Extended Configuration
    // =========================================================================

    public function appFakerLocale(): string
    {
        return (string) env('APP_FAKER_LOCALE', 'en_US');
    }

    public function appKey(): ?string
    {
        return env('APP_KEY');
    }

    public function appPreviousKeys(): string
    {
        return (string) env('APP_PREVIOUS_KEYS', '');
    }

    public function appMaintenanceDriver(): string
    {
        return (string) env('APP_MAINTENANCE_DRIVER', 'file');
    }

    public function appMaintenanceStore(): string
    {
        return (string) env('APP_MAINTENANCE_STORE', 'database');
    }

    // =========================================================================
    // Database Extended Configuration
    // =========================================================================

    public function dbUrl(): ?string
    {
        return env('DB_URL');
    }

    public function dbForeignKeys(): bool
    {
        return (bool) env('DB_FOREIGN_KEYS', true);
    }

    public function dbSocket(): string
    {
        return (string) env('DB_SOCKET', '');
    }

    public function dbCharset(): string
    {
        return (string) env('DB_CHARSET', 'utf8mb4');
    }

    public function dbCollation(): string
    {
        return (string) env('DB_COLLATION', 'utf8mb4_unicode_ci');
    }

    public function mysqlAttrSslCa(): ?string
    {
        return env('MYSQL_ATTR_SSL_CA');
    }

    // =========================================================================
    // Redis Extended Configuration
    // =========================================================================

    public function redisClient(): string
    {
        return (string) env('REDIS_CLIENT', 'phpredis');
    }

    public function redisCluster(): string
    {
        return (string) env('REDIS_CLUSTER', 'redis');
    }

    public function redisPrefix(): string
    {
        return (string) env('REDIS_PREFIX', $this->appName() . '_database_');
    }

    public function redisUrl(): ?string
    {
        return env('REDIS_URL');
    }

    public function redisUsername(): ?string
    {
        return env('REDIS_USERNAME');
    }

    public function redisDb(): string
    {
        return (string) env('REDIS_DB', '0');
    }

    public function redisCacheDb(): string
    {
        return (string) env('REDIS_CACHE_DB', '1');
    }

    public function redisCacheConnection(): string
    {
        return (string) env('REDIS_CACHE_CONNECTION', 'cache');
    }

    public function redisCacheLockConnection(): string
    {
        return (string) env('REDIS_CACHE_LOCK_CONNECTION', 'default');
    }

    // =========================================================================
    // Cache Extended Configuration
    // =========================================================================

    public function dbCacheConnection(): ?string
    {
        return env('DB_CACHE_CONNECTION');
    }

    public function dbCacheTable(): string
    {
        return (string) env('DB_CACHE_TABLE', 'cache');
    }

    public function dbCacheLockConnection(): ?string
    {
        return env('DB_CACHE_LOCK_CONNECTION');
    }

    public function dbCacheLockTable(): ?string
    {
        return env('DB_CACHE_LOCK_TABLE');
    }

    public function memcachedPersistentId(): ?string
    {
        return env('MEMCACHED_PERSISTENT_ID');
    }

    public function memcachedUsername(): ?string
    {
        return env('MEMCACHED_USERNAME');
    }

    public function memcachedPassword(): ?string
    {
        return env('MEMCACHED_PASSWORD');
    }

    public function memcachedHost(): string
    {
        return (string) env('MEMCACHED_HOST', '127.0.0.1');
    }

    public function memcachedPort(): int
    {
        return (int) env('MEMCACHED_PORT', 11211);
    }

    public function dynamodbCacheTable(): string
    {
        return (string) env('DYNAMODB_CACHE_TABLE', 'cache');
    }

    public function dynamodbEndpoint(): ?string
    {
        return env('DYNAMODB_ENDPOINT');
    }

    // =========================================================================
    // Mail Extended Configuration
    // =========================================================================

    public function mailUrl(): ?string
    {
        return env('MAIL_URL');
    }

    public function mailEncryption(): string
    {
        return (string) env('MAIL_ENCRYPTION', 'tls');
    }

    public function mailUsername(): ?string
    {
        return env('MAIL_USERNAME');
    }

    public function mailPassword(): ?string
    {
        return env('MAIL_PASSWORD');
    }

    public function mailEhloDomain(): string
    {
        return (string) env('MAIL_EHLO_DOMAIN', parse_url($this->appUrl(), PHP_URL_HOST) ?? 'localhost');
    }

    public function mailSendmailPath(): string
    {
        return (string) env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i');
    }

    public function mailLogChannel(): ?string
    {
        return env('MAIL_LOG_CHANNEL');
    }

    public function mailMarkdownTheme(): string
    {
        return (string) env('MAIL_MARKDOWN_THEME', 'default');
    }

    public function mailcoachDomain(): ?string
    {
        return env('MAILCOACH_DOMAIN');
    }

    public function mailcoachApiToken(): ?string
    {
        return env('MAILCOACH_API_TOKEN');
    }

    // =========================================================================
    // Queue Extended Configuration
    // =========================================================================

    public function dbQueueConnection(): ?string
    {
        return env('DB_QUEUE_CONNECTION');
    }

    public function dbQueueTable(): string
    {
        return (string) env('DB_QUEUE_TABLE', 'jobs');
    }

    public function dbQueue(): string
    {
        return (string) env('DB_QUEUE', 'default');
    }

    public function dbQueueRetryAfter(): int
    {
        return (int) env('DB_QUEUE_RETRY_AFTER', 90);
    }

    public function beanstalkdQueueHost(): string
    {
        return (string) env('BEANSTALKD_QUEUE_HOST', 'localhost');
    }

    public function beanstalkdQueue(): string
    {
        return (string) env('BEANSTALKD_QUEUE', 'default');
    }

    public function beanstalkdQueueRetryAfter(): int
    {
        return (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90);
    }

    public function sqsPrefix(): string
    {
        return (string) env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id');
    }

    public function sqsQueue(): string
    {
        return (string) env('SQS_QUEUE', 'default');
    }

    public function sqsSuffix(): ?string
    {
        return env('SQS_SUFFIX');
    }

    public function redisQueueConnection(): string
    {
        return (string) env('REDIS_QUEUE_CONNECTION', 'default');
    }

    public function redisQueue(): string
    {
        return (string) env('REDIS_QUEUE', 'default');
    }

    public function redisQueueRetryAfter(): int
    {
        return (int) env('REDIS_QUEUE_RETRY_AFTER', 90);
    }

    public function queueFailedDriver(): string
    {
        return (string) env('QUEUE_FAILED_DRIVER', 'database-uuids');
    }
}
