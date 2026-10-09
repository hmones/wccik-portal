# WCCIK Membership Portal, Progress Report

Prepared: 9 October 2026  
For: WCCIK Management & GPP  
Subject: Development progress since the 1 August 2026 progress report

---
## Hours Summary

Total hours spent to date: **40 hours** across all contract categories.

| Contract Activity | Hours Spent | Hours Remaining | Expected Total |
|---|-------------|---|---|
| Plan, design, and define overall system architecture and database structure | 20h         | 0h | 20h |
| Develop the public website, including forms, user flows, and UX design | 18h         | 17h | 35h |
| Implement backend development, including core logic and API functionalities | 9h          | 41h | 50h |
| Design and build the administrative panel, including review, approvals, payments, and ID management | 3h          | 57h | 60h |
| **Total** | **40h**     | **125h** | **165h** |

---
## Summary

Since the [1 August progress report](wccik-progress-report-1-aug-2026.md), the portal has progressed from the initial public application form and database foundation to a working applicant journey covering email sign-in, saved drafts, new membership applications, renewals, application tracking, filled PDF downloads, payment proof submission, and administrative review.

The latest work simplifies the administrative process into four actions and separates information approval from payment verification. Membership activates automatically only after the office has received the signed form and supporting documents, the information has been approved, and an uploaded payment receipt with its date and payment method has been verified. The same process applies to new memberships and renewals.

The public interface has also been redesigned, with continued English and Urdu support, a manual light/dark theme switch, and consistent header navigation and sign-out controls.

This report describes the current implementation and local validation. It does not confirm a production launch, live email delivery, or completion of every item in the original requirements.

---

## Work Completed Since the Last Report

### 1. Applicant Sign-in and Membership Linking

A dedicated applicant portal now provides passwordless access through an email verification code:

- Applicants enter their email at `/portal/sign-in` and verify a six-digit code.
- Codes expire after 10 minutes, have a five-attempt limit, and are stored as hashes.
- Request and resend endpoints have rate limits, and issuing a new code invalidates earlier unused codes.
- The applicant session is separate from the administrative login.
- After verification, an applicant can be linked to an existing member whose registered email matches the verified address.
- Sign-out clears the current session and rotates the remember token.

This replaces the need for a separate public status link as the primary way to access an application. Applicants use the authenticated portal to see their progress and next steps.

### 2. Applicant Dashboard and Journey

The dashboard selects the appropriate journey for the signed-in applicant:

| Applicant situation | Portal behavior |
|---|---|
| No membership or pending application | Offer a new membership application |
| Saved draft | Offer to continue the application or renewal |
| Submitted application or renewal | Show progress, document instructions, filled-form download, and payment upload controls |
| Active membership | Show member details, membership number, and expiry date |
| Expired membership | Offer renewal |

For submitted applications, progress is shown separately for the signed physical form, supporting documents, information approval, and payment verification. Uploading a receipt is clearly distinguished from having that receipt verified.

### 3. New Membership Applications and Saved Drafts

The main new-member journey now runs through `/portal/apply` after email verification.

The form has been extended beyond the initial August implementation to include membership class, industry, CNIC expiry, website, establishment year, turnover, employee count, additional contact details, sales tax number, and other chamber memberships, alongside the existing applicant, company, address, and NTN fields.

Draft behavior is implemented:

- Changes are saved automatically, with visible saving, saved, and error states.
- Applicants can return to continue a saved draft.
- Empty autosaves do not create unnecessary application records.
- Final submission validates the required information and records submission and terms-acceptance timestamps.
- The signed physical form and supporting documents remain part of the process; online submission does not replace them.

The previous unauthenticated `/apply` flow remains available for compatibility, but the OTP portal is the main journey.

### 4. Renewal Applications

A renewal form is now implemented at `/portal/renew`:

- The applicant must be linked to an existing member.
- Renewal is currently offered when membership has expired, with an existing draft allowed to be completed.
- The form is pre-filled from the member record and can be edited before submission.
- Renewal drafts use the same autosave behavior as new applications.
- The renewal is linked to the existing membership number.
- Submission, physical document receipt, information review, payment verification, and activation follow the same rules as a new application.
- On activation, the reviewed renewal information is copied to the existing member record and its expiry date is updated.
- The approval email uses the existing member's membership number.

### 5. Filled PDF Downloads and Physical Document Checklist

Applicants and administrators can download a PDF populated with the submitted application information.

