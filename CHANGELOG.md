# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2024-10-07

### Added
- Initial Laravel package release
- Eloquent models for all workflow entities
- Spatie Media Library integration for file attachments
- Laravel Service Provider and configuration
- Database migrations for Laravel
- ApprovalService class replacing ApprovalHandler
- Facade support for easier usage
- Comprehensive documentation
- Support for Laravel 9.x, 10.x, and 11.x
- File attachment support in approval histories
- Media conversions (thumbnails and previews)
- Expression Language for flow step conditions
- System groups for dynamic approver assignment
- Repository pattern with Eloquent implementation

### Features
- **File Management**: Upload and manage files with approval histories using Spatie Media Library
- **Flexible Flows**: Configure approval flows with conditional steps
- **System Integration**: Full Laravel integration with service providers, facades, and configuration
- **Media Support**: Automatic thumbnail and preview generation for uploaded files
- **Dynamic Approvers**: System groups for automatic approver resolution
- **Expression Conditions**: Use Symfony Expression Language for step conditions

### Migration from Non-Laravel Version
- Maintains backward compatibility with existing database structure
- Provides migration path from PDO-based implementation to Eloquent
- Preserves all existing functionality while adding Laravel-specific features
