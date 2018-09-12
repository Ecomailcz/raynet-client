# Ecomail Raynet API client

Installation:
```
composer require ecomailcz/raynet-client:~1.0.0
```

Usage:

```
$client = new \EcomailRaynet\Client('your@email.cz', 'crm-key', 'raynet-instance-name');

$client->makeRequest('GET', 'api/v2/person/');
```
