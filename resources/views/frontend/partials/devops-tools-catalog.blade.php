@php
    $devopsCategories = [
        [
            'title' => 'CI/CD وبناء الأنابيب',
            'icon' => 'fas fa-sync-alt',
            'desc' => 'أتمتة البناء والاختبار والنشر من الـ commit حتى الإنتاج.',
            'tools' => [
                ['name' => 'Jenkins', 'icon' => 'devicon-jenkins-line', 'type' => 'devicon', 'task' => 'تشغيل Pipelines مرنة مع مراحل build/test/deploy.'],
                ['name' => 'GitLab CI/CD', 'icon' => 'devicon-gitlab-plain', 'type' => 'devicon', 'task' => 'إدارة CI/CD داخل GitLab مع runners و environments.'],
                ['name' => 'GitHub Actions', 'icon' => asset('frontend/assets/images/tech/githubactions.svg'), 'type' => 'img', 'task' => 'Workflows تلقائية للبناء والنشر داخل GitHub.'],
                ['name' => 'CircleCI', 'icon' => asset('frontend/assets/images/tech/circleci.svg'), 'type' => 'img', 'task' => 'أنابيب سريعة مع caching وإدارة jobs متوازية.'],
                ['name' => 'Azure DevOps', 'icon' => 'devicon-azuredevops-plain', 'type' => 'devicon', 'task' => 'Boards + Repos + Pipelines لمنصات Microsoft.'],
                ['name' => 'Argo CD / Flux', 'icon' => asset('frontend/assets/images/tech/argo.svg'), 'type' => 'img', 'task' => 'GitOps للنشر التلقائي على Kubernetes.'],
            ],
        ],
        [
            'title' => 'حاويات وأوركستريشن',
            'icon' => 'fab fa-docker',
            'desc' => 'تشغيل التطبيقات داخل حاويات مع إدارة clusters قابلة للتوسع.',
            'tools' => [
                ['name' => 'Docker', 'icon' => 'devicon-docker-plain', 'type' => 'devicon', 'task' => 'Containerization موحد للتطوير والإنتاج.'],
                ['name' => 'Kubernetes', 'icon' => 'devicon-kubernetes-plain', 'type' => 'devicon', 'task' => 'إدارة Pods وServices وAuto-scaling.'],
                ['name' => 'Helm', 'icon' => asset('frontend/assets/images/tech/helm.svg'), 'type' => 'img', 'task' => 'قوالب Charts لنشر الخدمات بسرعة.'],
                ['name' => 'Kustomize', 'icon' => asset('frontend/assets/images/tech/kubernetes.svg'), 'type' => 'img', 'task' => 'تخصيص manifests حسب كل بيئة.'],
                ['name' => 'Docker Compose', 'icon' => 'fab fa-docker', 'type' => 'fa', 'task' => 'تشغيل بيئات متعددة الخدمات محلياً وعلى الخادم.'],
                ['name' => 'Rancher / Podman', 'icon' => asset('frontend/assets/images/tech/rancher.svg'), 'type' => 'img', 'task' => 'إدارة clusters وحاويات بدائل تشغيل.'],
            ],
        ],
        [
            'title' => 'سحابة ومنصات',
            'icon' => 'fas fa-cloud',
            'desc' => 'نشر وإدارة التطبيقات على السحابة العامة والخدمات المدارة.',
            'tools' => [
                ['name' => 'AWS', 'icon' => 'devicon-amazonwebservices-plain-wordmark', 'type' => 'devicon', 'task' => 'خدمات EC2, ECS, RDS, S3 والبنية المرنة.'],
                ['name' => 'Azure', 'icon' => 'devicon-azure-plain', 'type' => 'devicon', 'task' => 'تشغيل المنصات والتكامل مع خدمات Microsoft.'],
                ['name' => 'Google Cloud', 'icon' => 'devicon-googlecloud-plain', 'type' => 'devicon', 'task' => 'GCE/GKE وخدمات managed للبنى الحديثة.'],
                ['name' => 'DigitalOcean', 'icon' => 'devicon-digitalocean-plain', 'type' => 'devicon', 'task' => 'Droplets وManaged DB للبنى الرشيقة.'],
                ['name' => 'EKS / AKS / GKE', 'icon' => 'fas fa-network-wired', 'type' => 'fa', 'task' => 'Kubernetes مُدار على AWS/Azure/GCP.'],
                ['name' => 'Lambda / Serverless', 'icon' => 'devicon-amazonwebservices-plain', 'type' => 'devicon', 'task' => 'وظائف بلا خوادم لتقليل التكلفة والتشغيل.'],
            ],
        ],
        [
            'title' => 'IaC وإدارة التكوين',
            'icon' => 'fas fa-code-branch',
            'desc' => 'تحويل البنية التحتية والإعدادات إلى كود قابل للإصدار والمراجعة.',
            'tools' => [
                ['name' => 'Terraform', 'icon' => 'devicon-terraform-plain', 'type' => 'devicon', 'task' => 'تعريف البنية كوداً مع state وإعادة استخدام modules.'],
                ['name' => 'Ansible', 'icon' => 'devicon-ansible-plain', 'type' => 'devicon', 'task' => 'Provisioning وconfiguration بدون agents.'],
                ['name' => 'Pulumi', 'icon' => asset('frontend/assets/images/tech/pulumi.svg'), 'type' => 'img', 'task' => 'IaC بلغات برمجة عامة للمشاريع المعقدة.'],
                ['name' => 'CloudFormation', 'icon' => 'devicon-amazonwebservices-plain', 'type' => 'devicon', 'task' => 'قوالب بنية AWS الأصلية ككود.'],
                ['name' => 'Puppet / Chef', 'icon' => 'fas fa-gears', 'type' => 'fa', 'task' => 'إدارة التكوين المؤسسية على نطاق كبير.'],
                ['name' => 'Bash / Python', 'icon' => 'devicon-python-plain', 'type' => 'devicon', 'task' => 'أتمتة مهام التشغيل اليومية والنسخ والصيانة.'],
            ],
        ],
        [
            'title' => 'مراقبة وسجلات وأمان',
            'icon' => 'fas fa-chart-line',
            'desc' => 'رؤية شاملة للأداء والتنبيهات والأحداث التشغيلية والأمنية.',
            'tools' => [
                ['name' => 'Prometheus', 'icon' => 'devicon-prometheus-original', 'type' => 'devicon', 'task' => 'جمع metrics وتنبيهات عبر قواعد دقيقة.'],
                ['name' => 'Grafana', 'icon' => 'devicon-grafana-plain', 'type' => 'devicon', 'task' => 'لوحات متابعة لحظية ومرئية للخدمات.'],
                ['name' => 'ELK Stack', 'icon' => asset('frontend/assets/images/tech/elastic.svg'), 'type' => 'img', 'task' => 'تجميع وتحليل logs وبحث سريع في الأحداث.'],
                ['name' => 'Datadog', 'icon' => asset('frontend/assets/images/tech/datadog.svg'), 'type' => 'img', 'task' => 'APM ومراقبة بنية cloud-native متقدمة.'],
                ['name' => 'Vault', 'icon' => asset('frontend/assets/images/tech/hashicorp.svg'), 'type' => 'img', 'task' => 'إدارة أسرار ومفاتيح في بيئات CI/CD.'],
                ['name' => 'Jaeger / OpenTelemetry', 'icon' => asset('frontend/assets/images/tech/opentelemetry.svg'), 'type' => 'img', 'task' => 'Tracing لتتبّع الطلبات بين الخدمات.'],
            ],
        ],
    ];
