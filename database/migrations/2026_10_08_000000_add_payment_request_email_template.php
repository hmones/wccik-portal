<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $template = [
            'key' => 'application_payment_requested',
            'name' => 'Payment proof requested',
            'description' => 'Sent when an admin marks an application as awaiting payment. The body should point the applicant at the portal upload page.',
            'available_variables' => [
                'applicant_name' => 'The applicant\'s representative name.',
                'company_name' => 'The company applied for.',
                'portal_url' => 'Link to the portal dashboard where the applicant can upload payment proof.',
            ],
            'subject_en' => 'WCCIK, Payment proof required',
            'subject_ur' => 'ڈبلیو سی سی آئی, ادائیگی کا ثبوت درکار ہے',
            'body_en' => <<<'MD'
# Payment proof required

Hello {{ applicant_name }},

We need valid proof of payment of the membership fee for your WCCIK application for **{{ company_name }}**. This request does not confirm information approval.

Please pay the fee and upload a copy of your payment proof via the portal:

[Open the portal to upload your payment proof]({{ portal_url }})

Accepted file types: PDF, JPG, PNG (max 5 MB). You can also deliver the receipt in person to the WCCIK office. Membership activates only after the signed form and supporting documents are received and both your information and payment are approved. You will then receive an approval email with your membership number and expiry date.

Thanks,
Women Chamber of Commerce &amp; Industry, Karachi
MD,
            'body_ur' => <<<'MD'
# ادائیگی کا ثبوت درکار ہے

السلام علیکم {{ applicant_name }}،

**{{ company_name }}** کے لیے آپ کی ڈبلیو سی سی آئی درخواست کی ممبرشپ فیس کی ادائیگی کا درست ثبوت درکار ہے۔ یہ درخواست معلومات کی منظوری کی تصدیق نہیں ہے۔

براہ کرم فیس ادا کریں اور ادائیگی کا ثبوت پورٹل کے ذریعے اپ لوڈ کریں:

[پورٹل کھولیں اور ادائیگی کا ثبوت اپ لوڈ کریں]({{ portal_url }})

قابل قبول فائل اقسام: PDF، JPG، PNG (زیادہ سے زیادہ 5 MB)۔ آپ رسید ذاتی طور پر ڈبلیو سی سی آئی دفتر میں بھی جمع کروا سکتے ہیں۔ دستخط شدہ فارم اور معاون دستاویزات موصول ہونے اور معلومات اور ادائیگی دونوں کی منظوری کے بعد ممبرشپ فعال ہوگی۔ اس کے بعد آپ کو ممبرشپ نمبر اور اختتامی تاریخ کے ساتھ منظوری کی ای میل موصول ہوگی۔

شکریہ،
ویمن چیمبر آف کامرس اینڈ انڈسٹری، کراچی
MD,
        ];
        $template['available_variables'] = json_encode($template['available_variables'], JSON_THROW_ON_ERROR);
        $template['created_at'] = now();
        $template['updated_at'] = now();

        DB::table('email_templates')->insertOrIgnore($template);
    }

    public function down(): void
    {
        // Keep email content and any subsequent admin customisation.
    }
};
