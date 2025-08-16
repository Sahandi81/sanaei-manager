@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'List') . ' ' . tr_helper('contents', 'Users'))

@section('title', $customPageName)

@section('content')
	<div class="container-xxl flex-grow-1 container-p-y">
		@include('components.pagePath')
		@include('components.errors')

		<div class="card">
			<h5 class="card-header d-flex justify-content-between align-items-center">
				{{ $customPageName }}
				<a href="{{ route('panel.users.create') }}" class="btn btn-sm btn-primary">
					<i class="bx bx-plus"></i> {{ tr_helper('contents', 'Create') }}
				</a>
			</h5>
			<div class="table-responsive text-nowrap">
				<table class="table">
					<thead>
					<tr>
						<th>#</th>
						<th>{{ tr_helper('validation', 'attributes.name') }}</th>
						<th>{{ tr_helper('validation', 'attributes.email') }}</th>
						<th>{{ tr_helper('validation', 'attributes.role_key') }}</th>
						<th>{{ tr_helper('validation', 'attributes.status') }}</th>
						<th>{{ tr_helper('contents', 'Actions') }}</th>
					</tr>
					</thead>
					<tbody>
					@forelse($users as $user)
						<tr>
							<td>{{ $loop->iteration }}</td>
							<td>{{ $user->name }}</td>
							<td>{{ $user->email }}</td>
							<td>{{ $user->role_key }}</td>
							<td>
                            <span class="badge bg-label-{{ $user->status ? 'success' : 'danger' }}">
                                {{ $user->status ? tr_helper('contents', 'Active') : tr_helper('contents', 'InActive') }}
                            </span>
							</td>
							<td>
								<div class="dropdown">
									<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
										<i class="bx bx-dots-vertical-rounded"></i>
									</button>
									<div class="dropdown-menu">
										<a class="dropdown-item" href="{{ route('panel.users.edit', $user->id) }}">
											<i class="bx bx-edit-alt me-1"></i> {{ tr_helper('contents', 'Edit') }}
										</a>
										<button type="button"
												class="dropdown-item text-danger open-delete-modal"
												data-bs-toggle="modal"
												data-bs-target="#deleteModal"
												data-item-name="{{ $user->name }}"
												data-delete-route="{{ route('panel.users.destroy', $user->id) }}">
											<i class="bx bx-trash me-1"></i> {{ tr_helper('contents', 'Delete') }}
										</button>
									</div>
								</div>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="6" class="text-center text-muted">
								{{ tr_helper('contents', 'NoRecordsFound') }}
							</td>
						</tr>
					@endforelse
					</tbody>
				</table>
			</div>

			<div class="mt-3">
				{{ $users->links('vendor.pagination.bootstrap-5') }}
			</div>
		</div>
	</div>

	@include('components.delete-modal', [
		'modalId' => 'deleteModal',
		'title' => tr_helper('contents', 'DeleteUser'),
		'message' => tr_helper('contents', 'AreYouSureToDeleteThisUser'),
	])
@endsection
