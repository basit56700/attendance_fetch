# 🏢 ZK Attendance & HRMS ERP System

An industry-grade, scalable Attendance & HRMS Enterprise ERP application built on **Laravel 13** and **PHP 8.5**. It connects directly to **ZKTeco Biometric Devices** (over Ethernet patch cable or office LAN/Wi-Fi on Port 4370) to fetch, process, and export attendance records in clean **JSON format**, while providing a complete enterprise management suite: **Pre-Add Device Diagnostics with Error Resolution Guides, Multi-Machine Management, Comprehensive Audit Logging, and Full User/Role Management**.

---

## 🌟 Enterprise Highlights

* **🛠️ Pre-Add Machine Diagnostic Sandbox**:
  * Before saving any machine, run a live hardware diagnostic.
  * Measures latency, queries firmware, verifies comm key, and checks device RTC clock drift.
  * **Intelligent Error Resolver**: When a test fails, it provides the **Error Code**, **Root Cause Analysis**, and a **Step-by-Step Resolution Guide** (e.g. Ethernet cable verification, IP subnet alignment, Port 4370 Windows Firewall rules).
* **📄 JSON First-Class Extraction**:
  * Public/Authenticated REST API endpoint: `GET /api/v1/attendance` (supports date ranges, machine, punch state, employee ID).
  * 1-Click downloadable JSON file export: `attendance_export_YYYYMMDD.json`.
  * In-app JSON payload inspector modal with syntax highlighting and copy button.
* **👥 Complete User & Role Management (RBAC)**:
  * Role tiers: **Administrator** (full control), **HR Manager** (staff & rosters), **Operator** (fetch & inspect), **Viewer** (read-only).
  * Account activation/deactivation toggle, password resets, and user deletion guards.
* **👤 Personal Profile Management**:
  * Update full name and login email.
  * Secure password change with current password verification.
  * Personal audit activity timeline.
* **📜 System Audit Trail ("Logs for Everything")**:
  * Comprehensive audit logging tracking: `MACHINE_TEST`, `MACHINE_CREATE`, `MACHINE_FETCH`, `ATTENDANCE_EXPORT_JSON`, `USER_LOGIN`, `USER_CREATE`, `PASSWORD_CHANGE`, `SETTINGS_UPDATE`.
  * Filterable by action type, username, status, or date range, with administrative log pruning.
* **⏰ Background Automation & Scheduler**:
  * Artisan polling command: `php artisan zk:poll` for automated background cron jobs.
  * Automatic punch duplicate suppression using unique indexing: `(attendance_machine_id, erp_id, attendance_time)`.
  * Device RTC clock synchronization.

---

## 🚀 Quick Start (Windows)

### Option 1: 1-Click Launcher (Recommended)
Double-click **`run.bat`** in the project folder.

It will:
1. Initialize database migrations automatically.
2. Launch your default browser to `http://127.0.0.1:8000`.
3. Start the Laravel development server.

### Option 2: Command Line
```powershell
php artisan migrate --force
php artisan serve --port=8000
```
Open **`http://127.0.0.1:8000`** in your browser.

---

## 🔑 Default Administrator Login

* **Email / Login**: `admin@local.com`
* **Password**: `admin123`
*(You can create additional users or change this password in the **User Management** or **My Profile** sections).*

---

## 🔌 Hardware Connection Guide

### 1. Direct Cable Connection (Laptop to ZKTeco Device)
1. Connect an RJ45 Ethernet patch cable from your laptop's LAN port to the ZKTeco device.
2. On your ZKTeco device screen, navigate to: **Menu $\rightarrow$ Comm. $\rightarrow$ Ethernet** (e.g. `192.168.1.201`, Subnet `255.255.255.0`).
3. On your Windows laptop:
   * Open Network Connections (`ncpa.cpl`).
   * Right-click Ethernet adapter $\rightarrow$ Properties $\rightarrow$ IPv4.
   * Set a static IP on the same subnet (e.g., IP: `192.168.1.100`, Subnet: `255.255.255.0`).
4. In the ERP, navigate to **Machines $\rightarrow$ Add & Diagnose**, enter `192.168.1.201` and click **Test Connectivity & Diagnostics**.

### 2. Office Local Network / LAN Switch
1. Plug the ZKTeco device into your office network switch or router.
2. In the ERP, enter the assigned IP address (e.g., `192.168.1.201`), Port `4370`, and click **Test Connectivity & Diagnostics**.

---

## 📡 REST API & JSON Export Specification

### `GET /api/v1/attendance`
Retrieves filtered attendance records formatted as JSON for ERP integrations.

**Parameters (Query String)**:
* `date_from` (YYYY-MM-DD): Filter punches from date.
* `date_to` (YYYY-MM-DD): Filter punches to date.
* `erp_id` (string): Filter by employee code.
* `machine_id` (integer): Filter by machine ID.
* `punch_type` (0=IN, 1=OUT, 2=Break OUT, 3=Break IN).
* `limit` (integer, default 500, max 5000).

#### Response Sample:
```json
{
  "success": true,
  "count": 1,
  "data": [
    {
      "id": 1,
      "erp_id": "10023",
      "attendance_time": "2026-11-04 09:15:00",
      "punch_type": 0,
      "type_remarks": "IN",
      "verification_mode": "Fingerprint",
      "machine_id": 1,
      "machine_name": "Main Office Gate",
      "machine_ip": "192.168.1.201",
      "sync_status": "synced"
    }
  ]
}
```

---

## ⚡ Background Poller Command

To poll all active machines automatically via Windows Task Scheduler or Linux Cron:
```bash
# Poll all active machines
php artisan zk:poll

# Poll a specific machine by ID
php artisan zk:poll 1
```
