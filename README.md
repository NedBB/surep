# Nigeria CCT Monitoring Dashboard

An interactive web application for monitoring the Conditional Cash Transfer (CCT) Programme under the SURE-P MCH (Subsidy Reinvestment and Empowerment Programme - Maternal and Child Health) initiative in Nigeria.

## Features

### 🌍 Interactive Nigeria Map
- High-resolution SVG map with all 36 states + FCT Abuja
- Color-coded heatmap based on beneficiary activity levels
- Hover tooltips showing state statistics:
  - Total beneficiaries
  - Cash disbursed
  - Number of PHCs participating
  - Active vs exited beneficiaries
- Click on any state to view detailed information

### 📊 Dashboard Statistics
- Real-time national statistics:
  - Total beneficiaries enrolled
  - Active beneficiaries
  - Total cash disbursed (₦)
  - PHCs participating
- Color-coded cards for quick visual reference

### 📈 State-Level Details Drawer
When clicking on a state, a slide-in drawer displays:
- Comprehensive state statistics
- ANC visit tracking (ANC1-4) with progress bars
- Other services tracking:
  - Skilled birth attendance
  - Post-natal care
  - Immunization rates
- LGA and PHC listings
- Export options (Excel/PDF)

### 💰 CCT Payment Tracking
Based on the official CCT co-responsibility matrix:
- Registration & ANC1: ₦1,000
- ANC2 & ANC3: Up to ₦1,000 (pro-rated)
- ANC4: ₦300-600
- Skilled Delivery: ₦2,000
- Post-Natal/Immunization: ₦1,000

### 🔐 Role-Based Access Control
Four user levels as per requirements:
1. **Viewer**: View-only access to general information
2. **Updater**: Can add/edit beneficiary records (state-restricted)
3. **Coordinator**: Full viewing and flagging capabilities
4. **Administrator**: Full system access and user management

## Technology Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Livewire 3 + Volt (Class-based components)
- **Styling**: Tailwind CSS
- **JavaScript**: Alpine.js
- **Charts**: Chart.js
- **Database**: MySQL
- **Export**: Laravel Excel, Spatie PDF

## Database Structure

### Core Tables
1. **zones** - Nigeria's 6 geopolitical zones
2. **states** - 37 states (36 + FCT) with statistics
3. **lgas** - Local Government Areas
4. **wards** - Electoral wards
5. **general_hospitals** - Referral hospitals
6. **phcs** - Primary Health Centers
7. **beneficiaries** - CCT beneficiary records (comprehensive)
8. **cash_transfers** - Payment tracking
9. **users** - System users with roles

### Key Beneficiary Fields
- Personal information (name, age, address, phone)
- Next of kin details
- ANC visit tracking (planned & actual dates)
- Delivery and post-natal services
- Immunization records
- Referral information
- Financial entitlements
- Status tracking (active/completed/dropped_out/deceased/expired)
- Auto-expiry after 9 months

## Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL 8.0+
- Node.js & NPM
- Git

### Setup Steps

1. **Clone the repository**
```bash
cd /var/www/html/surep
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install NPM dependencies**
```bash
npm install
```

4. **Configure environment**
The project uses `data.env` instead of `.env`:
```bash
cp data.env.example data.env
```

Edit `data.env` and configure your database:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=surep_cct
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Create database**
```bash
mysql -u root -p -e "CREATE DATABASE surep_cct CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

6. **Run migrations**
```bash
php artisan migrate
```

7. **Seed the database**
```bash
php artisan db:seed
```

This will create:
- All 37 Nigerian states with zones
- Sample LGAs and PHCs
- Sample beneficiary data
- An admin user (admin@surep.gov.ng / password)

8. **Build frontend assets**
```bash
npm run build
# Or for development with hot reload:
npm run dev
```

9. **Start the development server**
```bash
php artisan serve
```

Visit: `http://localhost:8000/dashboard`

## Project Structure

