# HELA Alize Package

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/laravel-10%20%7C%2011-red)](https://laravel.com/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Production Ready](https://img.shields.io/badge/production-ready-brightgreen)](PRODUCTION_READY.md)

Mexican Number Portability (NUMLEX) Package for Laravel - ABD v2.1 Compliant

## 🚀 Installation

```bash
composer require ometra/hela-alize
php artisan vendor:publish --tag=config --provider="Ometra\HelaAlize\HelaAlizeServiceProvider"
php artisan migrate
```

## 📖 Quick Start

```php
use Ometra\HelaAlize\Orchestration\PortationFlowHandler;

$handler = new PortationFlowHandler();
$portability = $handler->initiatePortation([
    'port_type' => 'MOBILE',
    'numbers' => [['start' => '5512345678', 'end' => '5512345678']],
    // ... see full documentation
]);
```

## 📚 Documentation

- [Full Documentation](README.md)
- [Production Readiness](PRODUCTION_READY.md)
- [Coding Standards](CODING_STANDARDS.md)

## 🧪 Development

```bash
composer format        # Format code
composer analyze       # Static analysis
composer test          # Run tests
composer quality       # Run all checks
```

## ✨ Features

✅ Complete RIDA portation flow  
✅ SOAP/XML message handling  
✅ State machine with ABD compliance  
✅ Timer management (T1, T3, T4)  
✅ XSD validation  
✅ TLS-enabled client  

## 📦 Requirements

- PHP 8.1+
- Laravel 10.x or 11.x
- ext-soap, ext-dom

## 📄 License

MIT License - See [LICENSE](LICENSE) file

## 🤝 Support

Contact HELA Development Team

---

**Status**: Production Ready | **Version**: 1.0.0 | **NUMLEX Spec**: v2.1
