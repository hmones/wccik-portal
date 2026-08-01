# WCCIK Membership Website — Requirements Implementation Phases

Source of truth: WCCIK user journey and the three WCCIK PDF forms shared for this task. Do not add extra requirements unless WCCIK confirms them.

---

## Phase 1 — Core Forms, Workflows, UX/UI, Admin Review, Approvals, and Manual Payments

### Goal
Build the first working version of the website so WCCIK can collect member information, let users download the required forms, and let admins review applications, approve or reject them, record payment verification, and generate membership IDs.

### Public Website Navigation
✅Add a **Membership** two cards containing:
- **New Member**
- **Renew Membership**

### New Member Flow
✅ When the user selects **New Member**, show an online form that collects the required information from Annex 1:
- Authorized representative name: required
- Company/firm name
- Company classification/status: Proprietorship, Partnership, Private Ltd. Co., Public Ltd. Co., AOP
- Email: required
- Address: required
- District: required
- NTN question:
  - “Do you have NTN?” with Yes/No dropdown
  - If Yes: enter NTN number
  - If No: enter reason
- Authorized representative CNIC: required
- Phone/office number
- Cell number: required
- WhatsApp number: required

After submission:
- Save the submitted data in the system.
- Show or send instructions that the user must download the form, collect the required documents, and submit the physical form and documents within 10 days.
- Send an email confirming that the online information was submitted and that the user has 10 days to submit the physical form, documents, and pictures to the designated office.

The UI must also provide a downloadable membership form PDF because WCCIK notes that many members are not tech savvy and hard copies are required for signatures and stamps.

### Renewal Flow
When the user selects **Renew Membership**, show an online renewal form that collects Annex 2 information:
- Authorized representative name: required
- Email
- WCCIK membership number
- Company/firm name
- Address
- Authorized representative CNIC: required
- Company NTN: required
- Phone/office number
- Cell number: required
- WhatsApp number: required
- Option to download renewal form
- Option to upload proof of payment

After submission:
- Save the submitted data in the system.
- Send an email asking the member to submit payment, documents, and form.
- The suggested deadline is 10 days.

### Required Documents Checklist
Show the required documents checklist for both new membership and renewal:
1. Copy of CNIC of authorized member
2. Two passport-size photographs
3. Company profile
4. Proof of latest filing of Income Tax return
5. NTN Certificate
6. Membership fee proof of payment
7. Specimen signature document with official signatures and company stamp

The specimen signature card must include company/firm name, address, membership number, corporate/associate status, recent photograph, authorized representative details, CNIC, status, signature with official stamp, date, and partner/director names, roles/designations, and signatures.

### Admin Panel
Build an admin panel with queues for:
- New member applications
- Renewal applications
- Applications waiting for physical documents
- Applications waiting for payment
- Applications ready for approval
- Approved applications
- Rejected applications

For each application, admins must be able to:
- View submitted online form data.
- Record whether the physical form was received.
- Record whether all required documents were received.
- Upload or attach scanned hard copies after receiving them.
- Approve membership.
- Reject membership and enter the reason.
- Send a fixed-format rejection email with the reason.
- Mark an approved member as eligible for payment.
- Add payment receipt details manually for cheque/pay order.
- Verify online bank transfer proof of payment manually.
- Enter or update payment dates manually, including backdated dates.
- Generate or assign a unique WCCIK membership ID after approval and verified payment.
- Update member information when needed.

### Payment Handling in Phase 1
Payment handling is manual in this phase. The website/admin panel must support the payment methods listed by WCCIK:
- Cheque
- Pay order
- Online bank transfer

For approved new members, send payment details by email, including payment methods and account details.

For online bank transfer:
- The user emails payment proof.
- The email subject should include authorized member name, CNIC number, payment proof, and company/firm name.

For cheque or pay order:
- Admin verifies payment receipt.
- Admin records name, CNIC, company name, and date of receipt.

### Membership Completion
For new members:
- Once the form is approved and payment is verified, the system generates a unique WCCIK membership ID.
- Send two emails:
  1. Congratulations, membership is approved, including membership ID.
  2. Membership certificate will be available for collection in 5 days.

