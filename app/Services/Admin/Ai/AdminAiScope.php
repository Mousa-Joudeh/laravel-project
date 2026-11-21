<?php

namespace App\Services\Admin\Ai;

class AdminAiScope
{
    private const TOPICS = [
        'bookings' => '/bookings?|reservations?|cancellations?|cancelled|booking|حجز|حجوز|إلغاء|الغاء|إلغاءات|ملغية/u',
        'payments' => '/payments?|transactions?|revenue|refunds?|stripe|amount|financial|مدفوع|دفع|مال|مالية|إيراد|ايراد|استرداد|تحويل|معاملات/u',
        'approvals' => '/approvals?|requests?|moderation|pending|موافق|طلبات|معلق|معلّق|قبول|رفض|مراجعة/u',
        'catalog' => '/studios?|services?|packages?|listings?|catalog|equipment|استوديو|استديو|خدم|باق|حزم|معدات|عروض/u',
        'reviews' => '/reviews?|ratings?|feedback|تقييم|مراجع|آراء|اراء/u',
        'traffic' => '/traffic|visits?|visitors?|analytics|views?|reach|زيارات|زيارة|زوار|المرور|التحليل|المشاهدات/u',
        'users' => '/customers?|providers?|partners?|users?|members?|عملاء|عميل|مستخدم|مزود|مقدم|شركاء|شريك/u',
        'overview' => '/insights?|recommendations?|overview|summary|dashboard|performance|report|trends?|space|platform|how.*doing|نصيح|توصي|اقتراح|ملخص|تقرير|أداء|اداء|منصة|المنصة|المشروع|الوضع|مؤشرات|تحليل/u',
    ];

    public function topic(string $question, int $maxLength = 450): ?string
    {
        $text = mb_strtolower(trim($question));
        if ($text === '' || mb_strlen($text) > $maxLength) {
            return null;
        }

        // These requests are outside the assistant's informational, read-only remit.
        if (preg_match('/[\w.+\-]+@[\w\-]+\.[\w.\-]+|\b\d{7,}\b|\b(?:booking|customer|user|provider)\s*(?:id|#)\s*\d+\b/iu', $text)) {
            return null;
        }

        if (preg_match('/\b(?:approve|delete|refund|suspend|cancel|change|reset|update|create|charge)\s+(?:all|the|this|that|a|my|booking|user|customer|provider)\b|وافق على|احذف|الغِ الحجز|غير الدور|غيّر الدور/iu', $text)) {
            return null;
        }

        if (preg_match('/ignore (all |the |previous )?instructions|system prompt|developer message|jailbreak|pretend to be|forget your rules|override|execute (sql|command)|select \*|drop table|delete from|insert into|update users|password|api.?key|secret|token|credentials|email address|\bemail\b|\bphone\b|phone number|personal data|dump database|hack|exploit|shell|طقس|الطقس|نكتة|رياضة|مباراة|سياسة|برمجة|بايثون|كلمة مرور|كلمة السر|سرّي|سري|مفتاح|بيانات شخصية|رقم هاتف|إيميل|بريد إلكتروني|تجاهل التعليمات|تخطى التعليمات|احذف|عدل قاعدة|نفذ أمر|\bweather\b|\bjoke\b|\bfootball\b|\bpolitics\b|\brecipe\b/iu', $text)) {
            return null;
        }

        foreach (self::TOPICS as $topic => $pattern) {
            if (preg_match($pattern, $text)) {
                return $topic;
            }
        }

        return null;
    }

