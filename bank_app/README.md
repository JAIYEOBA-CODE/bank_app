# FedBank Digital Wallet System

A complete digital wallet application for managing online transactions and monthly budgeting. Built with PHP, MySQLi, Bootstrap 5, and Vanilla JavaScript.

## Overview

FedBank Digital Wallet is a full-featured banking application that allows users to:
- Create accounts and manage their digital wallet
- Deposit virtual money (no payment gateway required)
- Send and receive money between users
- Request money from other users
- Track all transactions with detailed history
- Set and enforce monthly spending budgets

## Features

### User Management
- **Registration**: Users can create accounts with email and password. System automatically generates unique account numbers.
- **Authentication**: Secure login system with password hashing and session management.
- **Account Management**: Each user gets a unique account number and can view their balance and transaction history.

### Money Operations

#### Virtual Deposits
- Users can add virtual money to their wallet
- No payment gateway integration - perfect for testing and demonstration
- Instant balance updates after deposit
- Transaction limits: Minimum ₦0.01, Maximum ₦1,000,000

#### Send Money
- Transfer funds to other users using email or account number
- Real-time balance updates for both sender and recipient
- Transaction records created for both parties
- Budget enforcement prevents overspending

#### Request Money
- Send money requests to other users
- Recipients see pending requests in their dashboard
- One-click approval transfers money automatically
- Both parties receive transaction notifications

### Transaction Management
- **Complete History**: View all transactions with date, type, amount, and status
- **Filtering**: Filter transactions by All, Credit (money in), or Debit (money out)
- **Details**: See recipient/sender information, descriptions, and transaction status
- **Real-time Updates**: Transactions appear immediately after completion

### Budget System
- **Monthly Targets**: Set spending limits for each month
- **Automatic Tracking**: System tracks all outgoing transactions (sends and approved requests)
- **Enforcement**: Prevents users from spending more than their monthly budget
- **Visual Progress**: See budget progress with progress bars and percentage indicators
- **Smart Alerts**: Clear error messages when transactions would exceed budget

## How It Works

### Registration & Login Flow
1. User visits landing page and fills registration form
2. System validates input, hashes password, and generates account number
3. User is automatically logged in and redirected to dashboard
4. For login, system verifies credentials and creates session

### Deposit Process
1. User navigates to deposit page
2. Enters amount to deposit
3. System validates amount within limits
4. Balance is updated in database
5. Transaction record is created
6. User sees updated balance immediately

### Send Money Flow
1. User enters recipient's email or account number
2. System finds recipient in database
3. Validates sender has sufficient balance
4. Checks if transaction would exceed monthly budget (if set)
5. If validations pass:
   - Deducts amount from sender's balance
   - Adds amount to recipient's balance
   - Creates transaction records for both users
6. Both parties see updated balances and transaction history

### Request Money Flow
1. User enters recipient and amount to request
2. System creates pending request records
3. Recipient sees request in their dashboard
4. When recipient approves:
   - System validates approver has sufficient balance
   - Checks budget limits
   - Transfers money from approver to requester
   - Updates all transaction records
   - Both balances update immediately

### Budget Enforcement
1. User sets monthly spending target
2. System tracks all outgoing transactions for the month
3. Before any send or approval:
   - Calculates current month spending
   - Adds pending transaction amount
   - Compares with budget target
   - Blocks transaction if it would exceed budget
4. Shows clear error message with remaining budget

## Technology Stack

- **Frontend**: HTML5, CSS3, Bootstrap 5, Vanilla JavaScript
- **Backend**: PHP 7.4+ with MySQLi
- **Database**: MySQL
- **Server**: Apache (XAMPP compatible)

## Installation

### Requirements
- XAMPP (or any PHP/MySQL server)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

### Setup Steps

1. **Place Project Files**
   - Extract project to `C:\xampp\htdocs\bank_app\` (or your web server directory)

2. **Start Services**
   - Open XAMPP Control Panel
   - Start Apache
   - Start MySQL

3. **Database Configuration** (Optional)
   - Default settings work with XAMPP (localhost, root, no password)
   - Edit `config/database.php` if your MySQL settings differ
   - Database `fedbank_wallet` is created automatically on first access

4. **Access Application**
   - Open browser and go to: `http://localhost/bank_app/`
   - Register a new account to get started

## Project Structure

```
bank_app/
├── index.php              # Landing page with login/registration
├── dashboard.php          # Main user dashboard
├── deposit.php            # Virtual money deposit
├── send_money.php         # Send money to users
├── request_money.php      # Request money from users
├── transactions.php       # Transaction history
├── budget.php             # Budget management
├── config/
│   └── database.php       # Database connection & setup
├── includes/
│   ├── auth.php          # Authentication functions
│   ├── functions.php      # Helper functions
│   └── logout.php        # Logout handler
└── assets/
    ├── css/
    │   └── style.css      # Custom styles
    └── js/
        └── main.js        # JavaScript functionality
```

## Database

The system uses three main tables:

- **users**: Stores user accounts, balances, and account numbers
- **transactions**: Records all money movements (deposits, sends, receives, requests)
- **budgets**: Stores monthly spending targets for users

Tables are automatically created when you first access the application.

## Security Features

- **Password Hashing**: All passwords are hashed using bcrypt
- **SQL Injection Prevention**: All database queries use prepared statements
- **Input Validation**: All user inputs are validated and sanitized
- **Session Management**: Secure session handling for user authentication
- **XSS Protection**: All output is properly escaped

## Key Features Explained

### Balance Updates
All balance changes use database transactions to ensure consistency. Money is never lost or duplicated - if any step fails, the entire transaction is rolled back.

### Budget System
The budget system only counts outgoing money (sends and approved requests). Incoming money (deposits, receives) doesn't affect your budget. This helps you control spending while still allowing you to receive money.

### Transaction Safety
Every money transfer is wrapped in a database transaction. This means either all steps complete successfully, or nothing happens. This prevents partial transfers and ensures data integrity.

### Real-time Updates
After any transaction, user balances are immediately refreshed from the database, ensuring the displayed balance is always accurate.

## Usage

1. **Register**: Create an account on the landing page
2. **Deposit**: Add virtual money to your wallet
3. **Send**: Transfer money to other users
4. **Request**: Ask other users for money
5. **Budget**: Set monthly spending limits
6. **Track**: View all your transactions

## Mobile Responsive

The application is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones

Features include:
- Hamburger menu for mobile navigation
- Touch-friendly buttons and forms
- Optimized layouts for small screens
- Smooth animations and transitions

## Troubleshooting

**Can't connect to database?**
- Ensure MySQL is running in XAMPP
- Check database credentials in `config/database.php`

**Balance not updating?**
- Check that transactions are completing successfully
- Verify database is accessible
- Check PHP error logs

**Budget not working?**
- Ensure budget is set for current month
- Check that transactions are being recorded correctly

## Future Enhancements

Potential features for future versions:
- Email notifications
- Transaction categories
- Spending analytics and reports
- Multi-currency support
- Scheduled payments
- Admin dashboard

## License

Open source - available for educational and development purposes.

## Support

For issues:
- Check PHP error logs
- Verify XAMPP services are running
- Ensure database connection is working
- Review code comments in source files

---

**FedBank Digital Wallet System** - Secure, Fast, and Reliable Digital Banking