For renewals:
- Once payment, documents, and forms are received, approve renewal.
- Send two emails:
  1. Congratulations on membership renewal.
  2. Renewed membership certificate will be available for collection in 5 days.

---

## Phase 2 — Email Automation, Deadlines, Holidays, Lifecycle Rules, Automatic Payment Handling, and Verification Workflows

### Goal
Automate the timing, reminders, lifecycle rules, payment follow-up, holiday-aware deadlines, and verification workflows described in the WCCIK user journey.

### New Member Deadline Automation
After a new member submits the online information:
- Start a 10-day deadline for submitting the physical form, required documents, and pictures.
- On the 11th day, show a pop-up or notification to admins for follow-up of documents and form.
- If documents and form were received:
  - Admin sends an email thanking the user for submitting the documents.
  - The email tells the user that the membership certificate will be ready for collection upon payment of the fee within 7 days.
- If documents and form were not received:
  - Send reminder email.
  - Mark the application for reminder call from the designated office.

### Renewal Reminder Automation
From January:
- Send renewal reminder emails to members every 15 days.
- Include a link for downloading the renewal form.

For renewals:
- Start a suggested 10-day deadline after renewal form submission.
- Send payment details and payment method by email.
- If payment and documents are received: mark as approved for renewal.
- If payment is not received: send reminder email stating that membership will be annulled on 31st March.
- If payment is received but documents/form are not received: send reminder email.
- If payment is not received before 31st March, membership is annulled.

### Membership Lifecycle Rules
Implement the following lifecycle rules:
- WCCIK membership is granted for one year from April to March.
- Membership expires on 31st March.
- Renewal payment must be received before 31st March, otherwise the membership is annulled.
- Annual subscription payments must be made by March 31st to prevent membership from ceasing.
- Completing and submitting the Data Updation Form is mandatory for renewal.
- Applicant must submit a complete Income Tax Return under Section 114 of the Income Tax Ordinance 2001.
- Members with missing or old cards must provide a fresh specimen card with a recent photo.
- The paid “KCCI Copy” must be submitted back to the KCCI Membership Department to finalize renewal.

### Holiday and Working-Day Logic
The system must allow admins to add holidays such as Eid, Labor Day, and other holidays.
Deadline calculations must exclude:
- Sundays
- Admin-defined holidays

Use this working-day logic for all deadline calculations mentioned in the user journey, including:
- 10-day submission deadlines
- 11th-day admin follow-up
- 5-day certificate collection timing
- 7-day payment-related certificate readiness timing

### Payment Verification Automation
The system should support automatic tracking states around payment, while keeping admin verification control:
- Waiting for payment details sent
- Waiting for payment proof
- Payment proof received
- Cheque/pay order received
- Payment verified
- Payment rejected or needs correction, if admin marks it so
- Ready for membership ID generation or renewal completion

For online bank transfer:
- Store uploaded proof of payment for renewals.
- For new members, support recording the payment proof that arrives by email.
- Store expected subject information: member name, CNIC, payment proof, company/firm name.

For cheque/pay order:
- Admin records payment receipt manually.
- Admin must be able to enter actual/realization payment dates, including backdated dates.

### Verification Workflows
Add structured admin verification steps:
1. Verify submitted online form information.
2. Verify physical form received.
3. Verify required documents received.
4. Verify scanned hard copies uploaded or attached.
5. Verify payment proof or receipt.
6. Approve or reject membership/renewal.
7. Generate membership ID for approved new members after verified payment.
8. Send final certificate collection emails.

Admins must also have rights to update credentials and any information when required.

### Email Templates Needed
Create configurable email templates for:
- New member online submission confirmation and 10-day physical submission instruction.
- New member document/form received confirmation and payment/certificate instruction.
- New member missing documents reminder.
- New member rejection with reason.
- New member approval with membership ID.
- New member certificate collection in 5 days.
- Renewal reminders every 15 days from January.
- Renewal submission confirmation asking for payment and documents.
- Renewal payment missing reminder with 31st March annulment warning.
- Renewal documents/form missing reminder.
- Renewal congratulations email.
- Renewal certificate collection in 5 days.

### Important Implementation Boundaries
Do not remove the hard-copy process in Phase 2. The documents require signatures and official stamps, and WCCIK explicitly requires hard copies. Automation should support the manual process, not replace it.
