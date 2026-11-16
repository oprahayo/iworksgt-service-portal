# How I Built a Multi-Role Business Operations Platform with PHP and MySQL

**Author:** Afolayan Opeyemi Ayomikun-Oprah  
**Published:** 16 November 2026  
**Role:** Full-Stack Software Engineer and Technical Founder  
**Project:** iWorks Global Service Operations Platform  
**Stack:** PHP 8+, MySQL/MariaDB, PDO, HTML, CSS, JavaScript, Apache, third-party payment APIs

> A technical case study about the architecture, engineering decisions, challenges, and ongoing improvements behind a platform connecting customers, payments, service agents, finance teams, and administrators.

## Introduction

Over the years, I have worked on web development and digital infrastructure projects. One recurring problem is how businesses use disconnected tools to manage operations: a customer makes a request on a website, receives an invoice elsewhere, sends documents by email or WhatsApp, and then has to ask repeatedly for progress updates.

As a software engineer and founder of **iWORKS GLOBAL LTD (Nigeria)** and **Orange Server LLC (USA)**, I wanted to build a more coordinated way of operating.

I designed and developed the **iWorks Global Service Operations Platform**, a web application connecting customer onboarding, service ordering, payments, work assignment, document sharing, progress tracking, and administration. It is an actively developed product; this case study describes the implementation and its trade-offs, not a claim that every operational or security concern has been fully solved.

- **Website:** https://www.iworksgt.com
- **Application:** https://portal.iworksgt.com
- **Repository:** https://github.com/oprahayo/iworksgt-service-portal

## 1. Begin with the business workflow

The most important early decision was to model a service as a lifecycle rather than an isolated form submission. A customer request needs ownership, financial state, requirements, communication, delivery records, and potentially a commission.

The application's principal roles are:

| Role | Responsibilities |
| --- | --- |
| Customer | Order services, submit requirements, track progress, communicate, and access deliverables |
| Agent | Work on assigned or eligible paid jobs, provide updates, and submit delivery documents |
| Administrator | Manage services, assignment, users, content, and operational workflows |
| Finance | Review relevant payment, invoicing, commission, and withdrawal records |

These users participate in the same operational process, but they should not have the same permissions.

The main workflow is:

```text
Customer discovers a service
           |
           v
Order and invoice created
           |
           v
Payment verification and confirmation
           |
           v
Paid job enters available pool / admin assigns an owner
           |
           v
Agent processes work, communicates, uploads documents
           |
           v
Delivery recorded and client can review the result
           |
           v
Eligible agent commission is accrued
```

The purpose of this model is to keep each step traceable and give users a consistent place to find the next action.

**Relevant implementation:** [Job and domain logic](../app/domain.php), [portal routes and actions](../index.php), [job workspace](../views/job.php).

## 2. Technology and architectural choices

I built the application with **PHP, PDO, and MySQL/MariaDB**, using server-rendered HTML templates and JavaScript for interactive behaviour.

The core organization separates application concerns:

```text
index.php             Request routing and workflow actions
app/auth.php          Authentication and authorization
app/domain.php        Jobs, services, commissions, business rules
app/order_intent.php  Preserving customer order intent across sign-in
app/db.php            Database access
app/gateway_verification.php  Payment verification checks
app/notification_outbox.php  Notification email queue
views/                Customer, agent, finance, admin interfaces
tests/                Focused regression and smoke checks
```

This was a deliberate trade-off. A modular PHP application fits existing Apache/cPanel infrastructure, avoids unnecessary deployment complexity for the current operating context, and still makes it possible to separate business rules from presentation.

The approach does not eliminate the need for rigorous boundaries. As the application expands, careful module design, database migrations, automated tests, and security reviews become more important.

