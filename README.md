# RYDO: Online Cab, Bike Taxi & Parcel Delivery Platform

**Mini-Project-I (Course Code: UCBIL0371)**
Department of Computer Science and Business Systems
Kolhapur Institute of Technology's College of Engineering (KITCoEK), Autonomous
Academic Year 2026–27 | Semester III

**Team Members**
1. Aryan Chougule (Roll No 06, PRN 2526UCB190)
2. Sainath Chavan (Roll No 05, PRN 2526UCB176)
3. Sumeet Keroor (Roll No 25, PRN 2526UCB184)
4. Varad Akolkar (Roll No 02, PRN 2526UCB200)
5. Atif Gulbarga (Roll No 34, PRN 2526UCB199)

**Project Guide:** Prof. Sheetal Gaikwad

---

## Project Overview

RYDO is an on-demand micro-mobility platform offering bike taxi, auto, cab, and intra-city parcel delivery (up to 5 kg). It connects customers with nearby captains (drivers) and gives administrators a console for operations and captain verification. The project follows the SRS (IEEE 830) requirements.

The platform has three sub-systems:

**1. Customer Web App** (`index.php`, `confirmation.php`)
- Sign up and log in with email and password.
- Book a ride (Bike, Auto, Cab) or a RYDO Parcel.
- Live fare estimate based on route distance.
- 4-digit security PIN generated for each booking.
- Simulated live trip tracking and an SOS button.
- Post-trip rating and feedback.

**2. Captain Partner Portal** (`captain/`)
- Log in with registered mobile number and license number.
- Toggle duty status between Online and Offline.
- View assigned trips and earnings.
- Start a trip by entering the customer's 4-digit PIN.
- Complete trips and receive the 80% net payout (after the 20% platform commission).

**3. Admin Console** (`admin/`, plus `login.php?role=admin`)
- Operational overview: active trips, online captains, gross bookings, and 20% commission revenue.
- Captain KYC queue: approve or reject captains.
- Monitor trips and parcels and update their status.

---

## Directory Structure

```
OnlineCabBooking/
├── index.php              Customer booking page (requires login)
├── book.php               Booking handler: creates PIN, calculates fare, assigns captain
├── confirmation.php       Trip confirmation, PIN display, live tracking, SOS, rating
├── login.php              Unified login for customers and admins
├── signin.php             Customer sign-up
├── logout.php             Customer logout
├── setup_admin.php        One-time admin password setup (delete after use)
├── database.sql           MySQL schema, seed captains, sample bookings
├── README.md
│
├── config/
│   ├── db.php             PDO database connection
│   ├── routes.php         Route distances (km) between Kolhapur locations
│   └── fares.php          Fare rates per vehicle type
│
├── captain/
│   ├── login.php          Captain login (mobile number + license number)
│   ├── dashboard.php      Captain portal: duty toggle, PIN verification, trips, earnings
│   └── logout.php         Captain logout
│
├── admin/
│   ├── login.php          Redirects to the unified login page (Admin tab)
│   ├── dashboard.php      Admin console: stats, trips, captain KYC queue
│   ├── update_status.php  Handles booking status and captain KYC updates
│   └── logout.php         Admin logout
│
└── assets/
    ├── css/
    │   ├── style.css      Shared design system (layout, forms, buttons, badges)
    │   └── admin.css      Admin and captain tables, stat cards, pagination
    └── js/
        └── main.js        Fare preview, service switcher, form validation
```

---

## Setup (XAMPP on Windows)

**1. Copy the project**
Place the `OnlineCabBooking` folder in `C:\xampp\htdocs\`.

**2. Start services**
Open the XAMPP Control Panel and start **Apache** and **MySQL**.

**3. Import the database**
1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2. Select the **Import** tab and choose `database.sql`.
3. Click **Go**. This creates `rydo_db` with the `users`, `admins`, `captains`, and `bookings` tables, plus seed captains and sample bookings.

**4. Create the admin account**
Open [http://localhost/OnlineCabBooking/setup_admin.php](http://localhost/OnlineCabBooking/setup_admin.php). It creates the admin account with the username `admin` and password `Admin@1234`. Delete `setup_admin.php` once you see the success message.

**5. Open the applications**

| Application | URL |
|---|---|
| Customer app | http://localhost/OnlineCabBooking/ |
| Login (customers and admins) | http://localhost/OnlineCabBooking/login.php |
| Customer sign-up | http://localhost/OnlineCabBooking/signin.php |
| Admin console | http://localhost/OnlineCabBooking/login.php?role=admin |
| Captain portal | http://localhost/OnlineCabBooking/captain/login.php |

---

## Test Accounts

**Admin**
- Username: `admin`
- Password: `Admin@1234`

**Captains** (seeded in `database.sql`, log in with mobile number and license number)

| Name | Mobile | License | KYC |
|---|---|---|---|
| Vikram Patil | 9822114455 | MH092021004123 | Approved |
| Sanjay Jadhav | 9765332211 | MH092019008942 | Approved |
| Mahesh Deshmukh | 9921445566 | MH092018001155 | Approved |
| Amol Kulkarni | 9890123456 | MH092023009911 | Pending |

**Customers:** create an account with the sign-up page.

---

## Fare Structure

| Service | Base fare (first 2 km) | Per additional km | Notes |
|---|---|---|---|
| Bike Taxi | ₹20 | ₹8 | Single rider |
| Auto | ₹30 | ₹12 | 3-seater |
| Prime Cab | ₹50 | ₹16 | 4-seater, air-conditioned |
| Parcel | ₹25 | ₹10 | Packages up to 5 kg |

Corridors include KIT College of Engineering, Central Bus Stand, Railway Station, Mahalaxmi Temple, Rankala Lake, DYP City Mall, Shivaji University, CPR Hospital, Shiroli MIDC, and Airport.

---

## SRS Feature Mapping

| SRS Section | Requirement | Implemented In |
|---|---|---|
| 1.4, 4.1 | Bike, auto, cab, and parcel booking | `index.php`, `book.php` |
| 4.2 | 4-digit PIN verification before trip start | `confirmation.php`, `captain/dashboard.php` |
| 4.3 | Live tracking and SOS alert | `confirmation.php` |
| 4.4 | Parcel receiver details, 5 kg limit | `index.php`, `book.php` |
| 3.1 | Captain online/offline toggle and earnings | `captain/dashboard.php` |
| 5.5 | 20% commission, 80% captain payout | `book.php`, `admin/dashboard.php`, `captain/dashboard.php` |
| 5.5 | Captain KYC verification queue | `admin/dashboard.php`, `admin/update_status.php` |
| 2.2 | Post-trip rating and feedback | `confirmation.php` |
| 1.x | Customer sign-up and login, admin and captain authentication | `signin.php`, `login.php`, `captain/login.php`, `admin/` |

---

## Notes

- Customer pages (`index.php`, `confirmation.php`) require login. Captain and admin pages require their own login.
- Delete `setup_admin.php` after setup. Do not leave it on a live server.
