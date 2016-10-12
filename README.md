# Restrict

[![Build Status](https://travis-ci.org/steveworley/restrict.svg?branch=master)](https://travis-ci.org/steveworley/restrict)

HTTP Middleware to handle IP and path restrictions.

## Installation

### Composer

`composer require drupal/restrict`

### Manual

```
cd modules/custom
git clone --branch <release> git@github.com:steveworley/restrict.git
```

## Configuration

All configuration for this module is managed via a sites `settings.php` file.

### Available options:

#### `$settings['restrict_whitelist'] Array`

An array of IP addresses to allow on to the site that may use the following syntax:

- CIDR (107.0.255.128/27)
- Range (121.91.2.5-121-121.91.3.4)
- Wildcard (36.222.120.\*)
- Single (9.80.226.4)

``` php
$settings['restrict_whitelist'] = [
  '107.20.238.9',
  '70.102.97.2/30'
];
```

#### `$settings['restrict_blacklist'] Array`

An array of IP addresses that will be denied access to the site that may use the following syntax:

- CIDR (107.0.255.128/27)
- Range (121.91.2.5-121-121.91.3.4)
- Wildcard (36.222.120.\*)
- Single (9.80.226.4)

``` php
$settings['restrict_blacklist'] = [
  '107.20.238.9',
  '70.102.97.2/30'
];
```

#### `$settings['restrict_basic_auth_credentials'] Array`

An array of basic auth username => password combinations.

``` php
$settings['restrict_basic_auth_credentials'] = [
  'Editor' => 'P455w0rd',
  'user' => 'password',
];
```

#### `$settings['restrict_restricted_paths'] Array`

Paths which may not be accessed unless the user is on the IP whitelist. Paths should start with a leading '/'.

``` php
$settings['restrict_restricted_paths'] = [
  '/path',
  '/path/to/restricted/resource',
];
```

#### `$settings['restrict_response_code'] Int`

Returns a 404 instead of a 403 when users are denied. This should be set to a value from the `RestrictManager` class. Possible values:

- `RESTRICT_NOT_FOUND`
- `RESTRICT_UNAUTHORISED`
- `RESTRICT_FORBIDDEN`

``` php
$settings['restrict_response_code'] = 'RESTRICT_NOT_FOUND';
```

#### `$settings['restrict_trusted_proxies'] Array`

`restrict_trusted_proxies` ensures Acquia load balancers and their IPs are added to the trusted proxies list.

``` php
$settings['restrict_trusted_proxies'] = [
  '127.0.0.1',
];
```
