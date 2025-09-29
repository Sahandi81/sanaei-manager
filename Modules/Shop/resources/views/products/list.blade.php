@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'List') . ' ' . tr_helper('contents', 'Product'))

@section('title', $customPageName)

@section('content')
	<div class="container-xxl flex-grow-1 container-p-y">
		@include('components.pagePath')
		@include('components.errors')

		<div class="row">
			<div class="col-12">
				<div class="card">
					<h5 class="card-header d-flex justify-content-between align-items-center">
						{{ $customPageName }}
						<a href="{{ route('shop.products.create') }}" class="btn btn-sm btn-primary">
							<i class="bx bx-plus"></i> {{ tr_helper('contents', 'Create') }}
						</a>
					</h5>
					<div class="table-responsive text-nowrap">
						<table class="table">
							<thead>
							<tr>
								<th>#</th>
								<th>{{ tr_helper('validation', 'attributes.name') }}</th>
								@if(\Illuminate\Support\Facades\Auth::user()->role->is_admin)
									<th>{{ tr_helper('validation', 'attributes.user_id') }}</th>
								@endif
								<th>{{ tr_helper('validation', 'attributes.traffic_gb') }}</th>
								<th>{{ tr_helper('validation', 'attributes.duration_days') }}</th>
								<th>{{ tr_helper('validation', 'attributes.price') }}</th>
								<th>{{ tr_helper('validation', 'attributes.user_limit') }}</th>
								<th>{{ tr_helper('validation', 'attributes.is_active') }}</th>
								<th>{{ tr_helper('validation', 'attributes.is_test') }}</th>
								<th>{{ tr_helper('validation', 'attributes.servers') }}</th>
								<th>{{ tr_helper('contents', 'Actions') }}</th>
							</tr>
							</thead>
							<tbody class="table-border-bottom-0">
							@forelse($products as $product)
								<tr>
									<td>{{ $loop->iteration }}</td>
									<td>{{ $product->name }}</td>
									@if(\Illuminate\Support\Facades\Auth::user()->role->is_admin)
										<td><a href="#{{-- route('users.edit',  $client->user->id ) --}}">{{ $product->user?->name }}</a></td>
									@endif
									<td>{{ $product->traffic_gb }} GB</td>
									<td>{{ $product->duration_days }} days</td>
									<td>{{ number_format($product->price) }} Toman</td>
									<td>{{ $product->user_limit }}</td>
									<td>
										<span
											class="badge bg-label-{{ $product->is_active ? 'success' : 'secondary' }}">
											{{ $product->is_active ? tr_helper('contents', 'Active') : tr_helper('contents', 'Inactive') }}
										</span>
									</td>
									<td>
										<span class="badge bg-label-{{ $product->is_test ? 'warning' : 'info' }}">
											{{ $product->is_test ? tr_helper('contents', 'Test') : tr_helper('contents', 'Main') }}
										</span>
									</td>
									<td>
										{{ $product->servers->count() }}
									</td>
									<td>
										<div class="dropdown">
											<button type="button" class="btn p-0 dropdown-toggle hide-arrow"
													data-bs-toggle="dropdown">
												<i class="bx bx-dots-vertical-rounded"></i>
											</button>
											<div class="dropdown-menu">
												<a class="dropdown-item"
												   href="{{ route('shop.products.edit', $product->id) }}">
													<i class="bx bx-edit-alt me-1"></i> {{ tr_helper('contents', 'Edit') }}
												</a>
												<button type="button"
														data-bs-toggle="modal"
														class="dropdown-item text-danger open-delete-modal"
														data-bs-target="#deleteModal"
														data-item-name="{{ $product->name }}"
														data-delete-route="{{ route('shop.products.destroy', $product->id) }}">
													<i class="bx bx-trash me-1"></i> {{ tr_helper('contents', 'Delete') }}
												</button>
											</div>

										</div>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="10" class="text-center text-muted">
										{{ tr_helper('contents', 'NoRecordsFound') }}
									</td>
								</tr>
							@endforelse
							</tbody>
						</table>
					</div>
				</div>

				<div class="mt-3">
					{{ $products->links('vendor.pagination.bootstrap-5') }}
				</div>
			</div>
		</div>
		@include('components.delete-modal', [
			'modalId' => 'deleteModal',
			'title' => tr_helper('contents', 'DeleteProduct'),
			'message' => tr_helper('contents', 'AreYouSure'),
		])
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				$('#servers').select2({
					placeholder: "Select servers",
					allowClear: true,
					width: '100%'
				});
			});
		</script>
	</div>
@endsection
