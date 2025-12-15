<?php

declare(strict_types=1);

use App\Support\Env;

describe('Env - Application Configuration', function (): void {
    it('returns app name with default', function (): void {
        putenv('APP_NAME=');
        expect(Env::make()->appName())->toBe('Relaticle');
    });

    it('returns custom app name', function (): void {
        putenv('APP_NAME=Test App');
        expect(Env::make()->appName())->toBe('Test App');
    });

    it('returns app debug as boolean', function (): void {
        putenv('APP_DEBUG=true');
        expect(Env::make()->appDebug())->toBeTrue();

        putenv('APP_DEBUG=false');
        expect(Env::make()->appDebug())->toBeFalse();
    });

    it('returns app url with default', function (): void {
        putenv('APP_URL=');
        expect(Env::make()->appUrl())->toBe('http://localhost');
    });

    it('returns app timezone with default', function (): void {
        putenv('APP_TIMEZONE=');
        expect(Env::make()->appTimezone())->toBe('UTC');
    });

    it('returns app locale with default', function (): void {
        putenv('APP_LOCALE=');
        expect(Env::make()->appLocale())->toBe('en');
    });
});

describe('Env - Database Configuration', function (): void {
    it('returns database connection with default', function (): void {
        putenv('DB_CONNECTION=');
        expect(Env::make()->dbConnection())->toBe('pgsql');
    });

    it('returns database host with default', function (): void {
        putenv('DB_HOST=');
        expect(Env::make()->dbHost())->toBe('127.0.0.1');
    });

    it('returns database port as integer', function (): void {
        putenv('DB_PORT=5432');
        expect(Env::make()->dbPort())->toBe(5432);
    });

    it('returns database name', function (): void {
        putenv('DB_DATABASE=test_db');
        expect(Env::make()->dbDatabase())->toBe('test_db');
    });
});

describe('Env - Cache Configuration', function (): void {
    it('returns cache store with default', function (): void {
        putenv('CACHE_STORE=');
        expect(Env::make()->cacheStore())->toBe('redis');
    });

    it('returns cache prefix with default', function (): void {
        putenv('CACHE_PREFIX=');
        expect(Env::make()->cachePrefix())->toBe('relaticle_cache');
    });
});

describe('Env - Session Configuration', function (): void {
    it('returns session driver with default', function (): void {
        putenv('SESSION_DRIVER=');
        expect(Env::make()->sessionDriver())->toBe('database');
    });

    it('returns session lifetime as integer', function (): void {
        putenv('SESSION_LIFETIME=120');
        expect(Env::make()->sessionLifetime())->toBe(120);
    });

    it('returns session encrypt as boolean', function (): void {
        putenv('SESSION_ENCRYPT=true');
        expect(Env::make()->sessionEncrypt())->toBeTrue();

        putenv('SESSION_ENCRYPT=false');
        expect(Env::make()->sessionEncrypt())->toBeFalse();
    });

    it('returns session table with default', function (): void {
        putenv('SESSION_TABLE=');
        expect(Env::make()->sessionTable())->toBe('sessions');
    });

    it('returns session cookie with default', function (): void {
        putenv('SESSION_COOKIE=');
        putenv('APP_NAME=Relaticle');
        expect(Env::make()->sessionCookie())->toBe('Relaticle_session');
    });

    it('returns session path with default', function (): void {
        putenv('SESSION_PATH=');
        expect(Env::make()->sessionPath())->toBe('/');
    });

    it('returns session http only with default', function (): void {
        putenv('SESSION_HTTP_ONLY=');
        expect(Env::make()->sessionHttpOnly())->toBeTrue();
    });

    it('returns session same site with default', function (): void {
        putenv('SESSION_SAME_SITE=');
        expect(Env::make()->sessionSameSite())->toBe('lax');
    });
});

describe('Env - Redis Configuration', function (): void {
    it('returns redis host with default', function (): void {
        putenv('REDIS_HOST=');
        expect(Env::make()->redisHost())->toBe('127.0.0.1');
    });

    it('returns redis port as integer', function (): void {
        putenv('REDIS_PORT=6379');
        expect(Env::make()->redisPort())->toBe(6379);
    });

    it('returns redis password as nullable', function (): void {
        putenv('REDIS_PASSWORD=');
        expect(Env::make()->redisPassword())->toBeNull();

        putenv('REDIS_PASSWORD=secret');
        expect(Env::make()->redisPassword())->toBe('secret');
    });
});

