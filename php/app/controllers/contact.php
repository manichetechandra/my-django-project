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
            flash('success', 'شكراً لك ' . $data['full_name'] . '! تم إرسال رسالتك بنجاح. سنتواصل معك في أقرب وقت ممكن.');
            redirect_to('contact:contact');
        }
        flash('error', 'يرجى تصحيح الأخطاء في النموذج');
    }
    render('contact/contact', [
        'form'           => $form,
        'page_title'     => 'اتصل بنا',
        'total_messages' => ContactMessage::count(),
    ]);
}