**Relevant implementation:** [authentication](../app/auth.php), [database abstraction](../app/db.php), [order intent](../app/order_intent.php), [README setup instructions](../README.md#local-setup).

## 3. Multi-role authorization must be enforced by the backend

One challenging area was ensuring that job visibility matched operational responsibility.

A user interface can hide a button, but hiding a button cannot protect the underlying route. A user might still try to open a record by changing a URL.

In the current job-access policy, an agent can access an eligible paid job if they are its assigned agent, or if the job is unassigned and available in the pool. Private jobs and unpaid work should not become accessible simply because someone knows their identifiers.

Administrative and financial actions are also separated from agent delivery actions. For example, the agent's permitted status updates differ from an administrator's.

The broader principle is simple:

> Authorization is a domain rule that must be checked at the point where protected data or an action is accessed.

**Relevant implementation:** [`can_access_job()` and assignment rules](../app/domain.php), [route-level guards](../index.php), [agent workspace controls](../views/job.php).

## 4. From payments to work allocation

Connecting multiple payment methods increases complexity because the payment provider, customer, invoice, and job must agree about what has been paid.

The application contains integrations and workflows involving **Paystack, Flutterwave, Stripe, bank transfer, and configured digital payment options**. Rather than treating a browser redirect or a submitted amount as sufficient proof, the code includes provider-verification checks that compare verified payment information with the stored transaction's immutable details, including reference, amount, currency, and related records.

A key workflow rule is that an agent cannot be assigned to a job before the job's payment has been confirmed.

This is an important boundary, but it should not be confused with a claim of complete financial correctness. Reconciliation, race conditions, retries, webhook handling, and unusual provider responses need continuing tests and operational scrutiny.

**Relevant implementation:** [gateway verification](../app/gateway_verification.php), [job assignment and finance logic](../app/domain.php).

## 5. Building a remote agent workspace

The agent dashboard supports an operational model where service delivery can happen remotely without granting every worker full administrative access.

The implementation includes:

- A view of eligible paid jobs and the agent's own assignments.
- A **claim job** action for available work, or direct assignment by administration.
- Job discussions, documents, intake information, and a status history.
- Team messaging and notifications.
- Commission records associated with eligible paid, delivered work.
- Withdrawal requests subject to configurable thresholds and intervals.

The lifecycle is more meaningful than simply displaying an earnings number. The commission accrual logic connects earnings to actual job delivery, while financial staff maintain separate controls.

This design also raises future access-control questions: different operational responsibilities may warrant more granular permissions than a single all-purpose agent role.

**Relevant implementation:** [agent dashboard](../views/dashboard.php), [agent management](../views/agents.php), [job view](../views/job.php), [withdrawal interface](../views/withdrawals.php), [commission and withdrawal rules](../app/domain.php).

## 6. Security and data handling

The platform handles customer accounts, business documents, payment references, and operational communication. Security therefore has to be considered in the application architecture.

The repository contains mechanisms for:

- Session authentication and role checks.
- Email OTP, account verification, and recovery workflows.
- CSRF validation on state-changing forms.
- Server-side job/file authorization.
- Protected upload handling and private configuration.

These controls provide a foundation, but the presence of security mechanisms does **not** establish that a production deployment has passed a comprehensive security audit. Authentication edge cases, file permissions, operational logging, and payment integrity still require ongoing review.

**Relevant implementation:** [authentication](../app/auth.php), [file access policy](../app/file_access.php), [security reporting](../SECURITY.md).

## 7. Engineering for the people who use the system

Software architecture has to support a real user journey.

For customers, that includes selecting services, completing onboarding, submitting documents, paying, and tracking progress. For agents, it means knowing which job to work on, what information is needed, and how to communicate progress. For administrators, it means maintaining a coherent operational view.

I also worked on responsive layouts, management interfaces, notifications, content editing, and deployment scripts. These details matter because a working backend is not enough when users must carry out business processes every day.

**Relevant implementation:** [customer dashboard](../views/client-dashboard.php), [operations dashboard](../views/dashboard.php), [notifications](../app/notification_outbox.php).

## 8. What building this taught me

**Model the domain before polishing the interface.** The relationships between orders, jobs, invoices, payments, roles, and commissions shape almost every later feature.

**Make security rules explicit.** Multi-role applications need server-side authorization and defensive handling of sensitive files.

**Payment state deserves special care.** Initiating checkout is different from confirming a payment. A visually successful journey cannot substitute for verified records.

**Choose infrastructure for the actual constraints.** Simpler deployment can be an advantage, provided the code remains maintainable and testable.

**Treat production as an ongoing engineering responsibility.** Real customer workflows expose cases that demos do not. Testing, observability, reconciliation, and continuous improvement matter as much as feature development.

## 9. What's next

The platform remains under active development. My priorities include strengthening test coverage, improving reliability and security, tightening workflow permissions, and making the agent and customer experience clearer.

I also want to share more of the technical decisions behind building real business systems, including what worked, what needed revision, and how design choices changed as requirements evolved.

If you're building an application with multiple user roles, paid workflows, or remote operations, I'd be interested in your perspective:

**Which engineering challenge has been hardest for you: authorization, payment consistency, workflow design, or maintainability?**

---

### About the author

**Afolayan Opeyemi Ayomikun-Oprah** is a full-stack software engineer and technology entrepreneur, and founder of **iWORKS GLOBAL LTD** and **Orange Server LLC**. His work spans business applications, backend services, integrations, hosting infrastructure, and digital product engineering.

**Connect:** [GitHub](https://github.com/oprahayo) · [DEV Community](https://dev.to/oprahayo) · [iWorks Global](https://www.iworksgt.com)

*This document explains engineering work and technical decisions. It does not claim audited security compliance, independently verified scalability benchmarks, user-growth figures, or financial results.*
