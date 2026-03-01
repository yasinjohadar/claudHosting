@extends('admin.layouts.master')

@section('title')
    البروفايل
@stop


@section('page_name')
    البروفايل
@stop

@section('css')
@stop

@section('content')
    @if (\Session::has('success'))
        <div class="alert alert-success">
            <ul>
                <li>{!! \Session::get('success') !!}</li>
            </ul>
        </div>
    @endif

    @if (\Session::has('error'))
        <div class="alert alert-danger">
            <ul>
                <li>{!! \Session::get('error') !!}</li>
            </ul>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif



<!-- main-content -->
		<div class="">

			<!-- container -->
			<div class="main-container container-fluid">

		

				<!-- row -->
				<div class="row row-sm mt-3">
					<div class="col-xl-4">
						<div class="card mg-b-20">
							<div class="card-body">
								<div class="ps-0">
									<div class="main-profile-overview">
										<div class="main-img-user profile-user">
											<img alt="" src="../assets/img/users/6.jpg"><a
												class="fas fa-camera profile-edit" href="JavaScript:void(0);"></a>
										</div>
										<div class="d-flex justify-content-between mg-b-20">
											<div>
												<h5 class="main-profile-name">{{$user->name}}</h5>
												<p class="main-profile-name-text">Web Designer</p>
											</div>
										</div>
										<h6>Bio</h6>
										<div class="main-profile-bio">
											pleasure rationally encounter but because pursue consequences that are
											extremely painful.occur in which toil and pain can procure him some great
											pleasure.. <a href="">More</a>
										</div><!-- main-profile-bio -->
										<div class="row">
											<div class="col-md-4 col mb20">
												<h5>947</h5>
												<h6 class="text-small text-muted mb-0">Followers</h6>
											</div>
											<div class="col-md-4 col mb20">
												<h5>583</h5>
												<h6 class="text-small text-muted mb-0">Tweets</h6>
											</div>
											<div class="col-md-4 col mb20">
												<h5>48</h5>
												<h6 class="text-small text-muted mb-0">Posts</h6>
											</div>
										</div>
										<hr class="mg-y-30">
										<label class="main-content-label tx-13 mg-b-20">Social</label>
										<div class="main-profile-social-list">
											<div class="media">
												<div class="media-icon bg-primary-transparent text-primary">
													<i class="icon ion-logo-github"></i>
												</div>
												<div class="media-body">
													<span>Github</span> <a href="">github.com/spruko</a>
												</div>
											</div>
											<div class="media">
												<div class="media-icon bg-success-transparent text-success">
													<i class="icon ion-logo-twitter"></i>
												</div>
												<div class="media-body">
													<span>Twitter</span> <a href="">twitter.com/spruko.me</a>
												</div>
											</div>
											<div class="media">
												<div class="media-icon bg-info-transparent text-info">
													<i class="icon ion-logo-linkedin"></i>
												</div>
												<div class="media-body">
													<span>Linkedin</span> <a href="">linkedin.com/in/spruko</a>
												</div>
											</div>
											<div class="media">
												<div class="media-icon bg-danger-transparent text-danger">
													<i class="icon ion-md-link"></i>
												</div>
												<div class="media-body">
													<span>My Portfolio</span> <a href="">spruko.com/</a>
												</div>
											</div>
										</div>
										<hr class="mg-y-30">
										<h6>Skills</h6>
										<div class="skill-bar mb-4 clearfix mt-3">
											<span>HTML5 / CSS3</span>
											<div class="progress mt-2">
												<div class="progress-bar bg-primary-gradient" role="progressbar"
													aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"
													style="width: 85%"></div>
											</div>
										</div>
										<!--skill bar-->
										<div class="skill-bar mb-4 clearfix">
											<span>Javascript</span>
											<div class="progress mt-2">
												<div class="progress-bar bg-danger-gradient" role="progressbar"
													aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"
													style="width: 89%"></div>
											</div>
										</div>
										<!--skill bar-->
										<div class="skill-bar mb-4 clearfix">
											<span>Bootstrap</span>
											<div class="progress mt-2">
												<div class="progress-bar bg-success-gradient" role="progressbar"
													aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"
													style="width: 80%"></div>
											</div>
										</div>
										<!--skill bar-->
										<div class="skill-bar clearfix">
											<span>Coffee</span>
											<div class="progress mt-2">
												<div class="progress-bar bg-info-gradient" role="progressbar"
													aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"
													style="width: 95%"></div>
											</div>
										</div>
										<!--skill bar-->
									</div><!-- main-profile-overview -->
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-8">
						<div class="row row-sm">
							<div class="col-sm-12 col-xl-4 col-lg-12 col-md-12">
								<div class="card ">
									<div class="card-body">
										<div class="counter-status d-flex md-mb-0">
											<div class="counter-icon bg-primary-transparent">
												<i class="icon-layers text-primary"></i>
											</div>
											<div class="ms-auto">
												<h5 class="tx-13">Orders</h5>
												<h2 class="mb-0 tx-22 mb-1 mt-1">1,587</h2>
												<p class="text-muted mb-0 tx-11"><i
														class="si si-arrow-up-circle text-success me-1"></i>increase</p>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-sm-12 col-xl-4 col-lg-12 col-md-12">
								<div class="card ">
									<div class="card-body">
										<div class="counter-status d-flex md-mb-0">
											<div class="counter-icon bg-danger-transparent">
												<i class="icon-paypal text-danger"></i>
											</div>
											<div class="ms-auto">
												<h5 class="tx-13">Revenue</h5>
												<h2 class="mb-0 tx-22 mb-1 mt-1">46,782</h2>
												<p class="text-muted mb-0 tx-11"><i
														class="si si-arrow-up-circle text-success me-1"></i>increase</p>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-sm-12 col-xl-4 col-lg-12 col-md-12">
								<div class="card ">
									<div class="card-body">
										<div class="counter-status d-flex md-mb-0">
											<div class="counter-icon bg-success-transparent">
												<i class="icon-rocket text-success"></i>
											</div>
											<div class="ms-auto">
												<h5 class="tx-13">Product sold</h5>
												<h2 class="mb-0 tx-22 mb-1 mt-1">1,890</h2>
												<p class="text-muted mb-0 tx-11"><i
														class="si si-arrow-up-circle text-success me-1"></i>increase</p>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="card">
							<div class="card-body">
								<div class="tabs-menu ">
									<!-- Tabs -->
									<ul class="nav nav-tabs profile navtab-custom panel-tabs">
										<li class="">
											<a href="#home" data-bs-toggle="tab" class="active" aria-expanded="true"> <span
													class="visible-xs"><i
														class="las la-user-circle tx-16 me-1"></i></span> <span
													class="hidden-xs">ABOUT ME</span> </a>
										</li>
										<li class="">
											<a href="#gallery" data-bs-toggle="tab" aria-expanded="false"> <span
													class="visible-xs"><i class="las la-images tx-15 me-1"></i></span>
												<span class="hidden-xs">GALLERY</span> </a>
										</li>
										<li class="">
											<a href="#friends" data-bs-toggle="tab" aria-expanded="false"> <span
													class="visible-xs"><i class="las la-life-ring tx-16 me-1"></i></span>
												<span class="hidden-xs">FRIENDS</span> </a>
										</li>
										<li class="">
											<a href="#settings" data-bs-toggle="tab" aria-expanded="false"> <span
													class="visible-xs"><i class="las la-cog tx-16 me-1"></i></span>
												<span class="hidden-xs">SETTINGS</span> </a>
										</li>
									</ul>
								</div>
								<div class="tab-content border border-top-0 p-4 br-dark">
									<div class="tab-pane active" id="home">
										<h4 class="tx-15 text-uppercase mb-3">BIO Data</h4>
										<p class="m-b-5">Hi I'm Petey Cruiser,has been the industry's standard dummy
											text ever since the 1500s, when an unknown printer took a galley of type.
											Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim
											justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis
											eu pede mollis pretium. Integer tincidunt.Cras dapibus. Vivamus elementum
											semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor
											eu, consequat vitae, eleifend ac, enim.</p>
										<div class="m-t-30">
											<h4 class="tx-15 text-uppercase mt-3">Experience</h4>
											<div class=" p-t-10">
												<h5 class="text-primary m-b-5 tx-14">Lead designer / Developer</h5>
												<p class="">websitename.com</p>
												<p><b>2010-2015</b></p>
												<p class="text-muted tx-13 m-b-0">Lorem Ipsum is simply dummy text of
													the printing and typesetting industry. Lorem Ipsum has been the
													industry's standard dummy text ever since the 1500s, when an unknown
													printer took a galley of type and scrambled it to make a type
													specimen book.</p>
											</div>
											<hr>
											<div class="">
												<h5 class="text-primary m-b-5 tx-14">Senior Graphic Designer</h5>
												<p class="">coderthemes.com</p>
												<p><b>2007-2009</b></p>
												<p class="text-muted tx-13 mb-0">Lorem Ipsum is simply dummy text of the
													printing and typesetting industry. Lorem Ipsum has been the
													industry's standard dummy text ever since the 1500s, when an unknown
													printer took a galley of type and scrambled it to make a type
													specimen book.</p>
											</div>
										</div>
									</div>
									<div class="tab-pane" id="gallery">
										<div class="masonry row">
											<div class="col-xl-3 col-lg-4 col-sm-6">
												<div class="brick card overflow-hidden">
													<a href="../assets/img/photos/1.jpg" class="js-img-viewer"
														data-caption="IMAGE-01" data-id="lion">
														<img src="../assets/img/photos/1.jpg" alt="">
													</a>
													<h4 class="text-center tx-14 mt-3 mb-0">Gallary Image</h4>
													<div class="ga-border"></div>
													<p class="text-muted text-center"><small>Photography</small></p>
												</div>
											</div>
											<div class="col-xl-3 col-lg-4 col-sm-6">
												<div class="brick card overflow-hidden">
													<a href="../assets/img/photos/2.jpg" class="js-img-viewer"
														data-caption="IMAGE-02" data-id="camel">
														<img src="../assets/img/photos/2.jpg" alt="">
													</a>
													<h4 class="text-center tx-14 mt-3 mb-0">Gallary Image</h4>
													<div class="ga-border"></div>
													<p class="text-muted text-center"><small>Photography</small></p>
												</div>
											</div>
											<div class="col-xl-3 col-lg-4 col-sm-6">
												<div class="brick card overflow-hidden">
													<a href="../assets/img/photos/3.jpg" class="js-img-viewer"
														data-caption="IMAGE-03" data-id="hippo">
														<img src="../assets/img/photos/3.jpg" alt="">
													</a>
													<h4 class="text-center tx-14 mt-3 mb-0">Gallary Image</h4>
													<div class="ga-border"></div>
													<p class="text-muted text-center"><small>Photography</small></p>
												</div>
											</div>
											<div class="col-xl-3 col-lg-4 col-sm-6">
												<div class="brick card overflow-hidden">
													<a href="../assets/img/photos/4.jpg" class="js-img-viewer"
														data-caption="IMAGE-04" data-id="koala">
														<img src="../assets/img/photos/4.jpg" alt="">
													</a>
													<h4 class="text-center tx-14 mt-3 mb-0">Gallary Image</h4>
													<div class="ga-border"></div>
													<p class="text-muted text-center"><small>Photography</small></p>
												</div>
											</div>
											<div class="col-xl-3 col-lg-4 col-sm-6">
												<div class="brick card overflow-hidden">
													<a href="../assets/img/photos/5.jpg" class="js-img-viewer"
														data-caption="IMAGE-05" data-id=" bear">
														<img src="../assets/img/photos/5.jpg" alt="">
													</a>
													<h4 class="text-center tx-14 mt-3 mb-0">Gallary Image</h4>
													<div class="ga-border"></div>
													<p class="text-muted text-center"><small>Photography</small></p>
												</div>
											</div>
											<div class="col-xl-3 col-lg-4 col-sm-6">
												<div class="brick card overflow-hidden">
													<a href="../assets/img/photos/6.jpg" class=" js-img-viewer"
														data-caption="IMAGE-06" data-id=" rhino">
														<img src="../assets/img/photos/6.jpg" alt="">
													</a>
													<h4 class="text-center tx-14 mt-3 mb-0">Gallary Image</h4>
													<div class="ga-border"></div>
													<p class="text-muted text-center"><small>Photography</small></p>
												</div>
											</div>
											<div class="col-xl-3 col-lg-4 col-sm-6">
												<div class="brick card overflow-hidden">
													<a href="../assets/img/photos/7.jpg" class=" js-img-viewer"
														data-caption="IMAGE-07" data-id=" rhino">
														<img src="../assets/img/photos/7.jpg" alt="">
													</a>
													<h4 class="text-center tx-14 mt-3 mb-0">Gallary Image</h4>
													<div class="ga-border"></div>
													<p class="text-muted text-center"><small>Photography</small></p>
												</div>
											</div>
											<div class="col-xl-3 col-lg-4 col-sm-6">
												<div class="brick card overflow-hidden">
													<a href="../assets/img/photos/8.jpg" class=" js-img-viewer"
														data-caption="IMAGE-08" data-id=" rhino">
														<img src=" ../assets/img/photos/8.jpg" alt="">
													</a>
													<h4 class="text-center tx-14 mt-3 mb-0">Gallary Image</h4>
													<div class="ga-border"></div>
													<p class="text-muted text-center"><small>Photography</small></p>
												</div>
											</div>
											<div class="col-xl-3 col-lg-4 col-sm-6">
												<div class="brick card overflow-hidden">
													<a href="../assets/img/photos/9.jpg" class=" js-img-viewer"
														data-caption="IMAGE-09" data-id=" rhino">
														<img src="../assets/img/photos/9.jpg" alt="">
													</a>
													<h4 class="text-center tx-14 mt-3 mb-0">Gallary Image</h4>
													<div class="ga-border"></div>
													<p class="text-muted text-center"><small>Photography</small></p>
												</div>
											</div>
											<div class="col-xl-3 col-lg-4 col-sm-6">
												<div class="brick card overflow-hidden">
													<a href="../assets/img/photos/10.jpg" class=" js-img-viewer"
														data-caption="IMAGE-10" data-id=" rhino">
														<img src="../assets/img/photos/10.jpg" alt="">
													</a>
													<h4 class="text-center tx-14 mt-3 mb-0">Gallary Image</h4>
													<div class="ga-border"></div>
													<p class="text-muted text-center"><small>Photography</small></p>
												</div>
											</div>
											<div class="col-xl-3 col-lg-4 col-sm-6">
												<div class="brick card overflow-hidden">
													<a href="../assets/img/photos/11.jpg" class=" js-img-viewer"
														data-caption="IMAGE-11" data-id=" rhino">
														<img src="../assets/img/photos/11.jpg" alt="">
													</a>
													<h4 class="text-center tx-14 mt-3 mb-0">Gallary Image</h4>
													<div class="ga-border"></div>
													<p class="text-muted text-center"><small>Photography</small></p>
												</div>
											</div>
											<div class="col-xl-3 col-lg-4 col-sm-6">
												<div class="brick card overflow-hidden">
													<a href="../assets/img/photos/12.jpg" class=" js-img-viewer"
														data-caption="IMAGE-11" data-id=" rhino">
														<img src="../assets/img/photos/12.jpg" alt="">
													</a>
													<h4 class="text-center tx-14 mt-3 mb-0">Gallary Image</h4>
													<div class="ga-border"></div>
													<p class="text-muted text-center"><small>Photography</small></p>
												</div>
											</div>
										</div>
									</div>
									<div class="tab-pane" id="friends">
										<div class="row row-sm">
											<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-3">
												<div class="card custom-card border">
													<div class="card-body  user-lock text-center">
														<div class="dropdown float-end">
															<a href="javascript:void(0);" class="option-dots" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true"> <i class="fe fe-more-vertical"></i> </a>
															<div class="dropdown-menu shadow"> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-message-square me-2"></i>
																	Message</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-edit-2 me-2"></i> Edit</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-eye me-2"></i> View</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-trash-2 me-2"></i> Delete</a>
															</div>
														</div>
														<a href="profile.html">
															<img alt="avatar" class="rounded-circle" src="../assets/img/users/1.jpg">
															<h5 class="fs-16 mb-0 mt-3 text-dark fw-semibold">James Thomas</h5>
															<span class="text-muted">Web designer</span>
															<div class="mt-3 d-flex mx-auto text-center justify-content-center">
																<span class="btn btn-icon me-3 btn-facebook">
																	<span class="btn-inner--icon"> <i class="bx bxl-facebook tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon me-3">
																	<span class="btn-inner--icon"> <i class="bx bxl-twitter tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon">
																	<span class="btn-inner--icon"> <i class="bx bxl-linkedin tx-18 tx-prime"></i>
																	</span>
																</span>
															</div>
														</a>
													</div>
												</div>
											</div>
											<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-3">
												<div class="card custom-card border">
													<div class="card-body  user-lock text-center">
														<div class="dropdown float-end">
															<a href="javascript:void(0);" class="option-dots" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true"> <i class="fe fe-more-vertical"></i> </a>
															<div class="dropdown-menu shadow"> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-message-square me-2"></i>
																	Message</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-edit-2 me-2"></i> Edit</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-eye me-2"></i> View</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-trash-2 me-2"></i> Delete</a>
															</div>
														</div>
														<a href="profile.html">
															<img alt="avatar" class="rounded-circle" src="../assets/img/users/3.jpg">
															<h5 class="fs-16 mb-0 mt-3 text-dark fw-semibold">Reynante
																Labares</h5>
															<span class="text-muted">Web designer</span>
															<div class="mt-3 d-flex mx-auto text-center justify-content-center">
																<span class="btn btn-icon me-3 btn-facebook">
																	<span class="btn-inner--icon"> <i class="bx bxl-facebook tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon me-3">
																	<span class="btn-inner--icon"> <i class="bx bxl-twitter tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon">
																	<span class="btn-inner--icon"> <i class="bx bxl-linkedin tx-18 tx-prime"></i>
																	</span>
																</span>
															</div>
														</a>
													</div>
												</div>
											</div>
											<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-3">
												<div class="card custom-card border">
													<div class="card-body  user-lock text-center">
														<div class="dropdown float-end">
															<a href="javascript:void(0);" class="option-dots" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true"> <i class="fe fe-more-vertical"></i> </a>
															<div class="dropdown-menu shadow"> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-message-square me-2"></i>
																	Message</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-edit-2 me-2"></i> Edit</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-eye me-2"></i> View</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-trash-2 me-2"></i> Delete</a>
															</div>
														</div>
														<a href="profile.html">
															<img alt="avatar" class="rounded-circle" src="../assets/img/users/4.jpg">
															<h5 class="fs-16 mb-0 mt-3 text-dark fw-semibold">Owen
																Bongcaras</h5>
															<span class="text-muted">Web designer</span>
															<div class="mt-3 d-flex mx-auto text-center justify-content-center">
																<span class="btn btn-icon me-3 btn-facebook">
																	<span class="btn-inner--icon"> <i class="bx bxl-facebook tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon me-3">
																	<span class="btn-inner--icon"> <i class="bx bxl-twitter tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon">
																	<span class="btn-inner--icon"> <i class="bx bxl-linkedin tx-18 tx-prime"></i>
																	</span>
																</span>
															</div>
														</a>
													</div>
												</div>
											</div>
											<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-3">
												<div class="card custom-card border">
													<div class="card-body  user-lock text-center">
														<div class="dropdown float-end">
															<a href="javascript:void(0);" class="option-dots" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true"> <i class="fe fe-more-vertical"></i> </a>
															<div class="dropdown-menu shadow"> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-message-square me-2"></i>
																	Message</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-edit-2 me-2"></i> Edit</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-eye me-2"></i> View</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-trash-2 me-2"></i> Delete</a>
															</div>
														</div>
														<a href="profile.html">
															<img alt="avatar" class="rounded-circle" src="../assets/img/users/8.jpg">
															<h5 class="fs-16 mb-0 mt-3 text-dark fw-semibold">Stephen
																Metcalfe</h5>
															<span class="text-muted">Administrator</span>
															<div class="mt-3 d-flex mx-auto text-center justify-content-center">
																<span class="btn btn-icon me-3 btn-facebook">
																	<span class="btn-inner--icon"> <i class="bx bxl-facebook tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon me-3">
																	<span class="btn-inner--icon"> <i class="bx bxl-twitter tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon">
																	<span class="btn-inner--icon"> <i class="bx bxl-linkedin tx-18 tx-prime"></i>
																	</span>
																</span>
															</div>
														</a>
													</div>
												</div>
											</div>
											<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-3">
												<div class="card custom-card border">
													<div class="card-body  user-lock text-center">
														<div class="dropdown float-end">
															<a href="javascript:void(0);" class="option-dots" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true"> <i class="fe fe-more-vertical"></i> </a>
															<div class="dropdown-menu shadow"> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-message-square me-2"></i>
																	Message</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-edit-2 me-2"></i> Edit</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-eye me-2"></i> View</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-trash-2 me-2"></i> Delete</a>
															</div>
														</div>
														<a href="profile.html">
															<img alt="avatar" class="rounded-circle" src="../assets/img/users/2.jpg">
															<h5 class="fs-16 mb-0 mt-3 text-dark fw-semibold">Socrates
																Itumay</h5>
															<span class="text-muted">Project Manager</span>
															<div class="mt-3 d-flex mx-auto text-center justify-content-center">
																<span class="btn btn-icon me-3 btn-facebook">
																	<span class="btn-inner--icon"> <i class="bx bxl-facebook tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon me-3">
																	<span class="btn-inner--icon"> <i class="bx bxl-twitter tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon">
																	<span class="btn-inner--icon"> <i class="bx bxl-linkedin tx-18 tx-prime"></i>
																	</span>
																</span>
															</div>
														</a>
													</div>
												</div>
											</div>
											<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-3">
												<div class="card custom-card border">
													<div class="card-body  user-lock text-center">
														<div class="dropdown float-end">
															<a href="javascript:void(0);" class="option-dots" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true"> <i class="fe fe-more-vertical"></i> </a>
															<div class="dropdown-menu shadow"> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-message-square me-2"></i>
																	Message</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-edit-2 me-2"></i> Edit</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-eye me-2"></i> View</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-trash-2 me-2"></i> Delete</a>
															</div>
														</div>
														<a href="profile.html">
															<img alt="avatar" class="rounded-circle" src="../assets/img/users/3.jpg">
															<h5 class="fs-16 mb-0 mt-3 text-dark fw-semibold">Reynante
																Labares</h5>
															<span class="text-muted">Web Designer</span>
															<div class="mt-3 d-flex mx-auto text-center justify-content-center">
																<span class="btn btn-icon me-3 btn-facebook">
																	<span class="btn-inner--icon"> <i class="bx bxl-facebook tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon me-3">
																	<span class="btn-inner--icon"> <i class="bx bxl-twitter tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon">
																	<span class="btn-inner--icon"> <i class="bx bxl-linkedin tx-18 tx-prime"></i>
																	</span>
																</span>
															</div>
														</a>
													</div>
												</div>
											</div>
											<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-3">
												<div class="card custom-card border">
													<div class="card-body  user-lock text-center">
														<div class="dropdown float-end">
															<a href="javascript:void(0);" class="option-dots" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true"> <i class="fe fe-more-vertical"></i> </a>
															<div class="dropdown-menu shadow"> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-message-square me-2"></i>
																	Message</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-edit-2 me-2"></i> Edit</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-eye me-2"></i> View</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-trash-2 me-2"></i> Delete</a>
															</div>
														</div>
														<a href="profile.html">
															<img alt="avatar" class="rounded-circle" src="../assets/img/users/4.jpg">
															<h5 class="fs-16 mb-0 mt-3 text-dark fw-semibold">Owen
																Bongcaras</h5>
															<span class="text-muted">App Developer</span>
															<div class="mt-3 d-flex mx-auto text-center justify-content-center">
																<span class="btn btn-icon me-3 btn-facebook">
																	<span class="btn-inner--icon"> <i class="bx bxl-facebook tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon me-3">
																	<span class="btn-inner--icon"> <i class="bx bxl-twitter tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon">
																	<span class="btn-inner--icon"> <i class="bx bxl-linkedin tx-18 tx-prime"></i>
																	</span>
																</span>
															</div>
														</a>
													</div>
												</div>
											</div>
											<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-3">
												<div class="card custom-card border">
													<div class="card-body  user-lock text-center">
														<div class="dropdown float-end">
															<a href="javascript:void(0);" class="option-dots" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true"> <i class="fe fe-more-vertical"></i> </a>
															<div class="dropdown-menu shadow"> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-message-square me-2"></i>
																	Message</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-edit-2 me-2"></i> Edit</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-eye me-2"></i> View</a> <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-trash-2 me-2"></i> Delete</a>
															</div>
														</div>
														<a href="profile.html">
															<img alt="avatar" class="rounded-circle" src="../assets/img/users/8.jpg">
															<h5 class="fs-16 mb-0 mt-3 text-dark fw-semibold">Stephen
																Metcalfe</h5>
															<span class="text-muted">Administrator</span>
															<div class="mt-3 d-flex mx-auto text-center justify-content-center">
																<span class="btn btn-icon me-3 btn-facebook">
																	<span class="btn-inner--icon"> <i class="bx bxl-facebook tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon me-3">
																	<span class="btn-inner--icon"> <i class="bx bxl-twitter tx-18 tx-prime"></i>
																	</span>
																</span>
																<span class="btn btn-icon">
																	<span class="btn-inner--icon"> <i class="bx bxl-linkedin tx-18 tx-prime"></i>
																	</span>
																</span>
															</div>
														</a>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="tab-pane" id="settings">
										<form>
											<div class="form-group">
												<label for="FullName">Full Name</label>
												<input type="text" value="John Doe" id="FullName" class="form-control">
											</div>
											<div class="form-group">
												<label for="Email">Email</label>
												<input type="email" value="first.last@example.com" id="Email"
													class="form-control">
											</div>
											<div class="form-group">
												<label for="Username">Username</label>
												<input type="text" value="john" id="Username" class="form-control">
											</div>
											<div class="form-group">
												<label for="Password">Password</label>
												<input type="password" placeholder="6 - 15 Characters" id="Password"
													class="form-control">
											</div>
											<div class="form-group">
												<label for="RePassword">Re-Password</label>
												<input type="password" placeholder="6 - 15 Characters" id="RePassword"
													class="form-control">
											</div>
											<div class="form-group">
												<label for="AboutMe">About Me</label>
												<textarea id="AboutMe"
													class="form-control">Loren gypsum dolor sit mate, consecrate disciplining lit, tied diam nonunion nib modernism tincidunt it Loretta dolor manga Amalia erst volute. Ur wise denim ad minim venial, quid nostrum exercise ration perambulator suspicious cortisol nil it applique ex ea commodore consequent.</textarea>
											</div>
											<button class="btn btn-primary waves-effect waves-light w-md"
												type="submit">Save</button>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- row closed -->
			</div>
			<!-- Container closed -->
		</div>
		<!-- main-content closed -->

    


    </div><!-- end card-body -->
    </div><!-- end card -->
    </div>
    <!-- end col -->
    </div>

@stop




@section('script')
@stop
