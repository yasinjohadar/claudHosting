@include('admin.coolify.partials.metrics-widget', [
    'metricsScope' => 'server',
    'metricsUuid' => $uuid,
    'metricsTitle' => 'مراقبة السيرفر (لحظي)',
    'serverUuid' => $uuid,
])
