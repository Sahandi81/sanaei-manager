<style>

	.top-traders .card {
		display: flex;
		height: 280px;
		border-radius: 10px;
		box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.3);
		transition: 0.4s ease-out;
		position: relative;
		left: 0;
	}

	.top-traders .card:hover {
		transform: translateY(-10px);
		transition: 0.4s ease-out;
	}

	.top-traders .card:hover ~ .card {
		position: relative;
		transition: 0.4s ease-out;
	}

	.top-traders .title {
		color: white;
		font-weight: 300;
	}


	.top-traders .circle {
		position: absolute;
		fill: transparent;
		z-index: -1;
		top: 10px;
	}

	.top-traders .stroke {
		stroke: white;
		stroke-dasharray: 360;
		stroke-dashoffset: 360;
		transition: 0.6s ease-out;
	}

	svg {
		stroke-width: 13px;
	}

	.top-traders .card:hover .stroke {
		stroke-dashoffset: 100;
		transition: 0.6s ease-out;
	}
</style>
<div class="top-traders flex-wrap justify-content-start d-flex my-4">
	@foreach($traders as $trader)
		@php
			$username = $trader['user'] ? (explode(' ', $trader['user']['name'])) : 'ناشناس';
			if (is_array($username)){
				$username = $username[0] . ( $username[1] ? ('.' . mb_substr($username[1],0,1)) : '' );
			}
		@endphp
		<div class="col-12 px-3 my-3 col-md-3">
			<div class="card">
				<div class="header d-flex align-items-center mt-4 mx-2">
					<div id="imagePreview" class="mx-2" style="border-radius: 50%;height: 3rem;width: 3rem;;background-image: url('{{ $trader['user']['logo'] ? asset($trader['user']['logo']) : asset('assets/img/avatars/2.png') }}');"></div>
					<h3 class="title mx-2">{{ $username }}</h3>
				</div>
				<hr>
				<div class="body px-2">
					<p class="mb-2 fw-bold d-flex justify-content-between align-items-center fs-5 px-md-3">
						<span> {{ tr_helper('contents', 'WITHDRAW_AMOUNT') }} :</span>
						<span class="ltr text-success" style="font-size: 27px"> +${{ number_format($trader['total_amount']) }} </span>
					</p>
				</div>
				<div class="card-footer">
					<p>{{ tr_helper('contents', 'ACCOUNT') }} <span class="fw-medium text-success fs-5">  {{ humanReadable($trader['account']['challenge']['stages'][0]['entry_balance'] ?? 0) }} </span> {{ tr_helper('contents', 'DOLLARABLE') }}  </p>
					<p>{{ tr_helper('contents', 'CHALLENGE') }} <a target="_blank" href="{{ route('panel.challenges.buy', $trader['account']['challenge']['id']) }}" class="fw-medium">  {{ $trader['account']['challenge']['title'] }} </a></p>
				</div>
				<div class="circle">
					<svg version="1.1" xmlns="">
						<circle class="stroke" cx="60" cy="60" r="50" style="transform: scale(.2);"/>
					</svg>
				</div>
			</div>
		</div>
	@endforeach

</div>

