@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'List') . ' ' . tr_helper('contents', 'Transactions'))

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
						<a href="{{ route('finance.transactions.create') }}" class="btn btn-sm btn-primary">
							<i class="bx bx-plus"></i> {{ tr_helper('contents', 'Create') }}
						</a>
					</h5>
					<div class="table-responsive text-nowrap">
						<table class="table">
							<thead>
							<tr>
								<th>#</th>
								<th>{{ tr_helper('validation', 'attributes.client_id') }}</th>
								<th>{{ tr_helper('validation', 'attributes.user_id') }}</th>
								<th>{{ tr_helper('validation', 'attributes.amount') }}</th>
								<th>{{ tr_helper('validation', 'attributes.status') }}</th>
								<th>{{ tr_helper('validation', 'attributes.type') }}</th>
								<th>{{ tr_helper('validation', 'attributes.created_at') }}</th>
								<th>{{ tr_helper('contents', 'Actions') }}</th>
							</tr>
							</thead>
							<tbody>
							@forelse($transactions as $transaction)
								<tr>
									<td>{{ $loop->iteration }}</td>
									<td>{{ $transaction->client?->name }}</td>
									<td><a href="#{{-- route('users.edit',  $client->user->id ) --}}">{{ $transaction->user?->name }}</a></td>
									<td>{{ number_format($transaction->amount) }} {{ $transaction->currency }}</td>
									<td>
										@php($status = (\Modules\Finance\Models\Transaction::getStatuses()[$transaction->status] ?? ['text' => 'Unknown', 'status' => 'secondary']))

										<span class="badge bg-label-{{ $status['status'] }}">
											{{ $status['text'] }}
										</span>
									</td>
									<td>{{ ucfirst($transaction->type) }}</td>
									<td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
									<td>
										<div class="dropdown">
											<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
												<i class="bx bx-dots-vertical-rounded"></i>
											</button>
											<div class="dropdown-menu">
												<a class="dropdown-item" href="{{ route('finance.transactions.edit', $transaction->id) }}">
													<i class="bx bx-edit-alt me-1"></i> {{ tr_helper('contents', 'Edit') }}
												</a>

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
					{{ $transactions->links('vendor.pagination.bootstrap-5') }}
				</div>
			</div>
		</div>
	</div>


@endsection
