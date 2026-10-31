# Aza Akaunting

Aza Akaunting is a web-based business management system for sales, inventory, purchasing, customers, suppliers, expenses, employees, and reporting. It is built with PHP 8.2+, CodeIgniter 4, and MySQL or MariaDB.

## Features

- Point of sale, quotations, invoices, and receipts
- Item, stock, and inventory management
- Purchasing and receiving
- Customer and supplier records
- Expenses, cash-up, and reporting
- Employee accounts with permission controls
- Barcode generation and printing
- Gift cards, rewards, and restaurant tables
- Multiple languages, currencies, and tax settings

## Requirements

- PHP version supported by `composer.json` (PHP 8.2 or newer)
- MySQL or MariaDB
- Composer
- Node.js and npm for building frontend assets

## Installation

See [docs/INSTALL.md](docs/INSTALL.md) for installation and configuration. For development setup and asset builds, see [docs/BUILD.md](docs/BUILD.md). For shared hosting deployment on Namecheap cPanel, see [docs/namecheap-shared-hosting-deployment.md](docs/namecheap-shared-hosting-deployment.md).

## Public website

The Aza Akaunting marketing website is in [`aza akaunting_public website`](<aza akaunting_public website/>). It contains the public home, feature details, FAQ, and About pages. The application can be deployed under `/app/` alongside the public pages.

## License and attribution

Aza Akaunting is based on Open Source Point of Sale (OSPOS) and includes its MIT license with additional attribution requirements. Keep the upstream [`LICENSE`](LICENSE) file with every distribution. The required footer signature linking to `opensourcepos.org`, including the version and commit hash, must remain visible and unchanged on every application page. This project does not claim ownership of the upstream software.

## Support and contributions

For Aza Akaunting setup or product questions, use the project’s GitHub issues. When reporting a bug, include the application version, PHP version, database version, and steps to reproduce it. Do not include passwords, API keys, or other secrets in issues.