describe('Env - Mail Configuration', function (): void {
    it('returns mail mailer with default', function (): void {
        putenv('MAIL_MAILER=');
        expect(Env::make()->mailMailer())->toBe('log');
    });

    it('returns mail host with default', function (): void {
        putenv('MAIL_HOST=');
        expect(Env::make()->mailHost())->toBe('127.0.0.1');
    });

    it('returns mail port as integer', function (): void {
        putenv('MAIL_PORT=2525');
        expect(Env::make()->mailPort())->toBe(2525);
    });

    it('returns mail from address with default', function (): void {
        putenv('MAIL_FROM_ADDRESS=');
        expect(Env::make()->mailFromAddress())->toBe('hello@example.com');
    });

    it('returns mail from name using app name', function (): void {
        putenv('MAIL_FROM_NAME=');
        putenv('APP_NAME=Test App');
        expect(Env::make()->mailFromName())->toBe('Test App');
    });
});

describe('Env - OAuth Configuration', function (): void {
    it('returns nullable oauth credentials', function (): void {
        putenv('GOOGLE_CLIENT_ID=');
        expect(Env::make()->googleClientId())->toBeNull();

        putenv('GOOGLE_CLIENT_ID=test-id');
        expect(Env::make()->googleClientId())->toBe('test-id');

        putenv('GITHUB_TOKEN=');
        expect(Env::make()->githubToken())->toBeNull();

        putenv('GITHUB_TOKEN=ghp_test');
        expect(Env::make()->githubToken())->toBe('ghp_test');
    });
});

describe('Env - Security Configuration', function (): void {
    it('returns security headers enabled with default', function (): void {
        putenv('SECURITY_HEADERS_ENABLED=');
        expect(Env::make()->securityHeadersEnabled())->toBeTrue();
    });

    it('returns bcrypt rounds with default', function (): void {
        putenv('BCRYPT_ROUNDS=');
        expect(Env::make()->bcryptRounds())->toBe(12);
    });

    it('returns zxcvbn min score with default', function (): void {
        putenv('ZXCVBN_MIN_SCORE=');
        expect(Env::make()->zxcvbnMinScore())->toBe(3);
    });
});

describe('Env - OCR Configuration', function (): void {
    it('returns ocr driver with default', function (): void {
        putenv('OCR_DRIVER=');
        expect(Env::make()->ocrDriver())->toBe('tesseract');
    });

    it('returns ocr ai enabled as boolean', function (): void {
        putenv('OCR_AI_ENABLED=true');
        expect(Env::make()->ocrAiEnabled())->toBeTrue();

        putenv('OCR_AI_ENABLED=false');
        expect(Env::make()->ocrAiEnabled())->toBeFalse();
    });

    it('returns ocr min confidence as float', function (): void {
        putenv('OCR_MIN_CONFIDENCE=0.7');
        expect(Env::make()->ocrMinConfidence())->toBe(0.7);
    });

    it('returns ocr max file size as integer', function (): void {
        putenv('OCR_MAX_FILE_SIZE=10240');
        expect(Env::make()->ocrMaxFileSize())->toBe(10240);
    });
});

describe('Env - Coverage Configuration', function (): void {
    it('returns pcov enabled with default', function (): void {
        putenv('PCOV_ENABLED=');
        expect(Env::make()->pcovEnabled())->toBeTrue();
    });

    it('returns coverage min percentage as integer', function (): void {
        putenv('COVERAGE_MIN_PERCENTAGE=80');
        expect(Env::make()->coverageMinPercentage())->toBe(80);
    });

    it('returns coverage min type coverage as float', function (): void {
        putenv('COVERAGE_MIN_TYPE_COVERAGE=99.9');
        expect(Env::make()->coverageMinTypeCoverage())->toBe(99.9);
    });
});

describe('Env - Warden Configuration', function (): void {
    it('returns warden schedule enabled with default', function (): void {
        putenv('WARDEN_SCHEDULE_ENABLED=');
        expect(Env::make()->wardenScheduleEnabled())->toBeTrue();
    });

    it('returns warden schedule frequency with default', function (): void {
        putenv('WARDEN_SCHEDULE_FREQUENCY=');
        expect(Env::make()->wardenScheduleFrequency())->toBe('daily');
    });

    it('returns warden cache enabled with default', function (): void {
        putenv('WARDEN_CACHE_ENABLED=');
        expect(Env::make()->wardenCacheEnabled())->toBeTrue();
    });

    it('returns warden cache duration as integer', function (): void {
        putenv('WARDEN_CACHE_DURATION=3600');
        expect(Env::make()->wardenCacheDuration())->toBe(3600);
    });
});

