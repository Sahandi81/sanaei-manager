@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'List') . ' ' . tr_helper('contents', 'Clients'))

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
						<a href="{{ route('clients.create') }}" class="btn btn-sm btn-primary">
							<i class="bx bx-plus"></i> {{ tr_helper('contents', 'Create') }}
						</a>
					</h5>
					<div class="table-responsive text-nowrap">
						<table class="table">
							<thead>
							<tr>
								<th>#</th>
								<th>{{ tr_helper('validation', 'attributes.name') }}</th>
								<th>{{ tr_helper('validation', 'attributes.user_id') }}</th>
								<th>{{ tr_helper('validation', 'attributes.type') }}</th>
								<th>{{ tr_helper('validation', 'attributes.telegram_id') }}</th>
								<th>{{ tr_helper('validation', 'attributes.status') }}</th>
								<th>{{ tr_helper('contents', 'Actions') }}</th>
							</tr>
							</thead>
							<tbody>
							@forelse($clients as $client)
								<tr>
									<td>{{ $loop->iteration }}</td>
									<td><a href="{{ route('clients.edit', $client->id) }}">{{ $client->name }}</a></td>
									<td><a href="#{{-- route('users.edit',  $client->user->id ) --}}">{{ $client->user?->name }}</a></td>
									<td>{{ ucfirst($client->type) }}</td>
									<td>{{ $client->telegram_id ?? '-' }}</td>
									<td>
                                            <span class="badge bg-label-{{ $client->status ? 'success' : 'danger' }}">
                                                {{ $client->status ? tr_helper('contents', 'Active') : tr_helper('contents', 'InActive') }}
                                            </span>
									</td>
									<td>
										<div class="dropdown">
											<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
												<i class="bx bx-dots-vertical-rounded"></i>
											</button>
											<div class="dropdown-menu">
												<a class="dropdown-item" href="{{ route('clients.edit', $client->id) }}">
													<i class="bx bx-edit-alt me-1"></i> {{ tr_helper('contents', 'Edit') }}
												</a>
												<button type="button"
														class="dropdown-item text-danger open-delete-modal"
														data-bs-toggle="modal"
														data-bs-target="#deleteModal"
														data-item-name="{{ $client->name }}"
														data-delete-route="{{ route('clients.destroy', $client->id) }}">
													<i class="bx bx-trash me-1"></i> {{ tr_helper('contents', 'Delete') }}
												</button>
											</div>
										</div>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="7" class="text-center text-muted">
										{{ tr_helper('contents', 'NoRecordsFound') }}
									</td>
								</tr>
							@endforelse
							</tbody>
						</table>
					</div>
				</div>

				<div class="mt-3">
					{{ $clients->links('vendor.pagination.bootstrap-5') }}
				</div>
			</div>
		</div>
	</div>

	@include('components.delete-modal', [
		'modalId' => 'deleteModal',
		'title' => tr_helper('contents', 'DeleteClient'),
		'message' => tr_helper('contents', 'AreYouSureToDeleteThisClient'),
	])
@endsection
