@php
    $testimonials = $testimonials ?? [
        [
            'name' => 'أحمد محمد',
            'role' => 'مطور ويب — سوريا',
            'quote' => 'دورة تطوير الويب كانت نقطة تحول في مسيرتي المهنية. أسلوب الشرح ممتاز والتطبيقات العملية رائعة. أنصح الجميع بالتسجيل!',
            'stars' => 5,
        ],
        [
            'name' => 'سارة العلي',
            'role' => 'مهندسة برمجيات — الأردن',
            'quote' => 'فريق كلاودسوفت من أفضل مزودي الاستضافة. الدعم سريع، الخوادم مستقرة، والمحتوى محدث بأحدث التقنيات. استفدت كثيراً من باقة VPS.',
            'stars' => 5,
        ],
        [
            'name' => 'عمر حسان',
            'role' => 'مطور تطبيقات — العراق',
            'quote' => 'تعلمت إدارة الاستضافة من الدليل والفيديوهات وقمت بنقل موقعي خلال أيام فقط! الدعم الفني والمتابعة من الفريق كانت ممتازة.',
            'stars' => 4.5,
        ],
    ];
@endphp
<div class="row g-4 align-items-stretch">
    @foreach($testimonials as $index => $item)
    <div class="col-lg-4 col-md-6 d-flex">
        @include('frontend.partials.testimonial-card', array_merge($item, ['index' => $index]))
    </div>
    @endforeach
</div>