describe('Env - Unsplash Configuration', function (): void {
    it('returns unsplash nullable credentials', function (): void {
        putenv('UNSPLASH_ACCESS_KEY=');
        expect(Env::make()->unsplashAccessKey())->toBeNull();

        putenv('UNSPLASH_ACCESS_KEY=test-key');
        expect(Env::make()->unsplashAccessKey())->toBe('test-key');
    });

    it('returns unsplash cache enabled with default', function (): void {
        putenv('UNSPLASH_CACHE_ENABLED=');
        expect(Env::make()->unsplashCacheEnabled())->toBeTrue();
    });

    it('returns unsplash cache ttl as integer', function (): void {
        putenv('UNSPLASH_CACHE_TTL=3600');
        expect(Env::make()->unsplashCacheTtl())->toBe(3600);
    });

    it('returns unsplash auto download with default', function (): void {
        putenv('UNSPLASH_AUTO_DOWNLOAD=');
        expect(Env::make()->unsplashAutoDownload())->toBeTrue();
    });

    it('returns unsplash http timeout as integer', function (): void {
        putenv('UNSPLASH_HTTP_TIMEOUT=30');
        expect(Env::make()->unsplashHttpTimeout())->toBe(30);
    });

    it('returns unsplash default per page as integer', function (): void {
        putenv('UNSPLASH_DEFAULT_PER_PAGE=30');
        expect(Env::make()->unsplashDefaultPerPage())->toBe(30);
    });
});

describe('Env - AWS Configuration', function (): void {
    it('returns aws nullable credentials', function (): void {
        putenv('AWS_ACCESS_KEY_ID=');
        expect(Env::make()->awsAccessKeyId())->toBeNull();

        putenv('AWS_ACCESS_KEY_ID=AKIA123');
        expect(Env::make()->awsAccessKeyId())->toBe('AKIA123');
    });

    it('returns aws default region with default', function (): void {
        putenv('AWS_DEFAULT_REGION=');
        expect(Env::make()->awsDefaultRegion())->toBe('us-east-1');
    });

    it('returns aws use path style endpoint with default', function (): void {
        putenv('AWS_USE_PATH_STYLE_ENDPOINT=');
        expect(Env::make()->awsUsePathStyleEndpoint())->toBeFalse();
    });
});

describe('Env - Prism AI Configuration', function (): void {
    it('returns prism server enabled with default', function (): void {
        putenv('PRISM_SERVER_ENABLED=');
        expect(Env::make()->prismServerEnabled())->toBeFalse();
    });

    it('returns anthropic api version with default', function (): void {
        putenv('ANTHROPIC_API_VERSION=');
        expect(Env::make()->anthropicApiVersion())->toBe('2023-06-01');
    });

    it('returns ollama url with default', function (): void {
        putenv('OLLAMA_URL=');
        expect(Env::make()->ollamaUrl())->toBe('http://localhost:11434');
    });
});

describe('Env - Geo Configuration', function (): void {
    it('returns geo auto translate with default', function (): void {
        putenv('GEO_AUTO_TRANSLATE=');
        expect(Env::make()->geoAutoTranslate())->toBeTrue();
    });

    it('returns geo phone default country with default', function (): void {
        putenv('GEO_PHONE_DEFAULT_COUNTRY=');
        expect(Env::make()->geoPhoneDefaultCountry())->toBe('us');
    });

    it('returns geo cache ttl minutes as integer', function (): void {
        putenv('GEO_CACHE_TTL_MINUTES=10080');
        expect(Env::make()->geoCacheTtlMinutes())->toBe(10080);
    });
});

describe('Env - System Admin Configuration', function (): void {
    it('returns sysadmin domain as nullable', function (): void {
        putenv('SYSADMIN_DOMAIN=');
        expect(Env::make()->sysadminDomain())->toBeNull();

        putenv('SYSADMIN_DOMAIN=admin.example.com');
        expect(Env::make()->sysadminDomain())->toBe('admin.example.com');
    });

    it('returns sysadmin path with default', function (): void {
        putenv('SYSADMIN_PATH=');
        expect(Env::make()->sysadminPath())->toBe('sysadmin');
    });
});

describe('Env - Community Configuration', function (): void {
    it('returns discord invite url with default', function (): void {
        putenv('DISCORD_INVITE_URL=');
        expect(Env::make()->discordInviteUrl())->toBe('https://discord.gg/b9WxzUce4Q');
    });
});

describe('Env - Monitoring Configuration', function (): void {
    it('returns sentry dsn as nullable', function (): void {
        putenv('SENTRY_LARAVEL_DSN=');
        expect(Env::make()->sentryDsn())->toBeNull();

        putenv('SENTRY_LARAVEL_DSN=https://example@sentry.io/123');
        expect(Env::make()->sentryDsn())->toBe('https://example@sentry.io/123');
    });

    it('returns sentry traces sample rate as float', function (): void {
        putenv('SENTRY_TRACES_SAMPLE_RATE=1.0');
        expect(Env::make()->sentryTracesSampleRate())->toBe(1.0);
    });
});
