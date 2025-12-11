# 🚀 Jira Analytics Portal

Enterprise-level analytics dashboard for Jira Service Management and Software projects with AI-powered insights, team gamification, and advanced reporting.

![Laravel](https://img.shields.io/badge/Laravel-11.x-red)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue)
![License](https://img.shields.io/badge/License-MIT-green)

## ✨ Features

### 📊 Core Analytics
- **Executive Summary** - Real-time KPIs and critical alerts
- **Service Metrics** - Service Desk ticket volume, SLA compliance, breach tracking
- **Demand Metrics** - Sprint velocity, lead time, throughput analysis
- **Agile Board Overview** - Sprint progress, burndown charts
- **Team Performance** - Individual and team metrics

### 🤖 AI-Powered Predictions
- **Volume Forecasting** - Linear regression-based ticket volume prediction
- **SLA Risk Analysis** - Predictive SLA breach detection
- **Capacity Planning** - Team capacity forecasting and bottleneck identification
- **Automated Recommendations** - AI-generated actionable insights

### 🏆 Gamification
- **Team Leaderboard** - Points-based ranking system
- **Badges & Achievements** - Performance recognition
  - 🏆 Super Resolver (50+ tickets)
  - 🔥 Priority Master (10+ high priority)
  - ⚡ Speed Demon (avg <24h resolution)
  - ⭐ Top Performer (500+ points)
- **Team Achievements** - Collective milestones

### 📈 Advanced Analytics
- **Worklog Analysis** - Actual time spent tracking
- **Component & Label Analytics** - Usage patterns
- **Priority Distribution** - Workload analysis
- **Resolution Time by Type** - Performance benchmarking
- **Sprint Velocity** - Agile metrics
- **Dependencies & Blockers** - Risk identification
- **Comment Activity** - Collaboration metrics
- **Reopened Issues Tracking** - Quality metrics

### 🎨 UI/UX Features
- **Dark Mode** - Toggle with `Ctrl/Cmd + D`
- **Real-time Notifications** - Toast alerts for critical events
- **Responsive Design** - Mobile, tablet, and desktop optimized
- **Interactive Charts** - Chart.js powered visualizations
- **Date Range Filters** - 1 week to 1 year views
- **Auto-refresh** - Every 5 minutes
- **Export to PDF** - Print-friendly reports
- **Export to Excel/CSV** - Data export capabilities

### ⌨️ Keyboard Shortcuts
- `Ctrl/Cmd + D` - Toggle Dark Mode
- `Ctrl/Cmd + R` - Refresh Data

## 🛠️ Tech Stack

- **Backend**: Laravel 11.x, PHP 8.2+
- **Frontend**: Alpine.js, Tailwind CSS, Chart.js
- **Database**: SQLite (configurable)
- **APIs**: Jira Cloud REST API v3
- **Export**: PhpSpreadsheet
- **Cache**: Laravel Cache

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & npm
- Jira Cloud instance with API access

## 🚀 Installation

### 1. Clone the repository
```bash
git clone <your-repo-url>
cd jiraportal
```

### 2. Install dependencies
```bash
composer install
npm install
npm run build
```

### 3. Environment setup
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure database
```bash
php artisan migrate
php artisan db:seed
```

### 5. Configure Jira credentials
Access the settings page at `/settings` and enter:
- Jira Base URL (e.g., `https://yourcompany.atlassian.net`)
- Jira Email
- Jira API Token

Or set in `.env`:
```env
JIRA_BASE_URL=https://yourcompany.atlassian.net
JIRA_EMAIL=your-email@company.com
JIRA_API_TOKEN=your-api-token
```

### 6. Start the server
```bash
php artisan serve
```

Visit: `http://127.0.0.1:8000`

## 📁 Project Structure

```
jiraportal/
├── app/
│   ├── Http/Controllers/
│   │   ├── MetricsController.php      # Main metrics API
│   │   └── SettingsController.php     # Settings management
│   ├── Services/
│   │   ├── Analytics/
│   │   │   ├── AdvancedAnalyticsService.php
│   │   │   ├── PredictiveAnalyticsService.php
│   │   │   ├── TeamLeaderboardService.php
│   │   │   ├── TeamHeatmapService.php
│   │   │   └── TrendForecastService.php
│   │   ├── Jira/
│   │   │   ├── JiraClient.php         # Jira API client
│   │   │   ├── ServiceMetricsService.php
│   │   │   └── DemandMetricsService.php
│   │   └── ExportService.php          # Excel/CSV export
│   └── Models/
│       └── Setting.php                # Settings model
├── resources/
│   └── views/
│       ├── dashboard.blade.php        # Main dashboard
│       ├── advanced.blade.php         # Advanced analytics
│       └── settings.blade.php         # Settings page
└── routes/
    └── web.php                        # Route definitions
```

## 🔌 API Endpoints

### Metrics
- `GET /api/metrics/stats` - General statistics
- `GET /api/metrics/charts?days={days}` - Chart data
- `GET /api/metrics/executive` - Executive summary
- `GET /api/metrics/advanced?days={days}` - Advanced analytics
- `GET /api/metrics/predictions` - AI predictions
- `GET /api/metrics/leaderboard?days={days}` - Team leaderboard
- `POST /api/metrics/refresh` - Refresh cache

### Analytics
- `GET /api/metrics/trend` - Trend analysis
- `GET /api/metrics/heatmap` - Team heatmap
- `GET /api/metrics/service` - Service metrics
- `GET /api/metrics/demand` - Demand metrics

## 🎯 Configuration

### Jira Project IDs
Update in your services or environment:
- Service Desk ID: `265`
- Queue ID: `438`
- Demand Project: `BTID`
- Agile Board ID: `331`

### SLA Thresholds
Configured in `MetricsController`:
- 🟢 Good: ≥95%
- 🟡 Warning: 93-95%
- 🔴 Critical: <93%

### Cache Duration
- Default: 600 seconds (10 minutes)
- Configurable per service

## 🎨 Design System

### Colors
- **Primary**: #002D72 (Sabancı Blue)
- **Success**: #10B981
- **Warning**: #F59E0B
- **Danger**: #EF4444
- **Info**: #3B82F6

### Spacing
- xs: 0.25rem
- sm: 0.5rem
- md: 1rem
- lg: 1.5rem
- xl: 2rem
- 2xl: 3rem

## 🔒 Security

- API tokens stored in database (encrypted)
- CSRF protection enabled
- Input validation on all forms
- Secure API communication

## 📊 Performance

- **Caching**: All Jira API calls cached (10 min default)
- **Lazy Loading**: Charts loaded on demand
- **Optimized Queries**: Minimal API calls
- **Auto-refresh**: Background updates every 5 minutes

## 🐛 Troubleshooting

### Jira API Connection Issues
1. Verify API token is valid
2. Check base URL format (no trailing slash)
3. Ensure email matches Jira account
4. Check Jira API rate limits

### Cache Issues
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Permission Issues
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License.

## 👥 Authors

- **Baris Delikaya** - Initial work

## 🙏 Acknowledgments

- Laravel Framework
- Jira Cloud REST API
- Chart.js
- Alpine.js
- PhpSpreadsheet

## 📞 Support

For support, email your-email@company.com or open an issue in the repository.

## 🗺️ Roadmap

- [ ] Multi-tenant support
- [ ] Custom dashboard widgets
- [ ] Email report scheduling
- [ ] Slack/Teams integration
- [ ] Advanced filtering
- [ ] Custom metrics builder
- [ ] Mobile app (PWA)
- [ ] Real-time WebSocket updates

---

Made with ❤️ for better Jira analytics
