<?php
/**
 * اتصل بنا (contact/views.py)
 */

function contact_view(): void
{
    $form = contact_form(is_post() ? $_POST : null);
    $status = 200;
    if (is_post()) {
        $key = 'contact:' . client_ip();
        // حقل خفي (honeypot) لا يراه الزائر ولا يملؤه إلا البرامج الآلية: نتظاهر بالنجاح ولا نحفظ شيئاً
        $honeypot = $_POST['contact_website'] ?? '';
        if ($honeypot !== '') {
            security_log('contact_honeypot');
            flash('success', __('شكراً لك {name}! تم إرسال رسالتك بنجاح. سنتواصل معك في أقرب وقت ممكن.', ['name' => is_string($_POST['full_name'] ?? null) ? mb_substr(trim($_POST['full_name']), 0, 60) : '']));
            redirect_to('contact:contact');
        }
        $wait = RateLimit::retryAfter($key, (int) config('security.contact_max', 5), 600);
        if ($wait > 0) {
            header('Retry-After: ' . $wait);
            flash('error', too_many_attempts_message($wait));
            $status = 429;
        } elseif ($form->is_valid()) {
            RateLimit::hit($key, 600);
            $data = $form->cleaned;
            ContactMessage::create([
                'full_name'  => $data['full_name'],
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'subject'    => $data['subject'],
                'message'    => $data['message'],
                'ip_address' => client_ip(),
            ]);
            flash('success', __('شكراً لك {name}! تم إرسال رسالتك بنجاح. سنتواصل معك في أقرب وقت ممكن.', ['name' => $data['full_name']]));
            redirect_to('contact:contact');
        } else {
            flash('error', __('يرجى تصحيح الأخطاء في النموذج'));
        }
    }
    render('contact/contact', [
        'form'           => $form,
        'page_title'     => __('اتصل بنا'),
        'total_messages' => ContactMessage::count(),
    ], $status);
}
