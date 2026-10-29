<?php
/**
 * اتصل بنا (contact/views.py)
 */

function contact_view(): void
{
    $form = contact_form(is_post() ? $_POST : null);
    if (is_post()) {
        if ($form->is_valid()) {
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
        }
        flash('error', __('يرجى تصحيح الأخطاء في النموذج'));
    }
    render('contact/contact', [
        'form'           => $form,
        'page_title'     => __('اتصل بنا'),
        'total_messages' => ContactMessage::count(),
    ]);
}
