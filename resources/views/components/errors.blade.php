@if ($errors->any())
	<div class="alert alert-danger" id="error-alert">
		<ul class="m-0">
			@foreach ($errors->all() as $error)
				<li>{{ $error }}</li>
			@endforeach
		</ul>
	</div>
@endif

@if (\Illuminate\Support\Facades\Session::has('error_msg'))
	<div class="alert alert-danger" id="session-error-alert">
		<ul class="m-0">
			<li>
				{{ \Illuminate\Support\Facades\Session::get('error_msg') }}
				@if(\Illuminate\Support\Facades\Session::has('details'))
					<b>{{ \Illuminate\Support\Facades\Session::get('details') }}</b>
				@endif
			</li>
		</ul>
	</div>
@endif

@if (\Illuminate\Support\Facades\Session::has('success_msg'))
	<div class="alert alert-success" id="success-alert">
		<ul class="m-0">
			<li>{{ \Illuminate\Support\Facades\Session::get('success_msg') }}</li>
		</ul>
	</div>
@endif
