# SPEC-SAAS-009: Governance and User Management Refinements

## 1. Objective

Optimize the user and company management workflow, ensuring data integrity during user provisioning, secure credential management, and accurate tenant association within the SaaS architecture.

---

## 2. Functional Requirements

### 2.1. Access Management

#### Access Removal

The company selector displayed in the **Remove Company Access** workflow must list only the companies currently associated with the selected user.

The list must be populated using a query filtered by the selected user's identifier.

Example:

```sql
SELECT company_id
FROM company_user
WHERE user_id = :userId;
```

#### Acceptance Rules

* Only companies associated with the selected user must be displayed.
* Companies without an existing relationship must never appear.
* Access revocation must immediately remove the association from the `company_user` table.

---

### 2.2. Initial Company Association

#### Current Issue

New users are currently being associated with an additional default tenant ("SwiftlyPark") through hardcoded logic.

This behavior violates the SaaS tenant isolation model.

#### Expected Behavior

During user creation, the system must associate the user exclusively with the company selected in the request.

The service responsible for user provisioning must remove any hardcoded tenant assignment.

#### Acceptance Rules

* Only the selected `company_id` must be inserted into `company_user`.
* No additional company associations may be created automatically.
* Additional company access must only be granted through the Governance module.

---

### 2.3. User Input Validation

#### Input Sanitization

All textual input must be sanitized using `trim()` before validation.

#### Name Validation Rules

The **Name** field must satisfy the following constraints:

* Minimum length of **2 characters** after trimming.
* Strings containing only whitespace must be rejected.
* Invalid names composed exclusively of special characters must be rejected.

Example:

```text
❌ " "
❌ "  "
❌ "@@@"
❌ "A"

✅ "Jo"
✅ "Maria"
```

Validation failures must return:

```http
HTTP 422 - Unprocessable Entity
```

---

### 2.4. Automatic Credential Provisioning

#### Password Removal

The `password` field must be removed from the `CreateUserAdmin` form.

Administrators should not manually define user passwords.

#### Temporary Password Generation

A dedicated `PasswordGeneratorService` must generate a secure temporary password.

Requirements:

* Minimum 8 characters;
* Alphanumeric;
* Randomly generated.

#### Notification Workflow

After successful user creation:

1. Generate a temporary password;
2. Persist the hashed password;
3. Dispatch a `UserCreatedEvent`;
4. Send an email containing the temporary credentials through the configured `EmailService`.

---

### 2.5. User Reactivation Workflow

#### Availability

The **Reactivate** action must only be available when:

```text
user.status == "inactive"
```

#### Reactivation Flow

When reactivating a user, the system must:

1. Change the user status to `active`;
2. Generate a new temporary password;
3. Persist the new password hash;
4. Send a reactivation email containing the new temporary password;
5. Record the action in the audit log.

#### Audit Log

The audit entry must follow the pattern:

```text
[Audit] User [UserID] reactivated by administrator [AdminID]
```

---

## 3. Technical Considerations

### Services

* `UserProvisioningService`
* `PasswordGeneratorService`
* `EmailService`
* `AuditLogService`

### Events

* `UserCreatedEvent`
* `UserReactivatedEvent`

### Database Tables

* `users`
* `company_user`
* `audit_logs`

---

## 4. Acceptance Criteria

| ID | Feature             | Acceptance Criteria                                                                            |
| -- | ------------------- | ---------------------------------------------------------------------------------------------- |
| 01 | Access Removal      | The company dropdown displays only companies associated with the selected user.                |
| 02 | Name Validation     | Names containing fewer than 2 characters or only whitespace are rejected with **HTTP 422**.    |
| 03 | Automatic Password  | The password field is removed from the UI and temporary credentials are delivered by email.    |
| 04 | User Reactivation   | Inactive users can be reactivated and automatically receive a new temporary password by email. |
| 05 | SaaS Tenant Mapping | Newly created users are associated exclusively with the selected company.                      |

---

## 5. Expected Outcome

After implementing this specification, the Governance module will provide:

* Accurate company-user association management;
* Secure automatic credential provisioning;
* Consistent tenant isolation;
* Improved user lifecycle management;
* Complete auditability for user activation and access management;
* Stronger validation rules during user registration.
