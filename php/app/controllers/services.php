<?php
/**
 * الخدمات الجامعية (services/views.py)
 */

function service_list(): void
{
    $services = Service::where('is_active = 1', [], 'display_order, created_at DESC');
    $byType = fn(string $t) => array_values(array_filter($services, fn($s) => $s['service_type'] === $t));
    render('services/service_list', [
        'services'             => $services,
        'transport_services'   => $byType('transport'),
        'housing_services'     => $byType('housing'),
        'catering_services'    => $byType('catering'),
        'scholarship_services' => $byType('scholarship'),
        'page_title'           => __('الخدمات الجامعية'),
    ]);
}

function service_by_type(string $serviceType): void
{
    $typeNames = [
        'transport'   => __('خدمات النقل'),
        'housing'     => __('خدمات الإيواء'),
        'catering'    => __('خدمات الإطعام'),
        'scholarship' => __('المنح والإعانات'),
    ];
    render('services/service_by_type', [
        'services'     => Service::where('service_type = ? AND is_active = 1', [$serviceType], 'display_order, created_at DESC'),
        'service_type' => $serviceType,
        'page_title'   => $typeNames[$serviceType] ?? __('الخدمات الجامعية'),
    ]);
}

function service_detail(string $slug): void
{
    $service = Service::firstWhere('slug = ? AND is_active = 1', [$slug]);
    if (!$service) {
        abort(404);
    }
    render('services/service_detail', [
        'service'          => $service,
        'related_services' => Service::where('service_type = ? AND is_active = 1 AND id <> ?', [$service['service_type'], (int) $service['id']], 'display_order, created_at DESC', 3),
        'page_title'       => $service['title'],
    ]);
}
