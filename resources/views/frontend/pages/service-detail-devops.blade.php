@extends('frontend.layouts.master')

@section('page-title')
DevOps وتشغيل المنصات | استضافة كلاودسوفت
@endsection

@section('meta-description')
DevOps وتشغيل المنصات — CI/CD، حاويات Docker و Kubernetes، بنية كود IaC (Terraform، Ansible)، سحابة AWS/Azure/GCP، ومراقبة Prometheus و Grafana.
@endsection

@section('content')
    <!-- ============ SERVICE BANNER ============ -->
    <section class="page-banner page-banner-service">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-infinity"></i></div>
                <h1 class="page-banner-title">DevOps <span>وتشغيل المنصات</span></h1>
                <p class="page-banner-desc">تكامل ونشر مستمر (CI/CD)، حاويات وأوركستريشن، بنية كود (IaC)، سحابة ومراقبة — من البناء حتى التشغيل الآلي</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <a href="{{ route('frontend.about') }}#specialties">التخصصات</a>
                    <span class="page-banner-sep">/</span>
                    <span>DevOps</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    <!-- ============ SERVICE INTRO ============ -->
    <section class="section-padding">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="service-detail-intro animate-on-scroll">
                        <span class="section-badge">نظرة عامة</span>
                        <h2 class="service-detail-heading">ماذا نقدم في DevOps؟</h2>
                        <p class="service-detail-lead">
                            أقدم حلولاً متكاملة في ثقافة ومنهجية DevOps: أتمتة البناء والاختبار والنشر (CI/CD)، إدارة الحاويات والأوركستريشن (Docker و Kubernetes)، البنية كود (Terraform، Ansible)، والعمل على منصات سحابية (AWS، Azure، GCP) مع مراقبة وسجلات وضمان استقرار وتوفر الخدمات.
                        </p>
                        <p class="service-detail-text">
                            نربط بين التطوير والتشغيل عبر خطوط أنابيب أوتوماتيكية، بيئات قابلة للتكرار، ومراقبة الأداء والسجلات. سواء مشروعك على سيرفرات تقليدية أو سحابة أو كوبرنيتس — نضع معك البنية والعمليات المناسبة وتدريب الفريق إن لزم.
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-panel service-detail-feature-list animate-on-scroll">
                        <h4 class="service-detail-feature-list-title"><i class="fas fa-check-circle"></i> أبرز ما يميز الخدمة</h4>
                        <ul class="service-detail-feature-list-ul">
                            <li><i class="fas fa-chevron-left"></i> خطوط أنابيب CI/CD (Jenkins، GitLab CI، GitHub Actions)</li>
                            <li><i class="fas fa-chevron-left"></i> حاويات وأوركستريشن (Docker، Kubernetes، Helm)</li>
                            <li><i class="fas fa-chevron-left"></i> بنية كود IaC (Terraform، Ansible)</li>
                            <li><i class="fas fa-chevron-left"></i> نشر على سحابة (AWS، Azure، GCP)</li>
                            <li><i class="fas fa-chevron-left"></i> مراقبة وسجلات (Prometheus، Grafana، ELK)</li>
                            <li><i class="fas fa-chevron-left"></i> أتمتة وإدارة تكوين موحّدة</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ WHAT WE OFFER ============ -->
    <section class="section-padding" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">تفاصيل الخدمة</span>
                <h2>ما الذي يشمل عليه العمل؟</h2>
                <p>مراحل ومنتجات واضحة في كل مشروع DevOps</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-sync-alt"></i></div>
                        <h5>CI/CD — تكامل ونشر مستمر</h5>
                        <p>إعداد خطوط أنابيب للبناء والاختبار والنشر التلقائي (Jenkins، GitLab CI، GitHub Actions، Azure DevOps) مع إدارة البيئات والسرّات.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fab fa-docker"></i></div>
                        <h5>حاويات وأوركستريشن</h5>
                        <p>إعداد حاويات (Containerization) بـ Docker، إدارة Clusters بـ Kubernetes (K8s)، Helm و Kustomize، واختياراً Docker Swarm أو منصات مُدارة (EKS، AKS، GKE).</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-code-branch"></i></div>
                        <h5>البنية كود (IaC)</h5>
                        <p>توفير البنية وتجهيز السيرفرات عبر Terraform و Ansible (أو Puppet/Chef) لبيئات قابلة للتكرار وقابلة للإصدار.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-cloud"></i></div>
                        <h5>السحابة والخدمات المُدارة</h5>
                        <p>نشر على AWS أو Azure أو GCP أو DigitalOcean: حسابات، شبكات، تخزين، قواعد بيانات مُدارة، وخدمات serverless حيث يناسب.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-chart-line"></i></div>
                        <h5>المراقبة والسجلات</h5>
                        <p>إعداد Prometheus و Grafana للمقاييس والتنبيهات، و/أو ELK/EFK stack للسجلات، مع لوحات ودمج مع أنظمة الحوادث.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-robot"></i></div>
                        <h5>أتمتة وسكريبتات</h5>
                        <p>سكريبتات Bash/Python للصيانة والنسخ الاحتياطي، إدارة التكوين الموحّد، وربط الأدوات في سير عمل واحد.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ TECHNOLOGIES — CI/CD ============ -->
    <section class="section-padding">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">التقنيات</span>
                <h2>تقنيات DevOps — شاملة</h2>
                <p>أدوات ومنصات نعتمد عليها في التكامل والنشر والحاويات والسحابة والمراقبة</p>
            </div>

            <div class="service-devops-tech-section animate-on-scroll">
                <h5 class="service-devops-tech-cat"><i class="fas fa-sync-alt"></i> CI/CD وبناء الأنابيب</h5>
                <div class="glass-panel service-tech-wrap">
                    <div class="row g-3">
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Jenkins</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">GitLab CI/CD</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">GitHub Actions</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">CircleCI</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Travis CI</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Azure DevOps</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Argo CD</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Flux</div></div>
                    </div>
                </div>
            </div>

            <div class="service-devops-tech-section animate-on-scroll">
                <h5 class="service-devops-tech-cat"><i class="fab fa-docker"></i> حاويات وأوركستريشن</h5>
                <div class="glass-panel service-tech-wrap">
                    <div class="row g-3">
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Docker</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Kubernetes</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Helm</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Kustomize</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Docker Compose</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">containerd</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Podman</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Rancher</div></div>
                    </div>
                </div>
            </div>

            <div class="service-devops-tech-section animate-on-scroll">
                <h5 class="service-devops-tech-cat"><i class="fas fa-cloud"></i> سحابة ومنصات</h5>
                <div class="glass-panel service-tech-wrap">
                    <div class="row g-3">
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">AWS</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Azure</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Google Cloud</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">DigitalOcean</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">EKS / AKS / GKE</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Lambda / Serverless</div></div>
                    </div>
                </div>
            </div>

            <div class="service-devops-tech-section animate-on-scroll">
                <h5 class="service-devops-tech-cat"><i class="fas fa-code-branch"></i> بنية كود وإدارة تكوين (IaC)</h5>
                <div class="glass-panel service-tech-wrap">
                    <div class="row g-3">
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Terraform</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Ansible</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Puppet</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Chef</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Pulumi</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">CloudFormation</div></div>
                    </div>
                </div>
            </div>

            <div class="service-devops-tech-section animate-on-scroll">
                <h5 class="service-devops-tech-cat"><i class="fas fa-chart-line"></i> مراقبة وسجلات وأمان</h5>
                <div class="glass-panel service-tech-wrap">
                    <div class="row g-3">
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Prometheus</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Grafana</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">ELK Stack</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Datadog</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Nagios / Zabbix</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">HashiCorp Vault</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Jaeger</div></div>
                    </div>
                </div>
            </div>

            <div class="service-devops-tech-section animate-on-scroll">
                <h5 class="service-devops-tech-cat"><i class="fas fa-server"></i> أنظمة وخدمات أساسية</h5>
                <div class="glass-panel service-tech-wrap">
                    <div class="row g-3">
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Linux / Ubuntu</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Nginx</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Apache</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Git / GitHub / GitLab</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Bash / Python</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">RabbitMQ / Kafka</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Redis</div></div>
                        <div class="col-6 col-md-4 col-lg-2"><div class="service-tech-item">Istio / Linkerd</div></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ RELATED SERVICES ============ -->
    <section class="section-padding" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">تخصصات أخرى</span>
                <h2>خدمات وتخصصات ذات صلة</h2>
                <p>اطّلع على مجالات أخرى نقدم فيها الاستشارة والتنفيذ</p>
            </div>
            <div class="row g-4">
                <div class="col-sm-6 col-lg-3">
                    <a href="{{ route('frontend.service-detail') }}" class="service-related-card glass-panel animate-on-scroll">
                        <i class="fas fa-globe"></i>
                        <h6>تطوير تطبيقات الويب</h6>
                        <p>مواقع وتطبيقات ويب بـ React و Node.js</p>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a href="{{ route('frontend.service-detail-mobile') }}" class="service-related-card glass-panel animate-on-scroll">
                        <i class="fas fa-mobile-alt"></i>
                        <h6>تطبيقات الجوال</h6>
                        <p>تطوير تطبيقات أندرويد و iOS</p>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a href="{{ route('frontend.service-detail-security') }}" class="service-related-card glass-panel animate-on-scroll">
                        <i class="fas fa-shield-alt"></i>
                        <h6>أمن المعلومات</h6>
                        <p>حماية الأنظمة والبيانات وتقييم الثغرات</p>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a href="{{ route('frontend.service-detail-servers') }}" class="service-related-card glass-panel animate-on-scroll">
                        <i class="fas fa-server"></i>
                        <h6>إدارة السيرفرات</h6>
                        <p>إعداد وإدارة الخوادم والاستضافة</p>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ CTA ============ -->
    <section class="cta-section">
        <div class="container animate-on-scroll">
            <h2>هل تحتاج بنية DevOps أو أتمتة نشر لمشروعك؟</h2>
            <p>تواصل معنا الآن ونناقش متطلباتك ونقدم لك عرضاً tailored لاحتياجاتك</p>
            <a href="{{ route('frontend.contact') }}" class="btn-light-custom">
                <i class="fas fa-paper-plane"></i> تواصل معنا
            </a>
        </div>
    </section>
@endsection