@endphp

<section class="section-padding security-tools-section devops-tools-section" id="devops-tools">
    <div class="security-tools-section__bg" aria-hidden="true">
        <div class="security-tools-section__circuit"></div>
        <div class="security-tools-section__glow security-tools-section__glow--1"></div>
        <div class="security-tools-section__glow security-tools-section__glow--2"></div>
    </div>
    <div class="container position-relative security-tools-section__inner">
        <div class="section-header animate-on-scroll">
            <span class="section-badge">التقنيات</span>
            <h2>تقنيات DevOps — شاملة</h2>
            <p>نفس نهج الأمن السيبراني: أدوات واضحة، أدوار محددة، وتنفيذ عملي في بيئات الاستضافة والإنتاج.</p>
        </div>

        @foreach ($devopsCategories as $catIndex => $category)
            <div class="security-tools-category animate-on-scroll animate-delay-{{ ($catIndex % 4) + 1 }}">
                <header class="security-tools-category__head">
                    <div class="security-tools-category__icon" aria-hidden="true">
                        <i class="{{ $category['icon'] }}"></i>
                    </div>
                    <div>
                        <h3 class="security-tools-category__title">{{ $category['title'] }}</h3>
                        <p class="security-tools-category__desc">{{ $category['desc'] }}</p>
                    </div>
                </header>
                <div class="row g-3">
                    @foreach ($category['tools'] as $tool)
                        <div class="col-md-6 col-xl-4">
                            <article class="security-tool-card">
                                @include('frontend.partials.security-tool-icon', [
                                    'type' => $tool['type'] ?? 'fa',
                                    'icon' => $tool['icon'],
                                    'name' => $tool['name'],
                                ])
                                <h4 class="security-tool-card__name">{{ $tool['name'] }}</h4>
                                <p class="security-tool-card__task">{{ $tool['task'] }}</p>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
