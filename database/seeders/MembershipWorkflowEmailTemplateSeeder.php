<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class MembershipWorkflowEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'application_form_accepted',
                'name' => 'Form accepted - payment instructions',
                'description' => 'Sent when the office accepts the signed form and supporting documents. Opens the payment stage.',
                'subject_en' => 'WCCIK - Your form is accepted, please arrange payment',
                'subject_ur' => 'ڈبلیو سی سی آئی - فارم منظور، براہ کرم ادائیگی کریں',
                'body_en' => "# Your form has been accepted\n\nHello {{ applicant_name }},\n\nThank you for submitting the signed form and supporting documents for **{{ company_name }}**. The office has accepted your application information. You are now eligible to pay the membership fee.\n\n## Payment instructions\n\n{{ payment_instructions }}\n\nSupported methods: Cheque, Pay Order, or Online Bank Transfer. If you already supplied a receipt with your renewal, do not pay again; our team will review that payment.\n\n[Sign in to submit your payment details and receipt]({{ portal_url }}). Include the payment date and method. You can also deliver a cheque or pay order and its receipt to the office for an administrator to record.\n\nYour membership certificate will be ready for collection within 7 days after payment is processed. Membership is not active yet; our administrators will verify the payment before creating or renewing your membership.",
                'body_ur' => "# آپ کا فارم منظور ہو گیا ہے\n\nالسلام علیکم {{ applicant_name }}،\n\n**{{ company_name }}** کے دستخط شدہ فارم اور معاون دستاویزات جمع کروانے کا شکریہ۔ دفتر نے آپ کی معلومات منظور کر لی ہیں۔ اب آپ ممبرشپ فیس ادا کر سکتے ہیں۔\n\n## ادائیگی کی ہدایات\n\n{{ payment_instructions }}\n\nادائیگی کے طریقے: چیک، پے آرڈر یا آن لائن بینک منتقلی۔ اگر تجدید کے ساتھ رسید دے چکے ہیں تو دوبارہ ادائیگی نہ کریں؛ ٹیم اس ادائیگی کا جائزہ لے گی۔\n\n[پورٹل میں ادائیگی کی تاریخ، طریقہ اور رسید جمع کروائیں]({{ portal_url }})۔ چیک یا پے آرڈر اور رسید دفتر میں بھی دی جا سکتی ہے۔\n\nادائیگی پراسیس ہونے کے بعد ۷ دنوں کے اندر ممبرشپ سرٹیفکیٹ وصولی کے لیے تیار ہوگا۔ ممبرشپ ابھی فعال نہیں ہے؛ منتظمین ادائیگی کی تصدیق کے بعد ممبرشپ بنائیں گے یا تجدید کریں گے۔",
            ],
            [
                'key' => 'admin_application_payment_submitted',
                'name' => 'Admin alert - payment submitted',
                'description' => 'Sent to each authorised administrator after payment details are submitted.',
                'subject_en' => 'WCCIK - Payment proof: {{ applicant_name }} / {{ cnic }} / {{ company_name }}',
                'subject_ur' => 'ڈبلیو سی سی آئی - ادائیگی کا ثبوت: {{ applicant_name }} / {{ cnic }} / {{ company_name }}',
                'body_en' => "# Payment is ready for review\n\n**{{ applicant_name }}** has submitted payment details for **{{ company_name }}**.\n\nCNIC: {{ cnic }}\nPayment date: {{ payment_date }}\nPayment method: {{ payment_method }}\n\nThe application form has been accepted. Review the receipt and process the payment before creating or renewing membership.\n\n[Review the application in Nova]({{ admin_url }}).",
                'body_ur' => "# ادائیگی جائزے کے لیے تیار ہے\n\n**{{ applicant_name }}** نے **{{ company_name }}** کے لیے ادائیگی کی معلومات جمع کروائی ہیں۔\n\nشناختی کارڈ: {{ cnic }}\nادائیگی کی تاریخ: {{ payment_date }}\nادائیگی کا طریقہ: {{ payment_method }}\n\nفارم منظور ہو چکا ہے۔ رسید کا جائزہ لے کر ادائیگی پراسیس کریں، پھر ممبرشپ بنائیں یا تجدید کریں۔\n\n[نووا میں درخواست دیکھیں]({{ admin_url }})۔",
            ],
            [
                'key' => 'application_payment_correction',
                'name' => 'Payment needs correction',
                'description' => 'Sent if an administrator rejects payment or requests corrected proof after form acceptance.',
                'subject_en' => 'WCCIK - Please correct your payment details',
                'subject_ur' => 'ڈبلیو سی سی آئی - ادائیگی کی معلومات درست کریں',
                'body_en' => "# Payment correction required\n\nHello {{ applicant_name }},\n\nThe office needs corrected payment details or proof for **{{ company_name }}**.\n\n{{ payment_notes }}\n\nPayment instructions:\n\n{{ payment_instructions }}\n\n[Sign in to supply the correct receipt, date and payment method]({{ portal_url }}). Your form remains accepted, but membership cannot be activated until payment is verified. The certificate will be ready for collection within 7 days after payment is processed.",
                'body_ur' => "# ادائیگی کی درستگی درکار ہے\n\nالسلام علیکم {{ applicant_name }}،\n\n**{{ company_name }}** کے لیے ادائیگی کی درست معلومات یا رسید درکار ہے۔\n\n{{ payment_notes }}\n\nادائیگی کی ہدایات:\n\n{{ payment_instructions }}\n\n[پورٹل میں درست رسید، تاریخ اور طریقہ جمع کروائیں]({{ portal_url }})۔ فارم منظور ہے مگر ادائیگی کی تصدیق تک ممبرشپ فعال نہیں ہوگی۔ ادائیگی پراسیس ہونے کے بعد ۷ دنوں کے اندر سرٹیفکیٹ وصولی کے لیے تیار ہوگا۔",
            ],
            [
                'key' => 'membership_certificate_collection',
                'name' => 'Membership certificate collection',
                'description' => 'Separate collection email sent after verified payment activates membership.',
                'subject_en' => 'WCCIK - Membership certificate collection',
                'subject_ur' => 'ڈبلیو سی سی آئی - ممبرشپ سرٹیفکیٹ کی وصولی',
                'body_en' => "# Membership certificate collection\n\nHello {{ applicant_name }},\n\nPayment for **{{ company_name }}** has been processed and your membership is approved.\n\nMembership number: **{{ membership_id }}**\nPayment processed on: {{ payment_processed_at }}\n\nYour membership certificate will be ready for collection within 7 days after payment processing. Please contact the WCCIK office to arrange collection.\n\n[View your membership]({{ portal_url }}).",
                'body_ur' => "# ممبرشپ سرٹیفکیٹ کی وصولی\n\nالسلام علیکم {{ applicant_name }}،\n\n**{{ company_name }}** کی ادائیگی پراسیس ہو گئی ہے اور ممبرشپ منظور ہے۔\n\nممبرشپ نمبر: **{{ membership_id }}**\nادائیگی پراسیس ہونے کی تاریخ: {{ payment_processed_at }}\n\nادائیگی پراسیس ہونے کے بعد ۷ دنوں کے اندر سرٹیفکیٹ وصولی کے لیے تیار ہوگا۔ وصولی کے لیے ڈبلیو سی سی آئی دفتر سے رابطہ کریں۔\n\n[اپنی ممبرشپ دیکھیں]({{ portal_url }})۔",
            ],
        ];

        $variables = [
            'applicant_name' => 'Authorised representative name.',
            'company_name' => 'Company name.',
            'portal_url' => 'Applicant dashboard URL.',
            'payment_instructions' => 'Fee and account instructions recorded by the accepting administrator.',
            'payment_notes' => 'Reason payment needs correction.',
            'cnic' => 'Applicant CNIC.',
            'payment_date' => 'Payment date supplied by applicant or office.',
            'payment_method' => 'Payment method label.',
            'admin_url' => 'Nova application review URL.',
            'membership_id' => 'Approved membership number.',
            'payment_processed_at' => 'Payment processing date confirmed by administrator.',
        ];

        foreach ($templates as $template) {
            EmailTemplate::firstOrCreate(['key' => $template['key']], [
                ...$template,
                'available_variables' => $variables,
            ]);
        }
    }
}
