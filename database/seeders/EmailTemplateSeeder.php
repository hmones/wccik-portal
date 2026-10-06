<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds the default email template library. Idempotent via updateOrCreate on
 * `key`, so running on an existing DB will refresh descriptions / variable
 * documentation but leave admin-edited subject/body intact.
 */
class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $tpl) {
            $existing = EmailTemplate::where('key', $tpl['key'])->first();

            if ($existing === null) {
                // Brand-new row — write everything, including subject/body.
                EmailTemplate::create($tpl);

                continue;
            }

            // Row exists — only refresh metadata, leave admin-edited copy alone.
            $existing->update([
                'name' => $tpl['name'],
                'description' => $tpl['description'],
                'available_variables' => $tpl['available_variables'],
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function templates(): array
    {
        return [
            [
                'key' => 'applicant_login_otp',
                'name' => 'Portal sign-in code',
                'description' => 'Sent to applicants when they request a sign-in code at /portal/sign-in.',
                'available_variables' => [
                    'code' => 'The 6-digit one-time sign-in code.',
                    'expires_in_minutes' => 'How long the code is valid for.',
                    'email' => 'The email the code was sent to.',
                ],
                'subject_en' => 'WCCIK Portal — Sign-in Code',
                'subject_ur' => 'ڈبلیو سی سی آئی پورٹل — سائن ان کوڈ',
                'body_en' => <<<'MD'
# Sign in to the WCCIK Portal

Enter this code to continue signing in. If you did not request a sign-in, you can safely ignore this email.

**Your sign-in code:** `{{ code }}`

This code expires in **{{ expires_in_minutes }} minutes**.

Thanks,
Women Chamber of Commerce &amp; Industry, Karachi
MD,
                'body_ur' => <<<'MD'
# ڈبلیو سی سی آئی پورٹل میں سائن ان کریں

سائن ان جاری رکھنے کے لیے یہ کوڈ درج کریں۔ اگر آپ نے سائن ان کی درخواست نہیں دی تو اس ای میل کو نظر انداز کر سکتے ہیں۔

**آپ کا سائن ان کوڈ:** `{{ code }}`

یہ کوڈ **{{ expires_in_minutes }} منٹ** میں ختم ہو جائے گا۔

شکریہ،
ویمن چیمبر آف کامرس اینڈ انڈسٹری، کراچی
MD,
            ],
            [
                'key' => 'application_submitted',
                'name' => 'Application submitted — acknowledgement',
                'description' => 'Sent to applicants right after they submit a new membership application.',
                'available_variables' => [
                    'applicant_name' => 'The applicant\'s representative name.',
                    'company_name' => 'The company applied for.',
                    'portal_url' => 'Link to the portal dashboard.',
                ],
                'subject_en' => 'We have received your WCCIK application',
                'subject_ur' => 'ہمیں آپ کی ڈبلیو سی سی آئی درخواست موصول ہو گئی',
                'body_en' => <<<'MD'
# Application received

Hello {{ applicant_name }},

Thank you for submitting your WCCIK membership application for **{{ company_name }}**. Our team will review your submission and documents.

You can track the status at any time by signing back into [the portal]({{ portal_url }}).

Remember to deliver the signed physical form and supporting documents to the WCCIK office within 10 days.

Thanks,
Women Chamber of Commerce &amp; Industry, Karachi
MD,
                'body_ur' => <<<'MD'
# درخواست موصول ہو گئی

السلام علیکم {{ applicant_name }}،

**{{ company_name }}** کے لیے ڈبلیو سی سی آئی ممبرشپ درخواست جمع کروانے کا شکریہ۔ ہماری ٹیم آپ کی درخواست اور دستاویزات کا جائزہ لے گی۔

آپ کسی بھی وقت [پورٹل]({{ portal_url }}) میں سائن ان کر کے صورتحال دیکھ سکتے ہیں۔

براہ کرم دستخط شدہ فزیکل فارم اور معاون دستاویزات ۱۰ دنوں کے اندر ڈبلیو سی سی آئی دفتر میں جمع کروائیں۔

شکریہ،
ویمن چیمبر آف کامرس اینڈ انڈسٹری، کراچی
MD,
            ],
            [
                'key' => 'application_awaiting_documents',
                'name' => 'Status — awaiting documents',
                'description' => 'Sent when an admin marks an application as awaiting physical documents.',
                'available_variables' => [
                    'applicant_name' => 'The applicant\'s representative name.',
                    'company_name' => 'The company applied for.',
                ],
                'subject_en' => 'WCCIK — Documents required to continue your application',
                'subject_ur' => 'ڈبلیو سی سی آئی — درخواست جاری رکھنے کے لیے دستاویزات درکار ہیں',
                'body_en' => <<<'MD'
# We are waiting on your documents

Hello {{ applicant_name }},

To continue processing your WCCIK application for **{{ company_name }}**, please deliver the signed physical form and all supporting documents to the WCCIK office at your earliest convenience.

Thanks,
Women Chamber of Commerce &amp; Industry, Karachi
MD,
                'body_ur' => <<<'MD'
# ہم آپ کی دستاویزات کے منتظر ہیں

السلام علیکم {{ applicant_name }}،

**{{ company_name }}** کے لیے آپ کی ڈبلیو سی سی آئی درخواست جاری رکھنے کے لیے، براہ کرم جلد از جلد دستخط شدہ فزیکل فارم اور تمام معاون دستاویزات ڈبلیو سی سی آئی دفتر میں جمع کروائیں۔

شکریہ،
ویمن چیمبر آف کامرس اینڈ انڈسٹری، کراچی
MD,
            ],
            [
                'key' => 'application_awaiting_payment',
                'name' => 'Status — awaiting payment',
                'description' => 'Sent when an admin marks an application as awaiting payment. The body should point the applicant at the portal upload page.',
                'available_variables' => [
                    'applicant_name' => 'The applicant\'s representative name.',
                    'company_name' => 'The company applied for.',
                    'portal_url' => 'Link to the portal dashboard where the applicant can upload payment proof.',
                ],
                'subject_en' => 'WCCIK — Documents approved, payment required to finalise',
                'subject_ur' => 'ڈبلیو سی سی آئی — دستاویزات منظور، درخواست مکمل کرنے کے لیے ادائیگی درکار ہے',
                'body_en' => <<<'MD'
# Documents approved — payment required to finalise

Hello {{ applicant_name }},

Great news — your WCCIK application documents for **{{ company_name }}** have been approved by our team. The last step before your membership becomes active is payment of the membership fee.

Please pay the fee and upload a copy of your payment proof via the portal:

[Open the portal to upload your payment proof]({{ portal_url }})

Accepted file types: PDF, JPG, PNG (max 5 MB). You can also deliver the receipt in person to the WCCIK office. Once we verify your payment, your membership will activate automatically and you will receive the approval email with your membership ID.

Thanks,
Women Chamber of Commerce &amp; Industry, Karachi
MD,
                'body_ur' => <<<'MD'
# دستاویزات منظور — درخواست مکمل کرنے کے لیے ادائیگی درکار ہے

السلام علیکم {{ applicant_name }}،

خوشخبری — **{{ company_name }}** کے لیے آپ کی ڈبلیو سی سی آئی درخواست کی دستاویزات ہماری ٹیم نے منظور کر لی ہیں۔ آپ کی ممبرشپ فعال ہونے سے پہلے صرف ممبرشپ فیس کی ادائیگی باقی ہے۔

براہ کرم فیس ادا کریں اور ادائیگی کا ثبوت پورٹل کے ذریعے اپ لوڈ کریں:

[پورٹل کھولیں اور ادائیگی کا ثبوت اپ لوڈ کریں]({{ portal_url }})

قابل قبول فائل اقسام: PDF، JPG، PNG (زیادہ سے زیادہ 5 MB)۔ آپ رسید ذاتی طور پر ڈبلیو سی سی آئی دفتر میں بھی جمع کروا سکتے ہیں۔ ادائیگی کی تصدیق ہوتے ہی آپ کی ممبرشپ خودکار طور پر فعال ہو جائے گی اور آپ کو ممبرشپ آئی ڈی کے ساتھ منظوری کی ای میل موصول ہو گی۔

شکریہ،
ویمن چیمبر آف کامرس اینڈ انڈسٹری، کراچی
MD,
            ],
            [
                'key' => 'application_approved',
                'name' => 'Application approved',
                'description' => 'Sent when an admin approves an application.',
                'available_variables' => [
                    'applicant_name' => 'The applicant\'s representative name.',
                    'company_name' => 'The company approved for membership.',
                    'membership_id' => 'The newly issued WCCIK membership ID.',
                    'active_until' => 'The date the membership is valid until.',
                    'portal_url' => 'Link to the portal dashboard.',
                ],
                'subject_en' => 'Welcome to WCCIK — Your membership is approved',
                'subject_ur' => 'ڈبلیو سی سی آئی میں خوش آمدید — آپ کی ممبرشپ منظور ہو گئی',
                'body_en' => <<<'MD'
# Welcome to WCCIK

Congratulations {{ applicant_name }} — your WCCIK membership for **{{ company_name }}** has been approved.

**Membership ID:** `{{ membership_id }}`
**Valid until:** {{ active_until }}

You can view your membership record at any time by signing into [the portal]({{ portal_url }}).

Thanks,
Women Chamber of Commerce &amp; Industry, Karachi
MD,
                'body_ur' => <<<'MD'
# ڈبلیو سی سی آئی میں خوش آمدید

مبارک ہو {{ applicant_name }} — **{{ company_name }}** کے لیے آپ کی ڈبلیو سی سی آئی ممبرشپ منظور ہو گئی ہے۔

**ممبرشپ آئی ڈی:** `{{ membership_id }}`
**تاریخ انقضا:** {{ active_until }}

آپ کسی بھی وقت [پورٹل]({{ portal_url }}) میں سائن ان کر کے اپنا ممبرشپ ریکارڈ دیکھ سکتے ہیں۔

شکریہ،
ویمن چیمبر آف کامرس اینڈ انڈسٹری، کراچی
MD,
            ],
            [
                'key' => 'application_rejected',
                'name' => 'Application rejected',
                'description' => 'Sent when an admin rejects an application. Should include the reason.',
                'available_variables' => [
                    'applicant_name' => 'The applicant\'s representative name.',
                    'company_name' => 'The company applied for.',
                    'rejection_reason' => 'The admin-provided reason for rejection.',
                ],
                'subject_en' => 'Decision on your WCCIK application',
                'subject_ur' => 'آپ کی ڈبلیو سی سی آئی درخواست کا فیصلہ',
                'body_en' => <<<'MD'
# Decision on your application

Hello {{ applicant_name }},

After reviewing your WCCIK membership application for **{{ company_name }}**, we are unable to approve it at this time.

**Reason:** {{ rejection_reason }}

If you believe this is a mistake or would like to re-apply, please contact the WCCIK office directly.

Thanks,
Women Chamber of Commerce &amp; Industry, Karachi
MD,
                'body_ur' => <<<'MD'
# آپ کی درخواست پر فیصلہ

السلام علیکم {{ applicant_name }}،

**{{ company_name }}** کے لیے آپ کی ڈبلیو سی سی آئی ممبرشپ درخواست کا جائزہ لینے کے بعد ہم اسے اس وقت منظور نہیں کر سکتے۔

**وجہ:** {{ rejection_reason }}

اگر آپ کو لگتا ہے کہ یہ غلطی ہے یا آپ دوبارہ درخواست دینا چاہتے ہیں تو براہ کرم براہ راست ڈبلیو سی سی آئی دفتر سے رابطہ کریں۔

شکریہ،
ویمن چیمبر آف کامرس اینڈ انڈسٹری، کراچی
MD,
            ],
        ];
    }
}