```
surep/
├── app/
│   ├── Livewire/
│   │   └── NigeriaMap.php          # Interactive map component
│   └── Models/
│       ├── Zone.php
│       ├── State.php
│       ├── Lga.php
│       ├── Ward.php
│       ├── GeneralHospital.php
│       ├── Phc.php
│       ├── Beneficiary.php         # Main CCT beneficiary model
│       ├── CashTransfer.php
│       └── User.php
├── database/
│   ├── migrations/                 # All database tables
│   └── seeders/
│       ├── NigeriaStatesSeeder.php # Zones, states, LGAs, PHCs
│       └── BeneficiariesSeeder.php # Sample beneficiary data
├── resources/
│   └── views/
│       ├── components/
│       │   └── layout.blade.php    # Main layout
│       └── livewire/
│           ├── dashboard.blade.php # Main dashboard (Volt class-based)
│           └── nigeria-map.blade.php # Map SVG component
├── routes/
│   └── web.php                     # Application routes
├── data.env                        # Environment configuration
└── README.md
```

## Usage

### Dashboard Navigation
1. Access the dashboard at `/dashboard`
2. View national statistics at the top
3. Hover over states on the map to see quick stats
4. Click any state to open the detailed drawer
5. Use the drawer to:
   - View comprehensive state data
   - Analyze ANC visit rates
   - Track service delivery
   - Filter by LGA, PHC, date range
   - Export data

### Data Entry (Future Enhancement)
The system is designed to support:
- Offline data entry by CCT field officers
- Periodic sync with cloud database
- Duplicate beneficiary detection
- Auto-expiry after 9 months
- Defaulter tracking

### User Management (Future Enhancement)
- Create users with specific roles
- Assign users to states/PHCs
- Track user activity
- Manage permissions

## Key Features from Requirements

✅ **Implemented:**
- Interactive Nigeria map with 37 states
- Hover tooltips with state statistics
- Click-to-view detailed state information
- Responsive design (mobile-friendly)
- Color-coded heatmap
- Zoom and pan support
- State-level insights with charts
- ANC visit tracking
- Service delivery monitoring
- Export capabilities (structure in place)
- Role-based access control (database structure)
- Comprehensive beneficiary tracking
- Cash transfer calculations
- 9-month auto-expiry
- Defaulter detection logic

📋 **Ready for Implementation:**
- Export to Excel/CSV/PDF (libraries installed)
- Advanced filters (UI components ready)
- User authentication (Laravel built-in)
- Data import from Excel
- Offline sync capabilities
- Report generation
- Duplicate detection workflows

## Database Schema Highlights

### Beneficiary Tracking
- Tracks entire continuum of care (ANC1-4, delivery, PNC, immunization)
- Both planned and actual attendance dates
- Automatic entitlement calculation
- Status management (active/completed/dropped out/deceased/expired)
- Soft deletes for data integrity

### Financial Tracking
- Service-based entitlement breakdown
- Total entitled vs disbursed amounts
- Payment verification and approval workflow
- Integration with partner banks (Stanbic, Zenith)

### Geographical Hierarchy
- Zone → State → LGA → Ward → PHC
- Complete Nigerian administrative structure
- Flexible cluster management

## Security & Best Practices

- Environment variables in `data.env`
- Password hashing (bcrypt)
- CSRF protection (Laravel default)
- SQL injection protection (Eloquent ORM)
- Role-based access control
- Soft deletes for audit trail
- Database timestamps

## Credits

Built for the SURE-P MCH CCT Programme based on:
- **CCT Software System - Requirement Specifications March 2014**
- Sample data from FCT_Kuje_CCTData_Oct2013.xls

## License

Developed for the Federal Government of Nigeria - SURE-P MCH Programme

## Support

For technical support or feature requests, please contact the SURE-P MCH PIU IT team.

---

**Version**: 1.0.0  
**Last Updated**: November 2025  
**Status**: Development/Testing Phase
