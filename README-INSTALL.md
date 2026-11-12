# WebRajya ERP — Quick Installation Guide for CA Firms

Welcome to **WebRajya ERP**, unified practice management software built exclusively for Chartered Accountants and tax practitioners.

---

## 💻 System Requirements

- **Operating System:** Windows 10/11 (64-bit), macOS 12+, or Ubuntu Linux
- **RAM:** Minimum 4GB (8GB recommended)
- **Disk Space:** 5GB free storage

---

## 🚀 3-Step Installation (Takes < 5 minutes)

### Step 1: Install Docker Desktop (One-Time Setup)
1. Download **Docker Desktop** (free) from [https://www.docker.com/products/docker-desktop/](https://www.docker.com/products/docker-desktop/)
2. Follow standard installer steps and restart your computer if prompted.
3. Open Docker Desktop from your Start menu / Applications.

### Step 2: Run WebRajya ERP Setup
- **Windows Users:** Double-click `install.bat`
- **Mac/Linux Users:** Open terminal in this folder and run `./install.sh`

*(The installer will automatically set up the database, create secure storage, and configure the system).*

### Step 3: Activate Your License
Your browser will open automatically at:
👉 **http://localhost:8000/license/activate**

1. Enter your **License Key** (provided in your purchase email).
2. Click **Activate WebRajya ERP**.
3. Done! You can now log in and manage your clients, filings, audits, and billing.

---

## 🔑 Default Sign-In Accounts (First Run)

| Role | Email | Password |
|---|---|---|
| **Super Admin / Partner** | `admin@webrajya.in` | `password` |
| **Manager** | `manager@webrajya.in` | `password` |
| **Accountant / Staff** | `employee@webrajya.in` | `password` |

*(You can change all passwords and emails anytime from **Settings**).*

---

## 💾 Daily Usage & Backups

- **To Open WebRajya ERP:** Double-click `start.bat` (or visit `http://localhost:8000`).
- **To Stop WebRajya ERP:** Double-click `stop.bat`.
- **To Take a Full Database Backup:** Double-click `backup.bat` — a complete `.sql` backup file will be created in the `backups/` folder.

---

## 📞 Support & License Inquiries

- **Email:** `support@webrajya.in`
- **Website:** `https://webrajya.in`
