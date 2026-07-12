# SPEC-SAAS-010: Global Control Dashboard (Super-Admin Home)

## 1. Objective

Transform the current **Home** experience for **Super Administrators** from an operational interface into a global governance dashboard.

The objective is to clearly separate the responsibilities of the **Platform Administrator (Super-Admin)** from those of **Company Administrators** and **Operational Users**, providing a centralized view of the entire SaaS platform while preserving tenant isolation and security.

---

# 2. Functional Requirements

## 2.1. Intelligent Home Routing

### Overview

The application must determine the user's role immediately after authentication and redirect them to the appropriate home interface.

### Routing Rules

| User Role     | Default Route            |
| ------------- | ------------------------ |
| Super-Admin   | `/admin/dashboard`       |
| Company Admin | `/operational/dashboard` |
| Operator      | `/operational/dashboard` |

### Expected Flow

```text
Login
    │
    ▼
Read authenticated user role
    │
    ├── SUPER_ADMIN
    │       ▼
    │   /admin/dashboard
    │
    └── ADMIN / OPERATOR
            ▼
    /operational/dashboard
```

---

## 2.2. Global Dashboard (Super-Admin)

The Super-Admin Home must provide a consolidated cross-tenant view of the entire platform.

Operational features such as quick check-in must not be displayed.

### KPI Cards

The dashboard should display platform-wide indicators, including:

* Total active companies;
* Total registered users;
* Total active parking sessions;
* Total completed check-ins;
* Platform-wide revenue;
* Active investigations (if applicable);
* Monthly platform growth.

---

### Cross-Tenant Analytics

Provide consolidated metrics across every tenant.

Examples:

* Total operational volume;
* Daily activity;
* Monthly growth;
* Revenue by company;
* Active versus inactive companies.

---

### Security Monitoring

A dedicated panel must display the latest security events affecting the SaaS platform.

Examples:

* Cross-tenant access attempts;
* Permission escalation attempts;
* Authentication failures;
* Suspicious activities;
* Critical audit events.

Only the most recent five critical events should be displayed by default.

---

### System Health

Provide operational health indicators such as:

* Top 5 companies by usage;
* Companies with the highest operational volume;
* Companies approaching plan limits;
* Platform availability indicators;
* Overall system status.

---

## 2.3. Tenant Switcher

### Overview

The Super-Admin dashboard must include a **Tenant Switcher** allowing administrators to temporarily enter the operational context of any company.

This feature is intended exclusively for:

* Technical support;
* Auditing;
* Troubleshooting;
* Administrative assistance.

### Expected Behavior

The selector must:

* List all active companies;
* Allow searching by company name;
* Redirect the Super-Admin to the selected company's operational environment;
* Replace the current tenant context.

---

## 2.4. Secure Impersonation Mode

### Overview

Whenever a Super-Admin enters a company's operational environment using the Tenant Switcher, the system must explicitly indicate that the administrator is operating in an impersonated context.

### Warning Banner

A persistent banner must be displayed at the top of every page.

Example:

```text
⚠️ You are operating as a Super Administrator within the company:

Acme Parking Ltd.

All actions performed in this session will be audited.
```

### Audit Requirements

Every operation executed while impersonating another tenant must be recorded.

Audit information should include:

* Super Administrator ID;
* Target Company ID;
* Action performed;
* Timestamp;
* IP Address;
* User Agent.

---

# 3. Dashboard Components

| Component              | Audience                 | Purpose                                                                |
| ---------------------- | ------------------------ | ---------------------------------------------------------------------- |
| KPI Cards              | Super-Admin              | Display overall SaaS platform health.                                  |
| Cross-Tenant Analytics | Super-Admin              | Consolidated operational metrics across all tenants.                   |
| Security Alerts        | Super-Admin              | Display recent security and audit events.                              |
| System Health          | Super-Admin              | Monitor platform usage and operational health.                         |
| Tenant Switcher        | Super-Admin              | Access a company's operational environment for support or auditing.    |
| Quick Check-In         | Company Admin / Operator | Daily parking operations (not available on the Super-Admin dashboard). |

---

# 4. Security Requirements

## Tenant Isolation

The Global Dashboard may aggregate platform metrics, but operational data must remain isolated by tenant.

Only Super-Administrators are authorized to access cross-tenant information.

---

## Auditability

The following events must be recorded:

* Dashboard access;
* Tenant switching;
* Impersonation start;
* Impersonation end;
* Administrative actions executed during impersonation.

---

## Permission Validation

Only users with the `SUPER_ADMIN` role may:

* Access `/admin/dashboard`;
* View global KPIs;
* Access security monitoring;
* Switch between tenants;
* Operate in impersonation mode.

---

# 5. Acceptance Criteria

| ID | Feature             | Acceptance Criteria                                                                                                         |
| -- | ------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| 01 | Intelligent Routing | Super-Admins are redirected to `/admin/dashboard`; Company Admins and Operators are redirected to `/operational/dashboard`. |
| 02 | Global KPIs         | The dashboard displays consolidated platform metrics across all tenants.                                                    |
| 03 | Security Monitoring | The latest cross-tenant security events are visible to Super-Admins.                                                        |
| 04 | Tenant Switcher     | Super-Admins can switch to any company's operational environment.                                                           |
| 05 | Impersonation Mode  | A persistent warning banner is displayed while operating within another tenant.                                             |
| 06 | Audit Trail         | All impersonation actions are fully recorded in the audit log.                                                              |

---

# 6. Expected Outcome

Upon completion of this specification, the application will provide two clearly separated experiences:

* **Operational Dashboard**, focused on day-to-day parking management for Company Administrators and Operators.
* **Global Governance Dashboard**, dedicated to Super-Administrators, providing platform-wide analytics, security monitoring, tenant management, and auditing capabilities.

This separation reinforces the SaaS multi-tenant architecture by clearly distinguishing operational workflows from platform governance while maintaining strict tenant isolation and complete auditability.
