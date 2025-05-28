# Magento 1.x Project Setup

This repository contains a Magento 1.x project using OpenMage LTS. Below you'll find instructions for setting up, running, and troubleshooting common issues.

## Prerequisites

- PHP 8.3
- Composer
- DDEV (for local development)
- Required PHP extensions:
  - soap
  - mysql
  - curl
  - gd
  - intl
  - xsl
  - zip
  - dom
  - xmlwriter
  - xmlreader
  - mcrypt
  - iconv
  - mbstring

## Initial Setup

1. Clone the repository:
```bash
git clone <repository-url>
cd <project-directory>
```

2. Install PHP SOAP extension (if not installed):
```bash
sudo apt install php8.3-soap
```

3. Configure Composer platform requirements:
```bash
composer config --unset platform.php
```

4. Install OpenMage LTS:
```bash
composer require "openmage/magento-lts":"^20.0.0"
```

## DDEV Configuration

1. Initialize DDEV:
```bash
ddev config
```

2. Start DDEV:
```bash
ddev start
```

## Common Commands

### Composer Commands

- Update dependencies:
```bash
composer update
```

- Install dependencies:
```bash
composer install
```

- Configure Magento root directory:
```bash
ddev composer config extra.magento-root-dir "public"
```

### DDEV Commands

- Start environment:
```bash
ddev start
```

- Stop environment:
```bash
ddev stop
```

- SSH into container:
```bash
ddev ssh
```

## Troubleshooting

### 1. Composer Plugin Issues

If you see messages about plugins not being loaded:

```
The "cweagans/composer-patches" plugin was not loaded...
The "magento-hackathon/magento-composer-installer" plugin was not loaded...
The "openmage/composer-plugin" plugin was not loaded...
```

Solution:
Add the following to your composer.json:

```json
{
    "config": {
        "allow-plugins": {
            "cweagans/composer-patches": true,
            "magento-hackathon/magento-composer-installer": true,
            "openmage/composer-plugin": true,
            "aydin-hassan/magento-core-composer-installer": true
        }
    }
}
```

### 2. PHP Version Conflicts

If you encounter PHP version compatibility issues:

```
openmage/magento-lts require php >=7.0 <7.5 -> your php version (8.4) does not satisfy that requirement
```

Solution:
1. Unset the platform PHP version:
```bash
composer config --unset platform.php
```

2. Install the correct OpenMage version:
```bash
composer require "openmage/magento-lts":"^20.0.0"
```

### 3. Missing PHP Extensions

If you see errors about missing PHP extensions:

Solution:
Install the required PHP extension. For example, for SOAP:
```bash
sudo apt install php8.3-soap
```