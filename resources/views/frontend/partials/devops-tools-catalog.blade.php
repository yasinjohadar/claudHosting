@php
    $devopsCategories = [
        [
            'title' => 'CI/CD وبناء الأنابيب',
            'icon' => 'fas fa-sync-alt',
            'desc' => 'أتمتة البناء والاختبار والنشر من الـ commit حتى الإنتاج.',
            'tools' => [
                ['name' => 'Jenkins', 'icon' => 'devicon-jenkins-line', 'type' => 'devicon', 'task' => 'تشغيل Pipelines مرنة مع مراحل build/test/deploy.'],
                ['name' => 'GitLab CI/CD', 'icon' => 'devicon-gitlab-plain', 'type' => 'devicon', 'task' => 'إدارة CI/CD داخل GitLab مع runners و environments.'],
                ['name' => 'GitHub Actions', 'icon' => 'https://cdn.simpleicons.org/githubactions/2088FF', 'type' => 'img', 'task' => 'Workflows تلقائية للبناء والنشر داخل GitHub.'],
                ['name' => 'CircleCI', 'icon' => 'https://cdn.simpleicons.org/circleci/343434', 'type' => 'img', 'task' => 'أنابيب سريعة مع caching وإدارة jobs متوازية.'],
                ['name' => 'Azure DevOps', 'icon' => 'https://cdn.simpleicons.org/azuredevops/0078D7', 'type' => 'img', 'task' => 'Boards + Repos + Pipelines لمنصات Microsoft.'],
                ['name' => 'Argo CD / Flux', 'icon' => 'https://cdn.simpleicons.org/argo/EF7B4D', 'type' => 'img', 'task' => 'GitOps للنشر التلقائي على Kubernetes.'],
            ],
        ],
        [
            'title' => 'حاويات وأوركستريشن',
            'icon' => 'fab fa-docker',
            'desc' => 'تشغيل التطبيقات داخل حاويات مع إدارة clusters قابلة للتوسع.',
            'tools' => [
                ['name' => 'Docker', 'icon' => 'devicon-docker-original', 'type' => 'devicon', 'task' => 'Containerization موحد للتطوير والإنتاج.'],
                ['name' => 'Kubernetes', 'icon' => 'devicon-kubernetes-plain', 'type' => 'devicon', 'task' => 'إدارة Pods وServices وAuto-scaling.'],
                ['name' => 'Helm', 'icon' => 'https://cdn.simpleicons.org/helm/0F1689', 'type' => 'img', 'task' => 'قوالب Charts لنشر الخدمات بسرعة.'],
                ['name' => 'Kustomize', 'icon' => 'https://cdn.simpleicons.org/kubernetes/326CE5', 'type' => 'img', 'task' => 'تخصيص manifests حسب كل بيئة.'],
                ['name' => 'Docker Compose', 'icon' => 'fab fa-docker', 'type' => 'fa', 'task' => 'تشغيل بيئات متعددة الخدمات محلياً وعلى الخادم.'],
                ['name' => 'Rancher / Podman', 'icon' => 'https://cdn.simpleicons.org/rancher/0075A8', 'type' => 'img', 'task' => 'إدارة clusters وحاويات بدائل تشغيل.'],
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
                ['name' => 'Lambda / Serverless', 'icon' => 'https://cdn.simpleicons.org/awslambda/FF9900', 'type' => 'img', 'task' => 'وظائف بلا خوادم لتقليل التكلفة والتشغيل.'],
            ],
        ],
        [
            'title' => 'IaC وإدارة التكوين',
            'icon' => 'fas fa-code-branch',
            'desc' => 'تحويل البنية التحتية والإعدادات إلى كود قابل للإصدار والمراجعة.',
            'tools' => [
                ['name' => 'Terraform', 'icon' => 'devicon-terraform-plain', 'type' => 'devicon', 'task' => 'تعريف البنية كوداً مع state وإعادة استخدام modules.'],
                ['name' => 'Ansible', 'icon' => 'devicon-ansible-plain', 'type' => 'devicon', 'task' => 'Provisioning وconfiguration بدون agents.'],
                ['name' => 'Pulumi', 'icon' => 'https://cdn.simpleicons.org/pulumi/8A3391', 'type' => 'img', 'task' => 'IaC بلغات برمجة عامة للمشاريع المعقدة.'],
                ['name' => 'CloudFormation', 'icon' => 'https://cdn.simpleicons.org/amazonaws/FF9900', 'type' => 'img', 'task' => 'قوالب بنية AWS الأصلية ككود.'],
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
                ['name' => 'Grafana', 'icon' => 'devicon-grafana-original', 'type' => 'devicon', 'task' => 'لوحات متابعة لحظية ومرئية للخدمات.'],
                ['name' => 'ELK Stack', 'icon' => 'https://cdn.simpleicons.org/elastic/005571', 'type' => 'img', 'task' => 'تجميع وتحليل logs وبحث سريع في الأحداث.'],
                ['name' => 'Datadog', 'icon' => 'https://cdn.simpleicons.org/datadog/632CA6', 'type' => 'img', 'task' => 'APM ومراقبة بنية cloud-native متقدمة.'],
                ['name' => 'Vault', 'icon' => 'https://cdn.simpleicons.org/hashicorp/844FBA', 'type' => 'img', 'task' => 'إدارة أسرار ومفاتيح في بيئات CI/CD.'],
                ['name' => 'Jaeger / OpenTelemetry', 'icon' => 'https://cdn.simpleicons.org/opentelemetry/000000', 'type' => 'img', 'task' => 'Tracing لتتبّع الطلبات بين الخدمات.'],
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
