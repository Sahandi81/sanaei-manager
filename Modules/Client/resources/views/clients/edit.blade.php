@php use Modules\Shop\Models\Order; @endphp
@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'Edit') . ' ' . tr_helper('contents', 'Client'))

@section('title', $customPageName)

@section('vendor-style')
	@vite([
		'resources/assets/vendor/libs/select2/select2.scss',
		'resources/assets/vendor/libs/@form-validation/form-validation.scss',
		'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'
	])
@endsection

@section('vendor-script')
	@vite([
		'resources/assets/vendor/libs/select2/select2.js',
		'resources/assets/vendor/libs/@form-validation/popular.js',
		'resources/assets/vendor/libs/@form-validation/auto-focus.js',
	])
@endsection

@section('content')
	<div class="container-xxl flex-grow-1 container-p-y">
		@include('components.pagePath')
		@include('components.errors')
		@if($client->type === 'panel')
			<div class="row flex justify-content-end">
				<button type="button" class="btn btn-success d-inline btn-11rem ms-2 my-2" data-bs-toggle="modal"
						data-bs-target="#buyServiceModal">
					{{ tr_helper('contents', 'BuyService') }} <i class="bx bx-plus"></i>
				</button>
			</div>
		@endif
		<div class="row">
			<div class="col-12">
				<div class="card">
					<h5 class="card-header">{{ $customPageName }}</h5>

					<div class="card-body">
						<form id="clientForm" method="POST" action="{{ route('clients.update', $client->id) }}"
							  class="row g-3" data-dynamic-validation>
							@csrf
							<input type="hidden" name="form" value="UPDATE">

							@if(auth()->user()->role->is_admin)
								<x-form.form-input
									name="user_id"
									type="select"
									:options="$users->pluck('name', 'id')"
									:value="$client->user_id"
									required
								/>
							@endif

							<x-form.form-input
								name="name"
								required
								:value="$client->name"
								:validation="['minLength' => 3, 'maxLength' => 255]"
							/>

							<x-form.form-input
								name="telegram_id"
								type="number"
								:value="$client->telegram_id"
							/>

							<x-form.form-input
								name="type"
								type="select"
								:options="['telegram' => 'Telegram', 'panel' => 'Panel']"
								:value="$client->type"
								:disabled="$client->type === 'panel' ? true : false"
								required
							/>

							<x-form.form-input
								name="desc"
								type="textarea"
								:value="$client->desc"
							/>

							<x-form.form-input
								name="status"
								type="select"
								:options="[1 => tr_helper('contents', 'Active'), 0 => tr_helper('contents','InActive')]"
								:value="$client->status"
								required
							/>

							<div class="col-12">
								<button type="submit" class="btn btn-primary btn-9rem" data-submit-button>
									<span class="btn-text">{{ tr_helper('contents', 'Update') }}</span>
									<span class="spinner-border spinner-border-sm d-none" role="status"
										  aria-hidden="true"></span>
								</button>


							</div>
						</form>
					</div>
				</div>

				<!-- Orders History -->
				<div class="card mt-4">
					<h5 class="card-header">{{ tr_helper('contents', 'PurchaseHistory') }}</h5>
					<div class="card-body">
						<div class="table-responsive">
							<table class="table datatable">
								<thead>
								<tr>
									<th>{{ tr_helper('contents', 'Product') }}</th>
									<th>{{ tr_helper('validation', 'attributes.price') }}</th>
									<th>{{ tr_helper('validation', 'attributes.traffic_gb') }}</th>
									<th>{{ tr_helper('validation', 'attributes.used_traffic_gb') }}</th>
									<th>{{ tr_helper('validation', 'attributes.duration_days') }}</th>
									<th>{{ tr_helper('validation', 'attributes.purchase_date') }}</th>
									<th>{{ tr_helper('validation', 'attributes.expires_at') }}</th>
									<th>{{ tr_helper('validation', 'attributes.status') }}</th>
									<th>{{ tr_helper('contents', 'Actions') }}</th>
								</tr>
								</thead>
								<tbody>
								@foreach($client->orders as $order)
									<tr>
										<td><a href="{{ route('shop.products.edit', $order->product?->id, 0) }}">{{ $order->product?->name }}</a></td>
										<td>{{ number_format($order->price) }}</td>
										<td>{{ $order->traffic_gb }} GB</td>
										<td>{{ $order->used_traffic_gb }} GB</td>
										<td>{{ $order->duration_days }} {{ tr_helper('contents', 'Days') }}</td>
										<td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
										<td>{{ $order->expires_at }}</td>
										<td>
											@php($status = (Order::getStatuses()[$order->status] ?? ['text' => 'Unknown', 'status' => 'secondary']))

											<span class="badge bg-label-{{ $status['status'] }}">
											{{ $status['text'] }}
										</span>
										</td>
										<td>
											@if($order->qr_path)
												<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
														data-bs-target="#qrModal"
														data-qr-url="{{ asset('storage/' . $order->qr_path) }}"
														data-subs="{{ route('shop.orders.subs', $order->subs) }}">
													<i class="bx bx-qr-scan"></i>
												</button>
											@endif

										</td>
									</tr>
								@endforeach
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Buy Service Modal -->
	<div class="modal fade" id="buyServiceModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">{{ tr_helper('contents', 'BuyService') }}</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<form id="buyServiceForm" method="POST" action="{{ route('shop.orders.store') }}">
					@csrf
					<input type="hidden" name="form" value="order_create">
					<input type="hidden" name="client_id" value="{{ $client->id }}">

					<div class="modal-body">
						<div class="row">
							<div class="col-md-6 mb-3">
								<label class="form-label">{{ tr_helper('contents', 'Product') }}</label>
								<select name="product_id" class="form-select" required>
									<option value="">{{ tr_helper('contents', 'Select a product') }}</option>
									@foreach($products as $product)
										<option value="{{ $product->id }}"
												data-price="{{ $product->price }}"
												data-traffic="{{ $product->traffic_gb }}"
												data-duration="{{ $product->duration_days }}">
											{{ $product->name }} - {{ number_format($product->price) }}
											- {{ $product->traffic_gb }}GB - {{ $product->duration_days }}Days
										</option>
									@endforeach
								</select>
							</div>

							<div class="col-md-6 mb-3">
								<label class="form-label">{{ tr_helper('validation', 'attributes.price') }}</label>
								<input type="text" class="form-control" id="productPrice" readonly>
							</div>

							<div class="col-md-6 mb-3">
								<label class="form-label">{{ tr_helper('validation', 'attributes.traffic_gb') }}</label>
								<input type="text" class="form-control" id="productTraffic" readonly>
							</div>

							<div class="col-md-6 mb-3">
								<label
									class="form-label">{{ tr_helper('validation', 'attributes.duration_days') }}</label>
								<input type="text" class="form-control" id="productDuration" readonly>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
							{{ tr_helper('contents', 'Close') }}
						</button>
						<button type="submit" class="btn btn-primary">
							{{ tr_helper('contents', 'Buy') }}
						</button>
					</div>
				</form>
			</div>
		</div>
		<!-- END QR Code Modal -->
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				$('select[name="product_id"]').change(function () {
					const selectedOption = $(this).find('option:selected');
					$('#productPrice').val(selectedOption.data('price').toLocaleString());
					$('#productTraffic').val(selectedOption.data('traffic') + ' GB');
					$('#productDuration').val(selectedOption.data('duration') + ' {{ tr_helper('contents', 'Days') }}');
				});
			});
		</script>
	</div>

	@include('client::qrModal')
@endsection

