# WhatsApp Automation Platform

A comprehensive WhatsApp Business Automation and Chatbot Platform built with Laravel 11 and Laravel Nova, designed for small businesses to automate their WhatsApp communications.

## Features

### Core Functionality
- **WhatsApp Business Integration** - Connect with WhatsApp Cloud API
- **Chatbot Builder** - Visual flow builder for automated responses
- **Contact Management** - Import, tag, and segment contacts
- **Broadcast Messaging** - Send bulk messages with scheduling
- **Campaign Analytics** - Track delivery and engagement metrics
- **Conversation Inbox** - Real-time chat interface for operators
- **Template Management** - Create and manage WhatsApp templates
- **Media Library** - Upload and manage media files
- **Role-Based Permissions** - Multi-user access control

### Technical Stack
- **Backend**: Laravel 11
- **Admin Panel**: Laravel Nova 4
- **Frontend**: Blade + TailwindCSS + Alpine.js
- **Database**: MySQL
- **Queue System**: Redis
- **API**: RESTful architecture
- **Authentication**: Laravel Sanctum + Spatie Permissions

## Project Structure

```
whatsapp-platform/
├── app/
│   ├── Http/Controllers/Api/     # API Controllers
│   ├── Models/                   # Eloquent Models
│   ├── Nova/                     # Nova Resources
│   ├── Services/                # Business Logic Services
│   ├── Jobs/                    # Queue Jobs
│   └── Providers/               # Service Providers
├── database/
│   ├── migrations/              # Database Migrations
│   └── seeders/                 # Database Seeders
├── routes/
│   ├── api.php                  # API Routes
│   └── web.php                  # Web Routes
└── resources/
    └── views/                   # Blade Templates
```

## Installation

### Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Redis
- Composer
- Node.js & NPM

### Step 1: Setup Environment
```bash
# Clone the repository
git clone <repository-url>
cd whatsapp-platform

# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 2: Configure Environment
Edit your `.env` file:

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=whatsapp_platform
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# WhatsApp Cloud API Configuration
WHATSAPP_ACCESS_TOKEN=your_access_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_webhook_token
WHATSAPP_API_VERSION=v18.0

# Nova Configuration
NOVA_LICENSE_KEY=your_nova_license_key

# Queue Configuration
QUEUE_CONNECTION=redis
```

### Step 3: Setup Database
```bash
# Create database
mysql -u root -p
CREATE DATABASE whatsapp_platform;

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed
```

### Step 4: Install Nova
```bash
# Install Nova (requires license)
composer require laravel/nova

# Publish Nova assets
php artisan vendor:publish --tag=nova-assets

# Install Nova in your app/Providers/NovaServiceProvider.php
```

### Step 5: Setup Queue Worker
```bash
# Start queue worker
php artisan queue:work --redis

# Or use Supervisor for production
```

### Step 6: Setup Webhook
Configure your WhatsApp Business webhook to point to:
```
https://your-domain.com/api/v1/whatsapp/webhook
```

## WhatsApp Business API Setup

### 1. Create Meta Developer Account
1. Go to [developers.facebook.com](https://developers.facebook.com)
2. Create a new app
3. Select "Business" category
4. Add "WhatsApp" product

### 2. Configure WhatsApp
1. Get your WhatsApp Business phone number
2. Configure webhook URL
3. Set up webhooks for:
   - Messages
   - Message status updates
   - Account updates

### 3. Get Credentials
- **Access Token**: Generate from Meta Developer Portal
- **Phone Number ID**: Found in WhatsApp settings
- **Webhook Verify Token**: Set your own secure token

## Usage

### Admin Panel Access
1. Navigate to `/nova` in your browser
2. Login with default credentials:
   - Email: `admin@whatsapp-platform.com`
   - Password: `password`

### Managing Contacts
1. Go to Contacts in Nova
2. Import contacts via CSV
3. Add tags for segmentation
4. View conversation history

### Creating Campaigns
1. Navigate to Campaigns
2. Create new campaign
3. Select target contacts or tags
4. Compose message
5. Schedule or send immediately

### Building Chatbots
1. Go to Chatbot Flows
2. Create new flow
3. Add trigger keywords
4. Build conversation nodes
5. Test and activate

### Template Management
1. Access Templates section
2. Create message templates
3. Submit for WhatsApp approval
4. Use in campaigns or chatbots

## API Documentation

### Authentication
All API endpoints require authentication via Laravel Sanctum tokens.

```bash
# Get token
curl -X POST http://your-domain.com/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password"}'
```

### Send Message
```bash
curl -X POST http://your-domain.com/api/v1/messages/send \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "contact_id": 1,
    "type": "text",
    "content": "Hello from API!"
  }'
```

### Webhook Endpoints
- **GET** `/api/v1/whatsapp/webhook` - Verify webhook
- **POST** `/api/v1/whatsapp/webhook` - Receive messages

## Deployment

### Production Setup
```bash
# Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set up production queue
php artisan queue:restart

# Schedule cron job for periodic tasks
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Environment Variables for Production
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Use production queue
QUEUE_CONNECTION=redis

# Enable HTTPS
FORCE_HTTPS=true
```

## Security Features

### Rate Limiting
- API requests: 60 per minute
- Bulk messages: 1000 per batch
- Configurable per environment

### Authentication
- Laravel Sanctum for API tokens
- Role-based permissions
- Secure webhook verification

### Data Protection
- Input validation
- SQL injection prevention
- XSS protection
- CSRF protection

## Monitoring & Analytics

### Built-in Metrics
- Message delivery rates
- Campaign performance
- Chatbot engagement
- User activity

### Logs
- WhatsApp API interactions
- Campaign execution
- Error tracking
- Performance monitoring

## Troubleshooting

### Common Issues

#### WhatsApp Not Receiving Messages
1. Check webhook URL is accessible
2. Verify webhook is configured in Meta Developer Portal
3. Check SSL certificate validity
4. Review webhook logs

#### Queue Jobs Not Processing
1. Ensure Redis is running
2. Check queue worker status
3. Verify queue configuration
4. Check failed jobs table

#### Nova Access Issues
1. Verify Nova license key
2. Check user permissions
3. Clear config cache
4. Review Nova configuration

### Debug Mode
```bash
# Enable debug mode
APP_DEBUG=true

# Check logs
tail -f storage/logs/laravel.log

# Monitor queue
php artisan queue:failed
php artisan queue:monitor
```

## Contributing

1. Fork the repository
2. Create feature branch
3. Make your changes
4. Add tests
5. Submit pull request

## License

This project is licensed under the MIT License.

## Support

For support and questions:
- Create an issue on GitHub
- Check documentation
- Review troubleshooting guide

## Roadmap

### Upcoming Features
- Multi-language support
- Advanced analytics dashboard
- AI-powered chatbot suggestions
- Integration with CRM systems
- Mobile app for operators
- Advanced scheduling options
- Message templates with variables
- Voice message support

### Performance Improvements
- Database optimization
- Caching strategies
- Load balancing
- CDN integration

---

**Note**: This platform requires a valid WhatsApp Business API account and Meta Developer access. Ensure compliance with WhatsApp's terms of service and usage policies.
#   w h a t s a p p _ p l a t f o r m  
 