- Separate filled PDFs are generated for new membership and renewal applications.
- The submitted-form download appears within the applicant's required-document checklist.
- Instructions explain that the form must be printed, signed, stamped, and delivered to the office with the supporting documents.
- Administrators have a direct link to the filled application from Nova.
- Submission acknowledgement emails include the filled PDF as an attachment.
- Original blank membership, renewal, and specimen signature forms are also available from the public website.

The filled PDFs are generated from HTML templates based on the forms; they are not exact overlays of the original PDF files.

### 6. Payment Proof, Date, and Method

Payment proof can be supplied through three routes:

1. The applicant attaches it when submitting the application or renewal.
2. The applicant uploads it later from the dashboard while the application is pending.
3. The applicant delivers it physically and an administrator uploads the office receipt.

Payment remains optional at initial application submission. Whenever a receipt is uploaded, the payment details are required:

| Required payment field | Supported input |
|---|---|
| Proof of payment | PDF, JPG, or PNG, up to 5 MB |
| Payment date | The actual date payment was made; future dates are rejected |
| Payment method | Cheque, Pay Order, or Online Bank Transfer |

Applicants and administrators use the same payment method choices. Administrators can review and correct the supplied date and method in Nova before verifying payment.

Replacing a receipt or changing its payment date or method clears the previous payment verification. These payment changes preserve information approval. Verification uses the saved payment details rather than replacing the applicant's date with the date of administrative review.

Payment verification remains a manual administrative decision; the website does not process payments or verify bank transactions automatically.

### 7. Simplified Administrative Review and Activation

The Nova application workflow has been reduced to four actions:

| Action | Result |
|---|---|
| **Approve information** | Confirm eligibility after the signed form and supporting documents have been marked received, and choose the membership expiry date |
| **Verify payment** | Verify the attached receipt and its saved payment details |
| **Request payment** | Send the applicant an email asking for valid payment proof and directing them to the portal |
| **Reject application** | Record a rejection reason and send the decision email |

The direct status override and editable payment-verification checkbox have been disabled. The separate actions to manually mark applications ready for approval or awaiting documents have been removed from the workflow.

Information approval and payment verification can happen in either order. Completing the second approval automatically activates the membership when all prerequisites are satisfied:

- Signed physical application received.
- Supporting documents received.
- Information approved.
- Payment receipt uploaded, with payment date and method recorded.
- Payment verified.
- Membership expiry date still in the future.

Only then does the system create or update the member record and send the final approval email with the membership number and expiry date. Information approval alone does not send a final membership approval email; Request payment is a separate administrator action.

The transitions use database transactions and application row locks. Repeated approval does not create another member or resend the final approval email, and stale payment decisions cannot reactivate a rejected application.

### 8. Editing and Review Reset Rules

The editing policy agreed during the workflow review has been implemented:

- Applicants can edit drafts, but application information locks after submission.
- Applicants can still supply or replace payment proof while the application is pending.
- Administrators can correct pending application information.
- An information correction resets information approval, payment verification, and the office document receipt checks. The corrected signed form and documents must be checked again.
- Removing an office receipt check clears information approval.
- Completed applications are protected from further editing through the administrative application form.
- Stale autosaves and repeated submission requests cannot create another pending application for the same applicant.

The portal and Nova display guidance explaining the correction policy.

### 9. Email Notifications and Editable Templates

Database-backed English and Urdu email templates are now editable through Nova. Implemented notifications cover:

- Portal sign-in codes.
- Submission acknowledgement with the filled application PDF.
- Payment proof requests and payment rejection follow-up.
- Final membership approval with membership number and expiry date.
- Application rejection with the administrator's reason.

The payment-request wording has been corrected so that requesting payment does not incorrectly state that information or documents have already been approved.

A new `application_payment_requested` template and migration were added. The migration was applied to the local database after a missing-template error was identified. The existing `application_awaiting_payment` template and any customized content were preserved.

Template subjects and bodies can be edited without changing code. Existing customized subjects and bodies are preserved by the seeder.

### 10. Membership Records and Identification

Dedicated member records now hold the approved member information and membership expiry date. Applicant records link the portal account to the relevant member.

An application receives a protected identifier at creation so that it can be used consistently during processing. Activation creates or links the actual member record. Renewal activation retains the existing membership number.

The current generated identifier format is `WCCIK-YYYY-####`. The implementation checks candidates against applications and members. The final production numbering rule still needs WCCIK confirmation; the current generator uses random candidate numbers rather than an agreed sequential numbering policy.

