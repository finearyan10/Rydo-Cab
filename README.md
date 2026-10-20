# ⚡ RYDO — Online Cab, Bike Taxi & Parcel Delivery Platform

**Mini-Project-I (Course Code: UCBIL0371)**  
*Department of Computer Science and Business Systems*  
**Kolhapur Institute of Technology's College of Engineering (KITCoEK), Autonomous**  
*Academic Year: 2026–27 | Semester: III*

**Project Team Members:**
1. Aryan Chougule (Roll No: 06, PRN: 2526UCB190)
2. Sainath Chavan (Roll No: 05, PRN: 2526UCB176)
3. Sumeet Keroor (Roll No: 25, PRN: 2526UCB184)
4. Varad Akolkar (Roll No: 02, PRN: 2526UCB200)
5. Atif Gulbarga (Roll No: 34, PRN: 2526UCB199)

**Project Guide:** Prof. Sheetal Gaikwad

---

## 📖 Project Overview (SRS Compliance)

**RYDO** is an on-demand, app-based micro-mobility bike taxi, cab, and intra-city logistics platform. As defined in the IEEE 830-compliant SRS Report, RYDO connects passengers and package senders with nearby certified micro-mobility captains.

The platform provides three core sub-systems:
1. **Rider Web App (`index.php`, `confirmation.php`):**
   - Toggle between **Bike Taxi / Cabs** and **RYDO Parcel (Logistics Delivery ≤ 5kg)**.
   - Fare estimation with transparent route distance formulas.
   - **4-Digit Security OTP PIN** generated upon booking.
   - **Simulated Live GPS Tracking** & route movement.
   - **1-Tap Emergency Safety SOS** alert broadcast.
   - Post-trip rating and feedback system.

2. **Captain Partner Portal (`captain/dashboard.php`):**
   - Duty toggle: **🟢 Online / ⚪ Offline**.
   - Review incoming trip requests with pickup distance and estimated net earnings.
   - **OTP Verification to "Start Trip" (SRS Section 4.2):** Captain must enter the rider's 4-digit PIN to begin the ride.
   - Trip completion & delivery confirmation.
   - Captain Net Earnings (80% net payout after 20% platform commission).

3. **Admin Central Console (`admin/`):**
   - Operational Analytics: Active trips, online captains, gross GMV, and **RYDO 20% commission revenue**.
   - **Captain KYC Verification Queue (SRS Section 5.5):** Review and **Approve / Reject** Driving Licenses and Vehicle Registration.
   - Live Trip & Parcel monitoring with real-time status controls.

---

## 📁 System Architecture & Directory Structure

```
OnlineCabBooking/
├── index.php              ← Rider booking portal (Bike, Auto, Cab & RYDO Parcel)
├── book.php               ← Booking processor, OTP generator & captain matcher
├── confirmation.php       ← Live trip tracking, 4-digit OTP PIN & Safety SOS
├── setup_admin.php        ← One-time password setup helper
├── database.sql           ← MySQL schema, seed captains & sample trips
├── README.md              ← Project documentation
│
├── config/
│   ├── db.php             ← Resilient PDO connection (rydo_db / cab_booking)
│   ├── routes.php         ← Kolhapur corridor route distances (km)
│   └── fares.php          ← Per-km pricing & capacity definitions
│
├── captain/
│   └── dashboard.php      ← Captain Partner App (Online toggle, PIN verify, Earnings)
│
├── admin/
│   ├── login.php          ← Secure admin authentication
│   ├── dashboard.php      ← Operations dashboard & Captain KYC verification queue
│   ├── update_status.php  ← Status change & KYC approval handler
│   └── logout.php         ← Session invalidation
│
└── assets/
    ├── css/
    │   ├── style.css      ← Modern electric neon dark theme & animations
    │   └── admin.css      ← Dashboard tables, filters & stat cards
    └── js/
        └── main.js        ← Fare calculator, service switcher, validation
```

---

## 🚀 Quick Setup Instructions (XAMPP on Windows)

### 1. Place Project in XAMPP
Copy the `OnlineCabBooking` folder into your local XAMPP `htdocs`:
```
C:\xampp\htdocs\OnlineCabBooking\
```

### 2. Start Services
Open the **XAMPP Control Panel** and click **Start** for:
- **Apache**
- **MySQL**

### 3. Import Database
1. Open browser: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click the **"Import"** tab.
3. Choose `database.sql` from the project folder.
4. Click **"Go"** at the bottom.
   *(This creates `rydo_db` with seed captains, admin account, and sample rides).*

### 4. Admin Credentials
- **Admin URL:** [http://localhost/OnlineCabBooking/admin/login.php](http://localhost/OnlineCabBooking/admin/login.php)
- **Username:** `admin`
- **Password:** `Admin@1234`
*(If you need to re-sync the password hash, open [http://localhost/OnlineCabBooking/setup_admin.php](http://localhost/OnlineCabBooking/setup_admin.php)).*

### 5. Open Web Applications
- **Rider Portal:** [http://localhost/OnlineCabBooking/](http://localhost/OnlineCabBooking/)
- **Captain App:** [http://localhost/OnlineCabBooking/captain/dashboard.php](http://localhost/OnlineCabBooking/captain/dashboard.php)
- **Admin Console:** [http://localhost/OnlineCabBooking/admin/dashboard.php](http://localhost/OnlineCabBooking/admin/dashboard.php)

---

## 🚖 Fare Structure & Corridors (Kolhapur Focus)

| Service | Base Fare (First 2 km) | Per Additional KM | Key Characteristics |
|---|---|---|---|
| **RYDO Bike Taxi** | ₹20 | ₹8 / km | Micro-mobility, helmet verified |
| **RYDO Auto** | ₹30 | ₹12 / km | 3-seater local transit |
| **RYDO Prime Cab** | ₹50 | ₹16 / km | Air-conditioned 4-seater |
| **RYDO Parcel** | ₹25 | ₹10 / km | Intra-city delivery (≤ 5kg package) |

*Key corridors included: KIT College of Engineering, Central Bus Stand, Railway Station, Mahalaxmi Temple, Rankala Lake, DYP City Mall, Shivaji University, CPR Hospital, and Shiroli MIDC.*

---

## 🛡️ SRS Feature Mapping Checklist

| SRS Section | Feature Requirement | Implementation File | Status |
|---|---|---|---|
| **1.4 & 4.1** | Bike Taxi & Intra-city Parcel (RYDO Parcel) | `index.php`, `book.php` | ✅ Fully Implemented |
| **4.2** | 4-Digit OTP PIN verification before "Start Trip" | `confirmation.php`, `captain/dashboard.php` | ✅ Fully Implemented |
| **4.3** | Live GPS Tracking & Emergency Safety SOS | `confirmation.php` | ✅ Fully Implemented |
| **4.4** | Parcel Receiver details & max 5kg limit | `index.php`, `book.php`, `confirmation.php` | ✅ Fully Implemented |
| **3.1** | Captain Online/Offline duty toggle & earnings | `captain/dashboard.php` | ✅ Fully Implemented |
| **5.5** | 20% Platform Commission / 80% Captain Payout | `book.php`, `admin/dashboard.php`, `captain/dashboard.php` | ✅ Fully Implemented |
| **5.5** | Captain KYC Verification Queue (License/RC approval) | `admin/dashboard.php`, `admin/update_status.php` | ✅ Fully Implemented |
| **2.2** | Post-trip rating and feedback system | `confirmation.php` | ✅ Fully Implemented |
