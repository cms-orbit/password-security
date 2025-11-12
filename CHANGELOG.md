# Changelog

All notable changes to `password-security` will be documented in this file.

## 1.0.0 - 2025-01-01

### Added
- Initial release
- Password complexity validation (2+ types with 10+ chars, or 3+ types with 8+ chars)
- Common pattern detection (sequential numbers/letters, repeated chars, keyboard patterns, etc.)
- Personal information blocking (name, email, etc.)
- Password history management (prevent reuse of last N passwords)
- Password expiration (force change every 90 days)
- Inactive account management (deactivate after 90 days of inactivity)
- Middleware for password expiration and account status checking
- Artisan commands for batch processing
- Email notifications for expiration and deactivation
- Multi-language support (Korean, English)
- Comprehensive test suite

