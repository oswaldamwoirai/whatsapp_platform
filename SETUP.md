# WhatsApp Automation Platform - Quick Setup Guide

## Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Redis
- Composer
- Node.js & NPM

## Quick Setup (15 minutes)

### 1. Install Dependencies
```bash
composer install
npm install
```

### 2. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
DB_DATABASE=whatsapp_platform
DB_USERNAME=root
DB_PASSWORD=

WHATSAPP_ACCESS_TOKEN=your_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_id
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_webhook_token
```

### 3. Setup Database
```bash
php artisan migrate
php artisan db:seed
```

### 4. Install Nova
```bash
composer require laravel/nova
# Add Nova license key to .env
NOVA_LICENSE_KEY=your_nova_key
```

### 5. Start Services
```bash
# Start queue worker
php artisan queue:work --redis

# Start development server
php artisan serve
```

### 6. Access Admin Panel
- URL: `http://localhost:8000/nova`
- Email: `admin@whatsapp-platform.com`
- Password: `password`

## WhatsApp Setup

### Get API Credentials
1. Go to [developers.facebook.com](https://developers.facebook.com)
2. Create app → Add WhatsApp product
3. Get Access Token, Phone Number ID
4. Configure webhook: `http://localhost:8000/api/v1/whatsapp/webhook`

### Test Integration
```bash
# Test WhatsApp connection
php artisan tinker
>>> app(WhatsAppService::class)->testConnection();
```

## Key Features Ready Out of the Box

✅ **Contact Management** - Import, tag, segment contacts  
✅ **Campaign System** - Send bulk messages with analytics  
✅ **Chatbot Builder** - Visual flow creation  
✅ **Message Templates** - WhatsApp template management  
✅ **Conversation Inbox** - Real-time chat interface  
✅ **Role Management** - User permissions and roles  
✅ **Media Library** - File upload and management  
✅ **Analytics Dashboard** - Campaign performance metrics  

## Common Commands

```bash
# Clear cache
php artisan config:clear
php artisan cache:clear

# Reset database
php artisan migrate:fresh --seed

# Check queue status
php artisan queue:monitor

# View logs
tail -f storage/logs/laravel.log
```

## Next Steps

1. Configure your WhatsApp Business API
2. Import your first contacts
3. Create a test campaign
4. Build your first chatbot flow
5. Set up production queue workers

## Support

- Check the full README.md for detailed documentation
- Review troubleshooting section for common issues
- Ensure WhatsApp webhook is properly configured