### 11. Public Website Design, Theme, and Navigation

The public interface has been updated with the navy and teal brand palette, revised landing-page sections, clearer form layouts, and consistent public page headers and footers. English and Urdu text and direction support continue across the portal.

Recent usability changes include:

- A light/dark switch beside Sign in on the homepage, also available in the portal header.
- The computer's theme is used by default.
- A manual theme choice is stored in local storage and survives page reloads.
- A system theme change clears the manual override; changes while the site was closed are detected when its saved system preference differs on return.
- Initial theme detection runs before the page paints to avoid a theme flash.
- Sign out has moved to the top header on the application, renewal, and dashboard pages, in the position beside the theme switch used by Sign in on the public homepage.
- The dashboard's duplicate sign-out button has been removed, and the shared header can wrap on small screens.

Administrative branding and access control have also been extended: Nova access uses an administrator email allowlist, and public administrator registration is disabled.

---

## Validation and Engineering Progress

Automated coverage has expanded from the 16 form tests described in August to a full backend suite covering OTP access, application drafts and submission, renewals, PDF generation, membership identifiers, payment details, workflow transitions, and Nova restrictions.

The latest full backend run on 9 October recorded **150 tests: 148 passed and 2 skipped**, with **645 assertions**. It ran with a 512 MB memory limit for that process because PDF rendering exceeded the local default 128 MB limit. No PHP configuration file was changed for this validation.

Recent frontend builds, targeted ESLint checks, formatting checks, and the existing sign-out test passed. Runtime checks also covered system theme defaults, manual switching, reload persistence, live and offline system theme changes, cleared storage, and unavailable browser storage.

Validation is not yet completely clean:

- Typechecking still reports three existing errors in the passkey components.
- Build warnings remain for CSS import ordering and unresolved Metropolis font assets.
- PHP static analysis has not been confirmed passing; earlier attempts exited without usable diagnostics.
- Passing local tests and builds does not establish that production hosting, live email delivery, or stakeholder acceptance testing are complete.

---

## Position Against the August Outstanding Items

| Item outstanding in August | Position as of 9 October |
|---|---|
| Renewal form | Implemented in the authenticated portal |
| Status check page | Application progress is implemented in the authenticated dashboard; a standalone `/status/{token}` page is not the current journey |
| Email notifications | Submission, payment-request, approval, rejection, and OTP notifications implemented |
| File uploads | Applicant and administrator payment receipt uploads implemented; a general supporting-document scan upload facility remains outstanding |
| PDF form downloads | Original blank forms and filled application/renewal PDFs implemented |
| Required documents checklist | Implemented, including the filled-form download and office receipt progress |
| Admin workflow actions | Four review actions implemented, with separate information and payment approval and automatic activation |
| Admin access control | Email allowlist and disabled public admin registration implemented; production configuration still requires verification |

---

## Remaining Work and Decisions

### Before Production Delivery

- Confirm the final WCCIK membership numbering policy.
- Confirm the annual expiry policy. Administrators currently choose a future expiry date; automatic enforcement of the April–March cycle is not implemented.
- Add general scanned supporting-document attachments if this remains required; payment receipt uploads do not provide that facility.
- Implement separate certificate collection notifications and agreed readiness timing.
- Resolve the remaining frontend type errors, build warnings, and PHP static-analysis validation.
- Refresh setup and route documentation where it still describes the older public renewal and status-link flows.
- Complete stakeholder acceptance testing for the three payment routes, renewals, administrative corrections, and English/Urdu presentation.
- Verify production hosting, administrator access configuration, outbound mail, file access, and database migrations before launch.

### Phase 2 Automation

The remaining automation includes:

- Automatic 10-day document deadlines and 11th-day administrative follow-up.
- Scheduled missing-document and missing-payment reminders.
- Renewal reminders from January at the agreed frequency.
- March 31st expiry and annulment rules.
- Holiday and Sunday exclusions in deadline calculations.
- Certificate collection dates and related automatic notifications.

Admin-editable email templates, originally listed in Phase 2, have already been delivered. The remaining scheduled and holiday-aware automation is not implemented.

---

## Hours Summary

The 1 August report recorded **40 hours spent** against a **165-hour contract allocation**. No updated timesheet was supplied for this reporting period, so this report does not invent additional hours, a new total, or a revised remaining-hours figure.
