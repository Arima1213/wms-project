import json

path = r'C:\Users\ASUS\Downloads\docker-setup\wms-project\backend\composer.json'
data = {
    'name': 'wms/backend',
    'type': 'project',
    'description': 'WMS Multi-Gudang - Laravel 11 Backend API',
    'require': {
        'php': '^8.3',
        'laravel/framework': '^11.0',
        'laravel/sanctum': '^4.0',
        'spatie/laravel-permission': '^6.0',
        'laravel/horizon': '^5.0',
        'predis/predis': '^2.0',
        'meilisearch/meilisearch-php': '^1.0',
        'league/flysystem-aws-s3-v3': '^3.0',
        'barryvdh/laravel-dompdf': '^3.0',
        'ulid/ulid': '^1.0',
        'ext-pdo': '*',
        'ext-pgsql': '*'
    },
    'require-dev': {
        'fakerphp/faker': '^1.23',
        'laravel/pint': '^1.0',
        'mockery/mockery': '^1.6',
        'nunomaduro/collision': '^8.0',
        'phpunit/phpunit': '^11.0'
    },
    'autoload': {
        'psr-4': {
            'App' + chr(92): 'app/',
            'Database' + chr(92) + 'Factories' + chr(92): 'database/factories/',
            'Database' + chr(92) + 'Seeders' + chr(92): 'database/seeders/'
        }
    },
    'autoload-dev': {
        'psr-4': {
            'Tests' + chr(92): 'tests/'
        }
    },
    'scripts': {
        'post-autoload-dump': [
            'Illuminate' + chr(92) + 'Foundation' + chr(92) + 'ComposerScripts::postAutoloadDump',
            '@php artisan package:discover --ansi'
        ]
    },
    'extra': {
        'laravel': {
            'dont-discover': []
        }
    },
    'config': {
        'optimize-autoloader': True,
        'preferred-install': 'dist',
        'sort-packages': True,
        'allow-plugins': {
            'php-http/discovery': True
        }
    },
    'minimum-stability': 'stable',
    'prefer-stable': True
}

with open(path, 'w') as f:
    json.dump(data, f, indent=4)

print('Written!')

# Verify
with open(path) as f:
    content = f.read()
print(content[:400])