    public function fallback(string $topic, array $data, string $question = ''): string
    {
        $b = $data['bookings'];
        $p = $data['payments'];
        $a = $data['approvals'];
        $days = $data['window']['days'];

        if (preg_match('/\p{Arabic}/u', $question)) {
            return match ($topic) {
                'bookings' => "خلال آخر {$days} يوم سجّلت SPACE عدد {$b['total']} حجز: {$b['completed']} مكتمل، {$b['confirmed']} مؤكّد، {$b['cancelled']} ملغي، و{$b['pending_payment']} بانتظار الدفع. " . ($b['cancellation_rate_pct'] === null ? 'لا توجد حجوزات كافية لحساب نسبة الإلغاء.' : "نسبة الإلغاء {$b['cancellation_rate_pct']}%."),
                'payments' => "خلال آخر {$days} يوم: المدفوعات الناجحة {$p['successful_gross']} دينار، الاستردادات الناجحة {$p['successful_refunds']} دينار، والصافي {$p['net']} دينار أردني. نجحت {$p['successful_attempts']} عملية دفع من أصل {$p['attempts']} محاولة.",
                'approvals' => "هناك {$a['total']} طلب بانتظار المراجعة: {$a['partners']} مقدم خدمة، {$a['studios']} استوديو، و{$a['services']} خدمة. كل طلب بحاجة لمراجعة الأدمن يدويًا.",
                'catalog' => "الكتالوج يحتوي على {$data['catalog']['approved_active_studios']} استوديو معتمد ونشط، و{$data['catalog']['approved_active_services']} خدمة معتمدة ونشطة، و{$data['catalog']['active_packages']} باكج نشط. الباكجات غير النشطة ({$data['catalog']['inactive_packages']}) مش بالضرورة طلبات موافقة.",
                'reviews' => "خلال آخر {$days} يوم وصل {$data['reviews']['count']} تقييم. " . ($data['reviews']['average_rating'] === null ? 'ما في بيانات كافية لحساب المتوسط.' : "متوسط التقييم {$data['reviews']['average_rating']} من 5."),
                'traffic' => "الموقع سجّل {$data['traffic']['recorded_public_page_views']} مشاهدة لصفحات عامة خلال آخر {$days} يوم. الصفر ممكن يعني أن تتبع الزيارات غير مفعّل، وليس بالضرورة عدم وجود زوار.",
                'users' => "عندنا {$data['users']['customers']} عميل، منهم {$data['users']['new_customers']} سجّلوا خلال آخر {$days} يوم، و{$data['users']['active_providers']} حساب مقدم خدمة نشط.",
                default => "خلال آخر {$days} يوم سجّلنا {$b['total']} حجز، وصافي مدفوعات ناجحة {$p['net']} دينار أردني، و{$a['total']} طلب موافقة معلق. راجع التوصيات الموجودة في الصفحة قبل اتخاذ أي إجراء.",
            };
        }

        return match ($topic) {
            'bookings' => "In the past {$days} days, SPACE recorded {$b['total']} bookings: {$b['completed']} completed, {$b['confirmed']} confirmed, {$b['cancelled']} cancelled and {$b['pending_payment']} pending payment. " . ($b['cancellation_rate_pct'] === null ? 'There are no bookings for a cancellation-rate calculation.' : "The cancellation rate is {$b['cancellation_rate_pct']}%."),
            'payments' => "In the past {$days} days, successful payments totalled {$p['successful_gross']} JOD, successful refunds {$p['successful_refunds']} JOD, and net processed payments {$p['net']} JOD. {$p['successful_attempts']} of {$p['attempts']} payment attempts succeeded.",
            'approvals' => "There are {$a['total']} open approvals: {$a['partners']} partner profiles, {$a['studios']} studios and {$a['services']} services. Each requires manual administrator review.",
            'catalog' => "The catalog has {$data['catalog']['approved_active_studios']} approved active studios, {$data['catalog']['approved_active_services']} approved active services and {$data['catalog']['active_packages']} active packages. Inactive packages ({$data['catalog']['inactive_packages']}) are not necessarily pending approval.",
            'reviews' => "In the past {$days} days, {$data['reviews']['count']} reviews were recorded. " . ($data['reviews']['average_rating'] === null ? 'There is not enough data to calculate an average rating.' : "The average rating is {$data['reviews']['average_rating']} out of 5."),
            'traffic' => "The system recorded {$data['traffic']['recorded_public_page_views']} public page views in the past {$days} days. A zero count may mean the tracking middleware is not active; do not treat it as proof of no visitors.",
            'users' => "SPACE has {$data['users']['customers']} customers ({$data['users']['new_customers']} registered in the past {$days} days) and {$data['users']['active_providers']} active provider accounts.",
            default => "SPACE recorded {$b['total']} bookings, {$p['net']} JOD in net successful payments and {$a['total']} pending approvals over the selected {$days}-day window. Check the evidence-based recommendations on this page for follow-up actions.",
        };
    }
}
